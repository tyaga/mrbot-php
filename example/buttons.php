<?php

declare(strict_types=1);

require_once __DIR__ . "/../vendor/autoload.php";

use MailIM\Bot;
use MailIM\Button\Button;
use MailIM\Button\RowSet;
use MailIM\Button\Simple;
use MailIM\Button\Style;
use MailIM\Dispatcher;

define('TOKEN', '001.0083881076.1776569829:754528935');
define('CHAT_ID', "tyagunov@corp.mail.ru");

$bot = new Bot(TOKEN);
$bot->setLogger(
	new class extends \Psr\Log\AbstractLogger {
		public function log($level, $message, array $context = []): void { error_log($message . ' ' . var_export($context, true)); }
	}
);

$button1 = new Button("a", Style::ATTENTION);
$button1->setCallback(
	static function(Bot $bot, string $queryId) {
		$bot->answerCallbackQuery($queryId, 'ответ button "a"', false, 'http://mail.ru');
	}
);

$button2 = new Button("b", Style::DEFAULT);
$button2->setCallback(
	static function(Bot $bot, string $queryId) {
		$bot->answerCallbackQuery($queryId, 'ответ button "b"');
	}
);

$buttonSet = new RowSet();
$buttonSet
	->add($button1)
	->add($button2, 1)
	->add(new Simple("c", Style::PRIMARY, 'ответ button "c"', false, "http://yandex.ru"), 2)
	->add(new Simple("d", Style::ATTENTION, 'ответ button "c"'), 2);

$res = $bot->sendText(CHAT_ID, "test", ['inlineKeyboardMarkup' => $buttonSet->json()]);
if (!$res['ok']) {
	throw new \RuntimeException($res['description']);
}

(new Dispatcher($bot, 30))->addButtonSetHandlers($buttonSet)->run();