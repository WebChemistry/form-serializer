<?php declare(strict_types = 1);

namespace WebChemistry\FormSerializer\Event;

final class EventDispatcher
{

	/** @var mixed[] */
	private array $listeners = [];

	public function addBeforeDenormalize(callable $callback): self
	{
		$this->listeners[BeforeDenormalizeEvent::class][] = $callback;

		return $this;
	}

	public function addAfterDenormalize(callable $callback): self
	{
		$this->listeners[AfterDenormalizeEvent::class][] = $callback;

		return $this;
	}

	public function addAfterValidation(callable $callback): self
	{
		$this->listeners[AfterValidationEvent::class][] = $callback;

		return $this;
	}

	public function addSuccess(callable $callback): self
	{
		$this->listeners[SuccessEvent::class][] = $callback;

		return $this;
	}

	public function dispatch(Event $event): void
	{
		foreach ($this->listeners[get_class($event)] ?? [] as $listener) {
			$listener($event);

			if ($event->isPropagationStopped()) {
				break;
			}
		}
	}

}
