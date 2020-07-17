<?php

declare(strict_types=1);

namespace MailIM\Button;

class Button {
	public const DEFAULT   = "";
	public const ATTENTION = "attention";
	public const PRIMARY   = "primary";
	
	private $text;
	private $callbackData;
	private $style;
	private $callback;
	
	/**
	 * Button constructor.
	 * @param string $text
	 * @param string $style
	 */
	public function __construct(string $text, string $style = self::DEFAULT) {
		$this->text  = $text;
		$this->style = $style;
		
		$this->callbackData = uniqid('', true);
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
	 * @return Button
	 */
	public function setCallback(callable $callback): Button {
		$this->callback = $callback;
		return $this;
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