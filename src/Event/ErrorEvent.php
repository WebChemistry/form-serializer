<?php declare(strict_types = 1);

namespace WebChemistry\FormSerializer\Event;

use Nette\Application\UI\Form;
use WebChemistry\FormSerializer\FormSerializer;

final class ErrorEvent extends Event
{

	public function __construct(
		private Form $form,
		private FormSerializer $serializer,
	)
	{
	}

	public function getSerializer(): FormSerializer
	{
		return $this->serializer;
	}

	public function getForm(): Form
	{
		return $this->form;
	}

}
