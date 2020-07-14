<?php

namespace MailIM\Test;

use PHPUnit\Framework\TestCase;

class Test extends TestCase {
	private const TOKEN   = "001.0083881076.1776569829:754528935";
	private const CHAT_ID = "tyagunov@corp.mail.ru";
	
	public function testSelfGet() {
		$res = $this->bot()->selfGet();
		self::assertTrue($res["ok"]);
	}
	
	protected function bot() {
		return new \MailIM\Bot(self::TOKEN);
	}
	
	public function testSendText() {
		$res = $this->bot()->sendText(self::CHAT_ID, "привет");
		self::assertTrue($res["ok"], $res['description'] ?? '');
	}
	
	public function testSendFile() {
		$res = $this->bot()->sendFile(self::CHAT_ID, null, ['t.txt', fopen(__DIR__ . "/t.txt", 'rb')]);
		self::assertTrue($res["ok"], $res['description'] ?? '');
		
		$res = $this->bot()->sendFile(self::CHAT_ID, null, ["t.txt", file_get_contents(__DIR__ . '/t.txt')], ['caption' => 'загруженный']);
		self::assertTrue($res["ok"], $res['description'] ?? '');
		
		$res = $this->bot()->sendFile(self::CHAT_ID, $res['fileId'], [], ['caption' => 'файл']);
		self::assertTrue($res["ok"], $res['description'] ?? '');
	}
}
