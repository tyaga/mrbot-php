<?php

declare(strict_types=1);

namespace MailIM;

class ButtonSet {
	/**
	 * @var $buttons array[Button]
	 */
	private $buttons = [];
	
	/**
	 * @param Button $button
	 * @param int $row
	 * @return ButtonSet
	 */
	public function add(Button $button, int $row = 0): ButtonSet {
		$this->buttons[$row]   = $this->buttons[$row] ?? [];
		$this->buttons[$row][] = $button;
		
		return $this;
	}
	
	/**
	 * @return string
	 * @throws \JsonException
	 */
	public function json(): string {
		$result = [];
		foreach ($this->buttons as $row => $buttons) {
			$result[$row] = $result[$row] ?? [];
			foreach ($buttons as $button) {
				$result[$row][] = $button->toArray();
			}
		}
		return json_encode($result, JSON_THROW_ON_ERROR);
	}
	
	/**
	 * @return array
	 */
	public function getCallbackHash(): array {
		$result = [];
		foreach ($this->buttons as $row => $buttons) {
			foreach ($buttons as $button) {
				/**
				 * @var $button Button
				 */
				
				$result[$button->getCallbackData()] = $button->getCallback();
			}
		}
		return $result;
	}
}