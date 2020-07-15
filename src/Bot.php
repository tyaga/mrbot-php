<?php

declare(strict_types=1);

namespace MailIM;

use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;

class Bot {
	const LIBRARY_VERSION = "1.0.0";
	
	
	private $token;
	private $apiUrlBase;
	private $name;
	private $version;
	private $timeout;
	private $lastEventId;
	private $uin;
	
	/**
	 * @var LoggerInterface
	 */
	private $logger;
	
	/**
	 * @param LoggerInterface $logger
	 */
	public function setLogger(LoggerInterface $logger) {
		$this->logger = $logger;
	}
	
	/**
	 * Bot constructor.
	 * @param string $token
	 * @param string $apiUrlBase
	 * @param string $name
	 * @param string $version
	 * @param int $timeout
	 */
	public function __construct(string $token, string $apiUrlBase = "https://agent.mail.ru/bot/v1/", string $name = '-', string $version = '-', int $timeout = 20) {
		$this->token      = $token;
		$this->apiUrlBase = $apiUrlBase;
		$this->name       = $name;
		$this->version    = $version;
		$this->timeout    = $timeout;
		
		$this->lastEventId = 0;
		
		$token_parts = explode(":", $token);
		$this->uin   = $token_parts[count($token_parts) - 1];
		
		$this->logger = new \Psr\Log\NullLogger();
	}
	
	/**
	 * @param string $uri
	 * @param array $query
	 * @param array $httpParams
	 * @return array
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 */
	private function apiRequest(string $uri, array $query = [], $httpParams = []): array {
		$query["token"] = $this->token;
		
		$this->logger->debug("<<< req :" . $uri, $query);
		$response = $this->httpClient($httpParams)->request('GET', $uri, ['query' => $query])->getBody()->jsonSerialize();
		
		$this->logger->debug(">>> resp:" . $uri, $response);
		return $response;
	}
	
	/**
	 * @param string $uri
	 * @param array $query
	 * @param array $httpParams
	 * @return array
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 */
	private function apiRequestMultipart(string $uri, array $query = [], array $httpParams = []): array {
		$query["token"] = $this->token;
		
		$options = [];
		foreach ($query as $key => $value) {
			$option = [
				'name'     => $key,
				'contents' => $value
			];
			if (strpos($key, 'file:') === 0) {
				$file               = explode(':', $key);
				$option['name']     = $file[0];
				$option['filename'] = $file[1];
			}
			
			$options['multipart'][] = $option;
		}
		
		return $this->httpClient($httpParams)->request('POST', $uri, $options)->getBody()->jsonSerialize();
	}
	
	/**
	 * @param array $httpParams
	 * @return \GuzzleHttp\Client
	 */
	private function httpClient(array $httpParams = []): \GuzzleHttp\Client {
		$stack = HandlerStack::create();
		
		$stack->push(
			Middleware::mapResponse(
				static function(Response $response) {
					$jsonStream = new JsonStream($response->getBody());
					return $response->withBody($jsonStream);
				}
			)
		);
		
		return new \GuzzleHttp\Client(
			[
				'handler' => $stack,
				
				'base_uri' => $this->apiUrlBase,
				'timeout'  => $httpParams['timeout'] ?? $this->timeout,
				'headers'  => [
					'User-Agent' => $this->userAgent(),
				]
			]
		);
	}
	
	/**
	 * @return string
	 */
	private function userAgent(): string {
		return sprintf("%s/%s (uin=%s) bot-php/%s", $this->name, $this->version, $this->uin() ?? "-", self::LIBRARY_VERSION);
	}
	
	/**
	 * @return string
	 */
	private function uin() {
		return $this->uin;
	}
	
	/**
	 * @return array
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 */
	public function selfGet(): array {
		return $this->apiRequest('self/get');
	}
	
