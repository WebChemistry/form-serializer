<?php declare(strict_types = 1);

namespace WebChemistry\FormSerializer;

use Doctrine\ORM\EntityManagerInterface;
use Nette\Application\UI\Form;
use Symfony\Component\Serializer\Serializer;
use WebChemistry\Validator\Validator;

final class FormSerializerFactory implements FormSerializerFactoryInterface
{

	public function __construct(
		private Serializer $serializer,
		private EntityManagerInterface $em,
		private ?Validator $validator = null,
	)
	{
	}

	public function create(Form $form, string $class): FormSerializer
	{
		$serializer = new FormSerializer($form, $class, $this->serializer, $this->em, $this->validator);

		if ($form instanceof FormSerializerAwareInterface) {
			$form->setSerializer($serializer);
		}

		return $serializer;
	}

}
