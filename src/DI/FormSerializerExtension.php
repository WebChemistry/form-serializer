<?php declare(strict_types = 1);

namespace WebChemistry\FormSerializer\DI;

use Nette\DI\CompilerExtension;
use WebChemistry\FormSerializer\FormSerializerFactory;
use WebChemistry\FormSerializer\FormSerializerFactoryInterface;

final class FormSerializerExtension extends CompilerExtension
{

	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();

		$builder->addDefinition($this->prefix('factory'))
			->setType(FormSerializerFactoryInterface::class)
			->setFactory(FormSerializerFactory::class);
	}

}
