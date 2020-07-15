<?php

declare(strict_types=1);

namespace MailIM;

class Dispatcher {
	private $bot;
	private $pollInterval;
	private $lastEventId = 0;
	
	private $handlers = [];
	
	/**
	 * Dispatcher constructor.
	 * @param Bot $bot
	 * @param int $pollInterval
	 */
	public function __construct(Bot $bot, int $pollInterval = 3) {
		$this->bot          = $bot;
		$this->pollInterval = $pollInterval;
	}
	
	/**
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 */
	public function run() {
		while (true) {
			try {
				$events = $this->bot->eventsGet($this->lastEventId, $this->pollInterval);
				$events = $events['events'];
				if (!$events) {
					continue;
				}
				
				foreach ($events as $event) {
					$handlers = $this->getHandlers($event['type']);
					
					foreach ($handlers as $handler) {
						$handler($this->bot, $event['payload']);
					}
				}
				
				$this->lastEventId = $events[count($events) - 1]['eventId'];
			} catch (\Exception $e) {
				continue;
			}
		}
	}
	
	/**
	 * @param string $type
	 * @return array
	 */
	private function getHandlers(string $type): array {
		return $this->handlers[$type] ?? [];
	}
	
	/**
	 * @param string $type
	 * @param callable $callback
	 * @return $this
	 */
	public function addHander(string $type, callable $callback) {
		$this->handlers[$type]   = $this->handlers[$type] ?? [];
		$this->handlers[$type][] = $callback;
		return $this;
	}
	
	
}