<?php

declare(strict_types=1);

namespace MailIM\Test;

use BlastCloud\Guzzler\Guzzler;
use GuzzleHttp\Client as HTTPClient;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Middleware;
use MailIM\Client as BaseBot;
use MailIM\JsonStream;
use Psr\Http\Message\ResponseInterface as Response;

class Client extends BaseBot {
	private $guzzler;
	private $testClient;
	
	public function __construct(Guzzler $guzzler) {
		$this->guzzler = $guzzler;
		
		parent::__construct("");
	}
	
	protected function httpClient(array $httpParams = []): ClientInterface {
		if ($this->testClient) {
			return $this->testClient;
		}
		$stack = $this->guzzler->getHandlerStack();
		$stack->push(
			Middleware::mapResponse(
				static function(Response $response) {
					return $response->withBody(new JsonStream($response->getBody()));
				}
			)
		);
		
		$this->testClient = new HTTPClient(
			[
				"handler" => $stack
			]
		);
		return $this->testClient;
	}
	
}