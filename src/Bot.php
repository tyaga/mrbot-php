<?php

declare(strict_types=1);

namespace MailIM;

use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Psr\Http\Message\ResponseInterface as Response;

class Bot {
	public const LIBRARY_VERSION = "1.0.0";
	
	private $token;
	private $api_url_base;
	private $name;
	private $version;
	private $timeout;
	private $last_event_id;
	private $uin;
	
	/**
	 * Bot constructor.
	 * @param string $token
	 * @param string $api_url_base
	 * @param string $name
	 * @param string $version
	 * @param int $timeout
	 */
	public function __construct(string $token, string $api_url_base = "https://agent.mail.ru/bot/v1/", string $name = '-', string $version = '-', int $timeout = 20) {
		$this->token        = $token;
		$this->api_url_base = $api_url_base;
		$this->name         = $name;
		$this->version      = $version;
		$this->timeout      = $timeout;
		
		$this->last_event_id = 0;
		
		$token_parts = explode(":", $token);
		$this->uin   = $token_parts[count($token_parts) - 1];
	}
	
	/**
	 * @param string $method
	 * @param string $uri
	 * @param array $query
	 * @param array|resource file
	 * @return array
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 */
	private function apiRequest(string $method, string $uri, array $query = [], $file = []): array {
		$query["token"] = $this->token;

		if (!$file) {
			return $this->httpClient()->request($method, $uri, ['query' => $query])->getBody()->jsonSerialize();
		}
		
		$options = [
			'multipart' => [
				[
					'name'     => 'file',
					'filename' => $file[0],
					'contents' => $file[1],
				]
			]
		];
		foreach ($query as $key => $value) {
			$options['multipart'][] = [
				'name'     => $key,
				'contents' => $value
			];
		}
		
		return $this->httpClient()->request('POST', $uri, $options)->getBody()->jsonSerialize();
	}
	
	/**
	 * @return \GuzzleHttp\Client
	 */
	private function httpClient(): \GuzzleHttp\Client {
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
				
				'base_uri' => $this->api_url_base,
				'timeout'  => $this->timeout,
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
		return $this->apiRequest('GET', 'self/get');
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
		return $this->apiRequest('GET', 'messages/sendText', $query);
	}
	
	/**
	 * @param string $chatId
	 * @param string $fileId
	 * @param resource|array|string $file
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
	public function sendFile(string $chatId, string $fileId = "", array $file = [], array $query = []): array {
		$query = array_merge(
			$query,
			[
				"chatId" => $chatId,
			]
		);
		
		if ($file) {
			return $this->apiRequest('POST', 'messages/sendFile', $query, $file);
		}
		
		if ($fileId) {
			$query["fileId"] = $fileId;
			return $this->apiRequest('GET', 'messages/sendFile', $query);
		}
		
		throw new \RuntimeException('Either fileId or file are required');
	}
	
	/**
	 * @param string $chatId
	 * @param string $fileId
	 * @param resource|array|string $file
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
	public function sendVoice(string $chatId, string $fileId = "", array $file = [], array $query = []): array {
		$query = array_merge(
			$query,
			[
				"chatId" => $chatId,
			]
		);
		
		if ($file) {
			return $this->apiRequest('POST', 'messages/sendVoice', $query, $file);
		}
		
		if ($fileId) {
			$query["fileId"] = $fileId;
			return $this->apiRequest('GET', 'messages/sendVoice', $query);
		}
		
		throw new \RuntimeException('Either fileId or file are required');
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
		
		return $this->apiRequest('GET', 'messages/editText', $query);
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
		
		return $this->apiRequest('GET', 'messages/deleteMessages', $query);
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
			$query['showAlert'] = $showAlert;
		}
		if ($url !== "") {
			$query['url'] = $url;
		}
		
		return $this->apiRequest('GET', 'messages/answerCallbackQuery', $query);
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
		
		return $this->apiRequest('GET', 'chats/sendActions', $query);
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
		
		return $this->apiRequest('GET', 'chats/getInfo', $query);
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
		
		return $this->apiRequest('GET', 'chats/getAdmins', $query);
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
		
		return $this->apiRequest('GET', 'chats/getBlockedUsers', $query);
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
		
		return $this->apiRequest('GET', 'chats/getPendingUsers', $query);
	}
	
	/**
	 * @param string $chatId
	 * @param null|string $cursor The identifier for obtaining the continuation of the list of users. Cursor can be obtained from the results of the first/previous request getMembers.
	 * @return array
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 */
	public function getMembers(string $chatId, $cursor = null): array {
		// todo walk over cursor
		$query = [
			"chatId" => $chatId,
		];
		
		if ($cursor) {
			$query['cursor'] = $cursor;
		}
		
		return $this->apiRequest('GET', 'chats/getMembers', $query);
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
		
		return $this->apiRequest('GET', 'chats/blockUser', $query);
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
		
		return $this->apiRequest('GET', 'chats/unblockUser', $query);
	}
	
	/**
	 * @param string $chatId
	 * @param bool $approve
	 * @param string $userId
	 * @param bool|null $everyone
	 * @return array
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 */
	public function resolvePending(string $chatId, bool $approve = true, string $userId = "", $everyone = null): array {
		$query = [
			"chatId"  => $chatId,
			"approve" => $approve,
		];
		
		if ($userId !== "" && $everyone !== null) {
			throw new \RuntimeException("One of the two parameters: userId, everyone must be specified. These parameters cannot be specified at the same time");
		}
		if ($userId !== "") {
			$query['userId'] = $userId;
		}
		if ($everyone !== null) {
			$query['everyone'] = $everyone;
		}
		return $this->apiRequest('GET', 'chats/resolvePending', $query);
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
		
		return $this->apiRequest('GET', 'chats/setTitle', $query);
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
		
		return $this->apiRequest('GET', 'chats/setAbout', $query);
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
		
		return $this->apiRequest('GET', 'chats/setRules', $query);
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
		
		return $this->apiRequest('GET', 'chats/pinMessage', $query);
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
		
		return $this->apiRequest('GET', 'chats/unpinMessage', $query);
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
		
		return $this->apiRequest('GET', 'files/getInfo', $query);
	}
	
	/**
	 * @param string $lastEventId
	 * @param int $pollTime
	 * @return array
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 */
	public function eventsGet(string $lastEventId, int $pollTime): array {
		$query = [
			"lastEventId" => $lastEventId,
			"pollTime"    => $pollTime,
		];
		return $this->apiRequest('GET', 'events/get', $query);
	}
}