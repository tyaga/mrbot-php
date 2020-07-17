<?php


namespace MailIM\Test;

use BlastCloud\Guzzler\UsesGuzzler;
use GuzzleHttp\Psr7\Response;

abstract class TestCase extends \PHPUnit\Framework\TestCase {
	use UsesGuzzler;
	
	protected function bot(): Client {
		return new Client($this->guzzler);
	}
	
	protected function mockJsonResponse(array $data = [], int $status = 201): void {
		$this->guzzler->queueResponse(new Response($status, [], json_encode($data)));
	}
	
}