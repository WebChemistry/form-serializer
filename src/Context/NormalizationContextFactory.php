<?php declare(strict_types = 1);

namespace WebChemistry\FormSerializer\Context;

use CallbackFilterIterator;
use Nette\Forms\Controls\BaseControl;
use Nette\Forms\Controls\Button;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use WebChemistry\FormSerializer\FormSerializer;

final class NormalizationContextFactory implements NormalizationContextFactoryInterface
{

	/** @var mixed[] */
	private array $context = [];

	private bool $attributesByFormControls = true;

	public function __construct(
		private FormSerializer $serializer,
	)
	{
	}

	public function replaceAll(array $context): self
	{
		$this->context = $context;

		return $this;
	}

	public function set(int|string $key, mixed $value): self
	{
		$this->context[$key] = $value;

		return $this;
	}

	public function setAttributesByFormControls(bool $attributesByFormControls): self
	{
		$this->attributesByFormControls = $attributesByFormControls;

		return $this;
	}

	public function create(): array
	{
		$context = $this->context;
		$context['rootEntity'] = $this->serializer->getDefaultObject();

		if ($this->attributesByFormControls) {
			$controls = $this->serializer->getForm()->getComponents(false, BaseControl::class);
			$controls = new CallbackFilterIterator($controls, fn (BaseControl $control) => !$control instanceof Button);

			$context[AbstractObjectNormalizer::ATTRIBUTES] = array_keys(iterator_to_array($controls));
		}

		return $context;
	}

}
