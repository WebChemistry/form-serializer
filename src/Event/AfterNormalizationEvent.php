<?php declare(strict_types = 1);

namespace WebChemistry\FormSerializer\Event;

use InvalidArgumentException;
use LogicException;
use WebChemistry\FormSerializer\FormSerializer;

final class AfterNormalizationEvent extends Event
{

	/**
	 * @param mixed[] $values
	 */
	public function __construct(
		private object $object,
		private array $values,
		private FormSerializer $serializer,
	)
	{
	}

	public function getSerializer(): FormSerializer
	{
		return $this->serializer;
	}

	public function getObject(?string $typeOf = null): object
	{
		if ($typeOf && !$this->object instanceof $typeOf) {
			throw new LogicException(
				sprintf('Object must be instance of %s, %s given', $typeOf, get_debug_type($this->object))
			);
		}

		return $this->object;
	}

	/**
	 * @return mixed[]
	 */
	public function getValues(): array
	{
		return $this->values;
	}

	/**
	 * @param mixed[] $values
	 */
	public function setValues(array $values): void
	{
		$this->values = $values;
	}

	public function setValue(int|string $key, mixed $value): void
	{
		$this->values[$key] = $value;
	}

	public function changeKeyName(int|string $key, int|string $newKey): void
	{
		if (!array_key_exists($key, $this->values)) {
			throw new InvalidArgumentException(sprintf('Key %s not exists in array', $key));
		}

		$this->values[$newKey] = $this->values[$key];

		unset($this->values[$key]);
	}

}