	/**
	 * @param string $chatId
	 * @param string $text
	 * @param array $query
	 *  [
	 *      "replyMsgId"
	 *      "forwardChatId"
	 *      "forwardMsgId"
	 *      "inlineKeyboardMarkup"
	 *  ]
	 * @return array
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 */
	public function sendText(string $chatId, string $text, array $query = []): array {
		$query = array_merge(
			$query,
			[
				"chatId" => $chatId,
				"text"   => $text,
			]
		);
		return $this->apiRequest('messages/sendText', $query);
	}
	
	/**
	 * @param array $query
	 * @return bool
	 */
	private function checkFileInQuery(array $query): bool {
		$found = false;
		foreach ($query as $key => $value) {
			if ((is_resource($value) || is_string($value)) && strpos($key, 'file:') === 0) {
				$found = true;
			}
		}
		return $found;
	}
	
	/**
	 * @param string $chatId
	 * @param string $fileId
	 * @param array $query
	 *  [
	 *      "caption"
	 *      "replyMsgId"
	 *      "forwardChatId"
	 *      "forwardMsgId"
	 *      "inlineKeyboardMarkup"
	 *  ]
	 * @return array
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 */
	public function sendFile(string $chatId, string $fileId, array $query = []): array {
		$query = array_merge($query, ["chatId" => $chatId, 'fileId' => $fileId]);
		
		return $this->apiRequest('messages/sendFile', $query);
	}
	
	/**
	 * @param string $chatId
	 * @param array $query
	 *  [
	 *      "caption"
	 *      "file:..." => resource|string
	 *      "replyMsgId"
	 *      "forwardChatId"
	 *      "forwardMsgId"
	 *      "inlineKeyboardMarkup"
	 *  ]
	 * @return array
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 */
	public function sendFileUpload(string $chatId, array $query = []): array {
		$query = array_merge($query, ["chatId" => $chatId]);
		
		if (!$this->checkFileInQuery($query)) {
			throw new \RuntimeException("File param missing");
		}
		
		return $this->apiRequestMultipart('messages/sendFile', $query);
	}
	
	/**
	 * @param string $chatId
	 * @param string $fileId
	 * @param array $query
	 *  [
	 *      "replyMsgId"
	 *      "forwardChatId"
	 *      "forwardMsgId"
	 *      "inlineKeyboardMarkup"
	 *  ]
	 * @return array
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 */
	public function sendVoice(string $chatId, string $fileId, array $query = []): array {
		$query = array_merge($query, ["chatId" => $chatId, 'fileId' => $fileId]);
		
		return $this->apiRequest('messages/sendVoice', $query);
	}
	
	/**
	 * @param string $chatId
	 * @param array $query
	 *  [
	 *      "file:..." => resource|string
	 *      "replyMsgId"
	 *      "forwardChatId"
	 *      "forwardMsgId"
	 *      "inlineKeyboardMarkup"
	 *  ]
	 * @return array
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 */
	public function sendVoiceUpload(string $chatId, array $query = []): array {
		$query = array_merge($query, ["chatId" => $chatId]);
		if (!$this->checkFileInQuery($query)) {
			throw new \RuntimeException("File param missing");
		}
		return $this->apiRequestMultipart('messages/sendVoice', $query);
	}
	
	/**
	 * @param string $chatId
	 * @param int $msgId
	 * @param string $text
	 * @param array $query
	 *  [
	 *      "inlineKeyboardMarkup"
	 *  ]
	 * @return array
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 */
	public function editText(string $chatId, int $msgId, string $text, array $query = []): array {
		$query = array_merge(
			$query,
			[
				"chatId" => $chatId,
				'msgId'  => $msgId,
				'text'   => $text,
			]
		);
		
		return $this->apiRequest('messages/editText', $query);
	}
	
	/**
	 * The following restrictions are imposed on deletion:
	 *
	 * A message can only be deleted if it was sent less than 48 hours ago;
	 * The bot can delete outgoing messages in private chats and groups;
	 * The bot can delete any message in the group if it is an administrator.
	 *
	 * @param string $chatId
	 * @param int|array $msgId
	 * @return array
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 */
	public function deleteMessages(string $chatId, $msgId): array {
		$query = [
			"chatId" => $chatId,
			'msgId'  => $msgId,
		];
		
		return $this->apiRequest('messages/deleteMessages', $query);
	}
	
