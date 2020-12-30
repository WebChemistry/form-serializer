<?php declare(strict_types = 1);

namespace WebChemistry\FormSerializer\Event;

use Nette\Application\UI\Form;

final class BeforeDenormalizeEvent extends Event
{

	/**
	 * @param mixed[] $values
	 */
	public function __construct(
		private array $values,
		private Form $form,
	)
	{
	}

	public function getForm(): Form
	{
		return $this->form;
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

	public function setValue(string|int $index, mixed $value): void
	{
		$this->values[$index] = $value;
	}

}
