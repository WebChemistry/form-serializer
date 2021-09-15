<?php declare(strict_types = 1);

namespace WebChemistry\FormSerializer\Context;

use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use WebChemistry\FormSerializer\FormSerializer;

final class DenormalizationContextFactory implements DenormalizationContextFactoryInterface
{

	/** @var mixed[] */
	private array $context = [];

	private bool $allowExtraAttributes = false;

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

	public function allowExtraAttributes(): self
	{
		$this->allowExtraAttributes = true;

		return $this;
	}

	public function create(): array
	{
		$context = $this->context;
		$class = $this->serializer->getClassName();
		if ($this->serializer->getDefaultObject() instanceof $class) {
			$context[AbstractObjectNormalizer::OBJECT_TO_POPULATE] ??= $this->serializer->getDefaultObject();
		}

		$context[AbstractObjectNormalizer::ALLOW_EXTRA_ATTRIBUTES] = $this->allowExtraAttributes;

		return $context;
	}

}