	/**
	 * @param string $queryId
	 * @param string $text
	 * @param bool|null $showAlert
	 * @param string $url
	 * @return array
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 */
	public function answerCallbackQuery(string $queryId, string $text = "", $showAlert = null, string $url = ""): array {
		$query = [
			'queryId' => $queryId
		];
		if ($text !== "") {
			$query['text'] = $text;
		}
		if ($showAlert !== null) {
			$query['showAlert'] = $showAlert ? 'true' : 'false';
		}
		if ($url !== "") {
			$query['url'] = $url;
		}
		return $this->apiRequest('messages/answerCallbackQuery', $query);
	}
	
	/**
	 * @param string $chatId
	 * @param string|array $actions Available values : looking, typing
	 * @return array
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 */
	public function sendActions(string $chatId, $actions): array {
		$query = [
			"chatId"  => $chatId,
			'actions' => $actions,
		];
		
		return $this->apiRequest('chats/sendActions', $query);
	}
	
	/**
	 * @param string $chatId
	 * @return array
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 */
	public function getInfo(string $chatId): array {
		$query = [
			"chatId" => $chatId,
		];
		
		return $this->apiRequest('chats/getInfo', $query);
	}
	
	/**
	 * @param string $chatId
	 * @return array
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 */
	public function getAdmins(string $chatId): array {
		$query = [
			"chatId" => $chatId,
		];
		
		return $this->apiRequest('chats/getAdmins', $query);
	}
	
	/**
	 * @param string $chatId
	 * @return array
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 */
	public function getBlockedUsers(string $chatId): array {
		$query = [
			"chatId" => $chatId,
		];
		
		return $this->apiRequest('chats/getBlockedUsers', $query);
	}
	
	/**
	 * @param string $chatId
	 * @return array
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 */
	public function getPendingUsers(string $chatId): array {
		$query = [
			"chatId" => $chatId,
		];
		
		return $this->apiRequest('chats/getPendingUsers', $query);
	}
	
	/**
	 * @param string $chatId
	 * @param null|string $cursor The identifier for obtaining the continuation of the list of users. Cursor can be obtained from the results of the first/previous request getMembers.
	 * @return array
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 */
	public function getMembers(string $chatId, $cursor = null): array {
		$result = [];
		do {
			$query = [
				"chatId" => $chatId,
			];
			if ($cursor) {
				$query['cursor'] = $cursor;
			}
			$res = $this->apiRequest('chats/getMembers', $query);
			
			$result[] = $res['members'];
			$cursor   = $res["cursor"] ?? null;
		} while ($cursor);
		
		return ['members' => array_merge(...$result), 'ok' => true];
	}
	
	/**
	 * @param string $chatId
	 * @param string $userId
	 * @param null|bool $delLastMessages
	 * @return array
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 */
	public function blockUser(string $chatId, string $userId, $delLastMessages = null): array {
		$query = [
			"chatId" => $chatId,
			"userId" => $userId,
		];
		
		if ($delLastMessages !== null) {
			$query['delLastMessages'] = $delLastMessages;
		}
		
		return $this->apiRequest('chats/blockUser', $query);
	}
	
	/**
	 * @param string $chatId
	 * @param string $userId
	 * @return array
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 */
	public function unblockUser(string $chatId, string $userId): array {
		$query = [
			"chatId" => $chatId,
			"userId" => $userId,
		];
		
		return $this->apiRequest('chats/unblockUser', $query);
	}
	
