<?php declare(strict_types = 1);

namespace WebChemistry\FormSerializer\Event;

use Error;

final class EventDispatcher
{

	/** @var mixed[] */
	private array $listeners = [];

	public function addAfterNormalization(callable $callback): self
	{
		$this->listeners[AfterNormalizationEvent::class][] = $callback;

		return $this;
	}

	public function addBeforeDenormalization(callable $callback): self
	{
		$this->listeners[BeforeDenormalizationEvent::class][] = $callback;

		return $this;
	}

	public function addAfterDenormalization(callable $callback): self
	{
		$this->listeners[AfterDenormalizationEvent::class][] = $callback;

		return $this;
	}

	public function addAfterValidation(callable $callback): self
	{
		$this->listeners[AfterValidationEvent::class][] = $callback;

		return $this;
	}

	public function addError(callable $callback): self
	{
		$this->listeners[Error::class][] = $callback;

		return $this;
	}

	public function addSuccess(callable $callback): self
	{
		$this->listeners[SuccessEvent::class][] = $callback;

		return $this;
	}

	public function addFinalize(callable $callback): self
	{
		$this->listeners[FinalizeEvent::class][] = $callback;

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
