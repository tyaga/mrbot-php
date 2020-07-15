<?php

declare(strict_types=1);

namespace MailIM;

class Button {
	
	private $text;
	private $callbackData;
	private $style;
	private $callback;
	
	/**
	 * Button constructor.
	 * @param string $text
	 * @param string $callbackData
	 * @param string $style
	 */
	public function __construct(string $text, string $callbackData, string $style = '') {
		$this->text         = $text;
		$this->callbackData = $callbackData;
		$this->style        = $style;
	}
	
	/**
	 * @return string
	 */
	public function getCallbackData(): string {
		return $this->callbackData;
	}
	
	/**
	 * @return callable
	 */
	public function getCallback(): callable {
		return $this->callback;
	}
	
	/**
	 * @param callable $callback
	 */
	public function setCallback(callable $callback): void {
		$this->callback = $callback;
	}
	
	/**
	 * @return array
	 */
	public function toArray(): array {
		return [
			'text'         => $this->text,
			'callbackData' => $this->callbackData,
			'style'        => $this->style
		];
	}
	
}