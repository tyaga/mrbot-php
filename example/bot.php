<?php

declare(strict_types=1);
require_once __DIR__ . "/../vendor/autoload.php";

define('TOKEN', '001.0083881076.1776569829:754528935');
define('CHAT_ID', "tyagunov@corp.mail.ru");
define('GROUP_ID', "AoLF07RVZ9zLWzIGf3E");

$bot = new \MailIM\Bot(TOKEN, 'https://api.icq.net/bot/v1/');

$lastEventId = 0;
while (true) {
	try {
		$events = $bot->eventsGet($lastEventId, 20);
		$events = $events['events'];
		
		foreach ($events as $event) {
			$payload = $event['payload'] ?? [];
			$chat    = $payload['chat'] ?? [];
			switch ($event['type']) {
				case 'newMessage':
					$parts = $payload['parts'] ?? [];
					if ($parts) {
						foreach ($parts as $part) {
							switch ($part['type']) {
								case 'reply':
									break;
								case 'mention':
									break;
								case 'forward':
									break;
								case 'voice':
									break;
								case 'file':
									break;
								case 'sticker':
									break;
							}
						}
					}
					
					$buttons = json_encode(
						[
							[
								new \MailIM\Button("a", "standard"),
								new \MailIM\Button("b", "standard_alert"),
							],
							[
								['text' => 'c', 'callbackData' => 'primary', "style" => "primary"],
								['text' => 'd', 'callbackData' => 'primary_alert', "style" => "primary"],
							],
							[
								['text' => 'e', 'callbackData' => 'attention', "style" => "attention"],
							]
						],
						JSON_THROW_ON_ERROR
					);
					var_dump($buttons);
					$button1 = new \MailIM\Button("a", "standard");
					
					$bot->sendText($chat['chatId'], $payload['text'] . ' -> replied by bot', ['inlineKeyboardMarkup' => $buttons]);
					
					break;
				case 'editedMessage':
					break;
				case 'deletedMessage':
					break;
				case 'pinnedMessage':
					break;
				case 'unpinnedMessage':
					break;
				case 'newChatMembers':
					break;
				case 'leftChatMembers':
					break;
				case 'callbackQuery':
					switch ($payload['callbackData']) {
						case 'standard':
							$bot->answerCallbackQuery($payload['queryId'], 'standard', false, 'http://mail.ru');
							break;
						case 'standard_alert':
							$bot->answerCallbackQuery($payload['queryId'], 'standard_alert', true, 'http://mail.ru');
							break;
						case 'primary':
							$bot->answerCallbackQuery($payload['queryId'], 'primary');
							break;
						case 'primary_alert':
							$bot->answerCallbackQuery($payload['queryId'], 'primary_alert', true);
							break;
						case 'attention':
							$bot->answerCallbackQuery($payload['queryId'], 'attention', false, 'http://mail.ru');
							break;
					}
					break;
			}
		}
		
		$lastEventId = $events[count($events) - 1]['eventId'];
	} catch (Exception $e) {
		continue;
	}
}
