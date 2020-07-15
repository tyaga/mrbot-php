<?php

namespace MailIM\Test;

use PHPUnit\Framework\TestCase;

class Test extends TestCase {
	private const TOKEN    = "001.0083881076.1776569829:754528935";
	private const CHAT_ID  = "tyagunov@corp.mail.ru";
	private const GROUP_ID = "AoLF07RVZ9zLWzIGf3E";
	
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
		$res = $this->bot()->sendFileUpload(self::CHAT_ID, ['file:t.txt' => fopen(__DIR__ . "/t.txt", 'rb')]);
		self::assertTrue($res["ok"], $res['description'] ?? '');
		
		$res = $this->bot()->sendFileUpload(self::CHAT_ID, ["file:t.txt" => file_get_contents(__DIR__ . '/t.txt'), 'caption' => 'загруженный']);
		self::assertTrue($res["ok"], $res['description'] ?? '');
		
		$res = $this->bot()->sendFile(self::CHAT_ID, $res['fileId'], ['caption' => 'файл']);
		self::assertTrue($res["ok"], $res['description'] ?? '');
	}
	
	public function testGetInfo() {
		$res = $this->bot()->getInfo(self::CHAT_ID);
		self::assertTrue($res["ok"], $res['description'] ?? '');
		
		$res = $this->bot()->getAdmins(self::GROUP_ID);
		self::assertTrue($res["ok"], $res['description'] ?? '');
		
		$res = $this->bot()->getMembers(self::GROUP_ID);
		self::assertTrue($res["ok"], $res['description'] ?? '');
		
		$res = $this->bot()->getPendingUsers(self::GROUP_ID);
		self::assertTrue($res["ok"], $res['description'] ?? '');
		
		$res = $this->bot()->getBlockedUsers(self::GROUP_ID);
		self::assertTrue($res["ok"], $res['description'] ?? '');
		
		$res = $this->bot()->resolvePending(self::GROUP_ID, true, true);
		self::assertTrue($res["ok"], $res['description'] ?? '');
		
		$res = $this->bot()->setRules(self::GROUP_ID, "set rules");
		self::assertTrue($res["ok"], $res['description'] ?? '');
		
		$res = $this->bot()->setAbout(self::GROUP_ID, "set about");
		self::assertTrue($res["ok"], $res['description'] ?? '');
		
		$res = $this->bot()->setTitle(self::GROUP_ID, "set title");
		self::assertTrue($res["ok"], $res['description'] ?? '');
	}
	
	public function testEvents() {
		$res = $this->bot()->sendActions(self::GROUP_ID, 'typing');
		self::assertTrue($res["ok"], $res['description'] ?? '');
		
		$res = $this->bot()->sendActions(self::CHAT_ID, 'looking');
		self::assertTrue($res["ok"], $res['description'] ?? '');

//		$res = $this->bot()->eventsGet(0, 0);
//		self::assertTrue($res["ok"], $res['description'] ?? '');
	}
	
}
