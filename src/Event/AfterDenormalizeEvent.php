<?php declare(strict_types = 1);

namespace WebChemistry\FormSerializer\Event;

use LogicException;
use Nette\Application\UI\Form;

class AfterDenormalizeEvent extends Event
{

	public function __construct(
		private object $object,
		private Form $form,
	)
	{
	}

	public function getForm(): Form
	{
		return $this->form;
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

}
