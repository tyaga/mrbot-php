<?php

declare(strict_types=1);

require_once __DIR__ . "/../vendor/autoload.php";

use MailIM\Bot;
use MailIM\Dispatcher;

define('TOKEN', '001.0083881076.1776569829:754528935');
define('CHAT_ID', "tyagunov@corp.mail.ru");

$bot = new \MailIM\Bot(TOKEN, 'https://api.icq.net/bot/v1/');
//$bot->setLogger(new \Monolog\Logger('botLogger', [new Monolog\Handler\ErrorLogHandler()]));
$bot->setLogger(
	new class extends \Psr\Log\AbstractLogger {
		public function log($level, $message, array $context = []): void { error_log($message . ' ' . var_export($context, true)); }
	}
);

$dispatcher = new Dispatcher($bot, 30);

$callback = static function(/** @noinspection PhpUnusedParameterInspection */ Bot $bot, string $type, array $payload) {
	error_log($type . ' ' . var_export($payload, true));
};

$dispatcher->addHander('editedMessage', $callback);
$dispatcher->addHander('deletedMessage', $callback);
$dispatcher->addHander('pinnedMessage', $callback);
$dispatcher->addHander('unpinnedMessage', $callback);
$dispatcher->addHander('newChatMembers', $callback);

$dispatcher->addHander('leftChatMembers', $callback);

$dispatcher->addHander('pinnedMessage', $callback);

$dispatcher->addHander(
	'newMessage',
	static function(/** @noinspection PhpUnusedParameterInspection */ Bot $bot, string $type, array $payload) {
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

