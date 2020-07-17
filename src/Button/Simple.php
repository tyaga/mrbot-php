<?php

declare(strict_types=1);

namespace MailIM\Button;

class Simple extends Button {
	public function __construct(string $text, string $style = self::DEFAULT, string $reply = "", $showAlert = null, string $url = "") {
		parent::__construct($text, $style);
		
		$this->setCallback(
			static function(\MailIM\Client $bot, string $queryId) use ($reply, $showAlert, $url) {
				$bot->answerCallbackQuery($queryId, $reply, $showAlert, $url);
			}
		);
	}
}