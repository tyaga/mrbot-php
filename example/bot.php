<?php

declare(strict_types=1);

require_once __DIR__ . "/../vendor/autoload.php";

use MailIM\Client;
use MailIM\Dispatcher;

define('TOKEN', 'use your token');
define('CHAT_ID', "use your chat id");

$bot = new \MailIM\Client(TOKEN, 'https://api.icq.net/bot/v1/');
//$bot->setLogger(new \Monolog\Logger('botLogger', [new Monolog\Handler\ErrorLogHandler()]));
$bot->setLogger(
	new class extends \Psr\Log\AbstractLogger {
		public function log($level, $message, array $context = []): void { error_log($message . ' ' . var_export($context, true)); }
	}
);

$dispatcher = new Dispatcher($bot, 30);

$callback = static function(/** @noinspection PhpUnusedParameterInspection */ Client $bot, string $type, array $payload) {
	error_log($type . ' ' . var_export($payload, true));
};

$dispatcher->addHander(Dispatcher::EVENT_EDITED_MESSAGE, $callback);
$dispatcher->addHander(Dispatcher::EVENT_DELETED_MESSAGE, $callback);
$dispatcher->addHander(Dispatcher::EVENT_PINNED_MESSAGE, $callback);
$dispatcher->addHander(Dispatcher::EVENT_UNPINNED_MESSAGE, $callback);

$dispatcher->addHander(Dispatcher::EVENT_NEW_CHAT_MEMBERS, $callback);
$dispatcher->addHander(Dispatcher::EVENT_LEFT_CHAT_MEMBERS, $callback);

$dispatcher->addHander(
	Dispatcher::EVENT_NEW_MESSAGE,
	static function(/** @noinspection PhpUnusedParameterInspection */ Client $bot, string $type, array $payload) {
		error_log($type . ' ' . var_export($payload, true));
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
	}
);

$dispatcher->run();

