<?php

declare(strict_types=1);
require_once __DIR__ . "/../vendor/autoload.php";

define('TOKEN', '001.0083881076.1776569829:754528935');
define('CHAT_ID', "tyagunov@corp.mail.ru");
define('GROUP_ID', "AoLF07RVZ9zLWzIGf3E");

$bot = new \MailIM\Bot(TOKEN);

$button1 = new \MailIM\Button("a", "cbdata1", "attention");
$button1->setCallback(
	function(\MailIM\Bot $bot, string $queryId) {
		$bot->answerCallbackQuery($queryId, 'ответ button "a"', false, 'http://mail.ru');
	}
);

$button2 = new \MailIM\Button("b", "cbdata2", "primary");
$button2->setCallback(
	function(\MailIM\Bot $bot, string $queryId) {
		$bot->answerCallbackQuery($queryId, 'ответ button "b"', false, 'http://ya.ru');
	}
);

$buttonSet = new \MailIM\ButtonSet();
$buttonSet->add($button1)->add($button2, 1);

$res = $bot->sendText(CHAT_ID, "test", ['inlineKeyboardMarkup' => $buttonSet->json()]);
if (!$res['ok']) {
	throw new Exception($res['description']);
}

$dispatcher = new \MailIM\Dispatcher($bot);
foreach ($buttonSet->getCallbackHash() as $callbackData => $callback) {
	$dispatcher->addHander(
		'callbackQuery',
		function(\MailIM\Bot $bot, $payload) use ($callbackData, $callback) {
			if ($payload['callbackData'] === $callbackData) {
				$callback($bot, $payload['queryId']);
			}
		}
	);
}
$dispatcher->run();

/*case 'standard':
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


$buttons = json_encode(
	[
		[
			['text' => 'c', 'callbackData' => 'primary', "style" => "primary"],
			['text' => 'd', 'callbackData' => 'primary', "style" => "primary"],
		],
		[
			['text' => 'e', 'callbackData' => 'attention', "style" => "attention"],
		]
	],
	JSON_THROW_ON_ERROR
);

*/