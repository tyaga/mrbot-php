<?php

declare(strict_types=1);

namespace MailIM;

class Dispatcher {
	public const EVENT_NEW_MESSAGE    = "newMessage";
	public const EVENT_CALLBACK_QUERY = "callbackQuery";
	
	public const EVENT_EDITED_MESSAGE   = "editedMessage";
	public const EVENT_DELETED_MESSAGE  = "deletedMessage";
	public const EVENT_PINNED_MESSAGE   = "pinnedMessage";
	public const EVENT_UNPINNED_MESSAGE = "unpinnedMessage";
	
	public const EVENT_NEW_CHAT_MEMBERS  = "newChatMembers";
	public const EVENT_LEFT_CHAT_MEMBERS = "leftChatMembers";
	
	private $bot;
	private $pollInterval;
	private $lastEventId = 0;
	
	private $handlers = [];
	
	/**
	 * Dispatcher constructor.
	 * @param Client $bot
	 * @param int $pollInterval
	 */
	public function __construct(Client $bot, int $pollInterval = 3) {
		$this->bot          = $bot;
		$this->pollInterval = $pollInterval;
	}
	
	/**
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 */
	public function run(): void {
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
						$handler($this->bot, $event['type'], $event['payload']);
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
	 * @return Dispatcher
	 */
	public function addHander(string $type, callable $callback): Dispatcher {
		$this->handlers[$type]   = $this->handlers[$type] ?? [];
		$this->handlers[$type][] = $callback;
		return $this;
	}
	
	/**
	 * @param Button\RowSet $buttonSet
	 * @return Dispatcher
	 */
	public function addButtonSetHandlers(Button\RowSet $buttonSet): Dispatcher {
		foreach ($buttonSet->getCallbackHash() as $callbackData => $callback) {
			$this->addHander(
				self::EVENT_CALLBACK_QUERY,
				static function(Client $bot, /** @noinspection PhpUnusedParameterInspection */ string $type, array $payload) use ($callbackData, $callback) {
					if ($payload['callbackData'] === $callbackData) {
						$callback($bot, $payload['queryId']);
					}
				}
			);
		}
		return $this;
	}
}