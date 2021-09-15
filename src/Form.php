<?php declare(strict_types = 1);

namespace WebChemistry\FormSerializer;

use LogicException;
use Nette\Application\UI\Form as NetteUIForm;
use Nette\ComponentModel\IContainer;
use Nette\Utils\Arrays;
use WebChemistry\FormSerializer\Event\EventDispatcher;

class Form extends NetteUIForm
{

	private FormSerializer $formSerializer;

	/** @var callable[] */
	public array $onSerializerCreated = [];

	public function __construct(
		private FormSerializerFactoryInterface $formSerializerFactory,
	)
	{
		parent::__construct();
	}

	public function setClassName(string $className): static
	{
		if (isset($this->formSerializer)) {
			throw new LogicException('Form serializer already set.');
		}

		$this->formSerializer = $this->formSerializerFactory->create($this, $className);

		Arrays::invoke($this->onSerializerCreated, $this->formSerializer);

		return $this;
	}

	public function getSerializer(): FormSerializer
	{
		if (!isset($this->formSerializer)) {
			throw new LogicException('Call $form->setClassName() before calling getSerializer().');
		}

		return $this->formSerializer;
	}

	public function getEventDispatcher(): EventDispatcher
	{
		return $this->getSerializer()->getEventDispatcher();
	}

}