	/**
	 * @param string $chatId
	 * @param bool $approve
	 * @param string $userId
	 * @param bool|null $everyone
	 * @return array
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 */
	public function resolvePending(string $chatId, $approve = true, $everyone = null, string $userId = ""): array {
		$query = [
			"chatId"  => $chatId,
			"approve" => $approve ? 'true' : 'false',
		];
		
		if ($userId !== "" && $everyone !== null) {
			throw new \RuntimeException("One of the two parameters: userId, everyone must be specified. These parameters cannot be specified at the same time");
		}
		if ($userId !== "") {
			$query['userId'] = $userId;
		}
		if ($everyone !== null) {
			$query['everyone'] = $everyone ? 'true' : 'false';
		}
		$res = $this->apiRequest('chats/resolvePending', $query);
		
		if (!$res['ok'] && $res['description'] === 'User is not pending or nobody in pending list') {
			$res['ok'] = true;
		}
		return $res;
	}
	
	/**
	 * @param string $chatId
	 * @param string $title
	 * @return array
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 */
	public function setTitle(string $chatId, string $title): array {
		$query = [
			"chatId" => $chatId,
			"title"  => $title,
		];
		
		return $this->apiRequest('chats/setTitle', $query);
	}
	
	/**
	 * @param string $chatId
	 * @param string $about
	 * @return array
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 */
	public function setAbout(string $chatId, string $about): array {
		$query = [
			"chatId" => $chatId,
			"about"  => $about,
		];
		
		return $this->apiRequest('chats/setAbout', $query);
	}
	
	/**
	 * @param string $chatId
	 * @param string $rules
	 * @return array
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 */
	public function setRules(string $chatId, string $rules): array {
		$query = [
			"chatId" => $chatId,
			"rules"  => $rules,
		];
		
		return $this->apiRequest('chats/setRules', $query);
	}
	
	/**
	 * @param string $chatId
	 * @param string $msgId
	 * @return array
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 */
	public function pinMessage(string $chatId, string $msgId): array {
		$query = [
			"chatId" => $chatId,
			"msgId"  => $msgId,
		];
		
		return $this->apiRequest('chats/pinMessage', $query);
	}
	
	/**
	 * @param string $chatId
	 * @param string $msgId
	 * @return array
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 */
	public function unpinMessage(string $chatId, string $msgId): array {
		$query = [
			"chatId" => $chatId,
			"msgId"  => $msgId,
		];
		
		return $this->apiRequest('chats/unpinMessage', $query);
	}
	
	
	/**
	 * @param string $fileId Id of a previously uploaded file.
	 * @return array
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 */
	public function filesGetInfo(string $fileId): array {
		$query = [
			"fileId" => $fileId,
		];
		
		return $this->apiRequest('files/getInfo', $query);
	}
	
	/**
	 * @param int $lastEventId
	 * @param int $pollTime
	 * @return array
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 */
	public function eventsGet(int $lastEventId, int $pollTime): array {
		$query = [
			"lastEventId" => $lastEventId,
			"pollTime"    => $pollTime,
		];
		return $this->apiRequest('events/get', $query, ['timeout' => $pollTime]);
	}
	
	/**
	 * @param string $chatId
	 * @param string $fromMsgId
	 * @param string $count
	 * @param string $patchVersion
	 * @param null|string $toMsgId
	 * @return array
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 * @deprecated
	 *
	 */
	public function getHistory($chatId, $fromMsgId, $count, $patchVersion = 'init', $toMsgId = null) {
		if (strpos($this->apiUrlBase, "icq") === false) {
			throw new \RuntimeException('Works only for ICQ');
		}
		$params = [
			'sn'           => $chatId,
			'fromMsgId'    => $fromMsgId,
			'count'        => $count,
			'patchVersion' => $patchVersion,
		];
		
		if ($toMsgId !== null) {
			$params['tillMsgId'] = $toMsgId;
		}
		
		return $this->httpClient()->request(
			'POST',
			'https://botapi.icq.net/rapi',
			[
				'json' => [
					'method' => 'getHistory',
					'reqId'  => (string)uniqid('', true),
					'aimsid' => $this->token,
					'params' => $params,
				]
			]
		)->getBody()->jsonSerialize();
	}
}