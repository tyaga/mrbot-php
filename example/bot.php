<?php

require_once __DIR__ . "/../vendor/autoload.php";

define('TOKEN', '001.0083881076.1776569829:754528935');
define('CHAT_ID', "tyagunov@corp.mail.ru");
define('GROUP_ID', "AoLF07RVZ9zLWzIGf3E");

$bot = new \MailIM\Bot(TOKEN);

$lastEventId = 0;
while (true) {
	$events = $bot->eventsGet($lastEventId, 100);
	
	var_dump($events);
	
	$lastEventId = $events['events'][count($events['events']) - 1]['eventId'];
}