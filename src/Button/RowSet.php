<?php

declare(strict_types=1);

namespace MailIM\Button;

class RowSet {
	/**
	 * @var $buttons array[Button]
	 */
	private $buttons = [];
	
	/**
	 * @param Button $button
	 * @param int $row
	 * @return RowSet
	 */
	public function add(Button $button, int $row = 0): RowSet {
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
		foreach ($this->rowsIterator() as $row => $button) {
			/** @var $button Button */
			$result[$row]   = $result[$row] ?? [];
			$result[$row][] = $button->toArray();
		}
		return json_encode($result, JSON_THROW_ON_ERROR);
	}
	
	/**
	 * @return array
	 */
	public function getCallbackHash(): array {
		$result = [];
		foreach ($this->rowsIterator() as $row => $button) {
			/** @var $button Button */
			$result[$button->getCallbackData()] = $button->getCallback();
		}
		return $result;
	}
	
	/**
	 * @return \Generator
	 */
	public function rowsIterator(): \Generator {
		foreach ($this->buttons as $row => $buttons) {
			foreach ($buttons as $button) {
				yield $row => $button;
			}
		}
	}
}