<?php declare(strict_types = 1);

namespace WebChemistry\FormSerializer;

use Nette\Application\UI\Form;

interface FormSerializerFactoryInterface
{

	public function create(Form $form, string $class): FormSerializer;

}
