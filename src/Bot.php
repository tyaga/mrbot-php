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
	
	public function __construct($token, $api_url_base = "https://agent.mail.ru/bot/v1/", $name = '-', $version = '-', $timeout = 20) {
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
	 * @return array
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 */
	public function selfGet(): array {
		return $this->apiRequest('GET', 'self/get');
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
	
	private function userAgent(): string {
		return sprintf("%s/%s (uin=%s) bot-php/%s", $this->name, $this->version, $this->uin() ?? "-", self::LIBRARY_VERSION);
	}
	
	private function uin() {
		return $this->uin;
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
	public function sendText($chatId, $text, $query = []): array {
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
	public function sendFile($chatId, $fileId = "", $file = [], $query = []): array {
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
	public function sendVoice($chatId, $fileId = "", $file = [], $query = []): array {
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
}