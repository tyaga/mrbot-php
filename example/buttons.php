<?php

declare(strict_types=1);

require_once __DIR__ . "/../vendor/autoload.php";

use MailIM\Button\Button;
use MailIM\Button\RowSet;
use MailIM\Button\Simple;
use MailIM\Client;
use MailIM\Dispatcher;

define('TOKEN', 'use your token');
define('CHAT_ID', "use your chat id");

$bot = new Client(TOKEN);
$bot->setLogger(
	new class extends \Psr\Log\AbstractLogger {
		public function log($level, $message, array $context = []): void { error_log($message . ' ' . var_export($context, true)); }
	}
);

$button1 = new Button("a", Button::ATTENTION);
$button1->setCallback(
	static function(Client $bot, string $queryId) {
		$bot->answerCallbackQuery($queryId, 'ответ button "a"', false, 'http://mail.ru');
	}
);

$button2 = new Button("b", Button::DEFAULT);
$button2->setCallback(
	static function(Client $bot, string $queryId) {
		$bot->answerCallbackQuery($queryId, 'ответ button "b"');
	}
);

$buttonSet = new RowSet();
$buttonSet
	->add($button1)
	->add($button2, 1)
	->add(new Simple("c", Button::PRIMARY, 'ответ button "c"', false, "http://yandex.ru"), 2)
	->add(new Simple("d", Button::ATTENTION, 'ответ button "c"'), 2);

$res = $bot->sendText(CHAT_ID, "test", ['inlineKeyboardMarkup' => $buttonSet->json()]);
if (!$res['ok']) {
	throw new \RuntimeException($res['description']);
}

(new Dispatcher($bot, 30))->addButtonSetHandlers($buttonSet)->run();