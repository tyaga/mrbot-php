<?php

declare(strict_types=1);

require_once __DIR__ . "/../vendor/autoload.php";

define('ICQ_TOKEN', "001.0083881076.1776569829:754528935");
define('ICQ_GROUP_ID', "682941013@chat.agent"); // test

define('MTM_TOKEN', "001.2324756584.4261213124:1000000331");
define('MTM_GROUP_ID', "Aqk4Au51O7r6dTk"); // test

$bot = new \MailIM\Bot(ICQ_TOKEN, 'https://api.icq.net/bot/v1/');
$mtm = new \MailIM\Bot(MTM_TOKEN, 'https://api.internal.myteam.mail.ru/bot/v1/');

//$bot->setLogger(new class extends \Psr\Log\AbstractLogger { public function log($level, $message, array $context = []) { error_log($message . ' ' . var_export($context, true));}});

function history(\MailIM\Bot $bot, $chatId, $msgId = 0, $chunk = 1000) {
	while (true) {
		$messages = $bot->getHistory($chatId, $msgId, $chunk);
		$messages = $messages['results']['messages'];
		if (!$messages || !count($messages)) {
			break;
		}
		
		foreach ($messages as $message) {
			if (!isset($message['text'])) {
				continue;
			}
			
			yield $message;
		}
		$msgId = $messages[count($messages) - 1]['msgId'];
	}
}

$messageMap = [];
$senderMap  = [];

foreach (history($bot, ICQ_GROUP_ID) as $message) {
	if (strpos($message["text"], "Message was deleted") !== false) {
		continue;
	}
	if ($message['class'] ?? "" === 'event') {
		continue;
	}
	
	$time   = date("Y-m-d H:m:s", $message['time'] + 3 * 60 * 60);
	$sender = $message['chat']['sender'];
	
	error_log("time=" . $time);
	
	if (isset($message['filesharing'])) {
		foreach ($message['filesharing'] ?? [] as $filesharing) {
			$fileInfo = $bot->filesGetInfo($filesharing['id']);
			$fp       = fopen($fileInfo['url'], "rb");
			$mtm->sendFileUpload(
				MTM_GROUP_ID,
				[
					"file:" . $fileInfo['filename'] => $fp,
					"caption"                       => sprintf("%s %s: %s", $sender, $time, $fileInfo['filename'])
				]
			);
		}
	} else {
		$text   = $message['text'];
		$params = [];
		
		$foundReply = 0;
		foreach ($message['parts'] ?? [] as $part) {
			if ($part['mediaType'] === 'quote' && isset($messageMap[$part['msgId']])) {
				$params['replyMsgId'] = $messageMap[$part['msgId']];
				$foundReply++;
			}
			if ($part['mediaType'] === 'text') {
				$text = $part['text'];
				$foundReply++;
			}
		}
		
		if ($foundReply < 2) {
			$text   = $message['text'];
			$params = [];
		}
		
		$res                           = $mtm->sendText(MTM_GROUP_ID, sprintf("%s %s: %s", $sender, $time, $text), $params);
		$messageMap[$message['msgId']] = $res['msgId'];
	}
}