<?php declare(strict_types = 1);

namespace WebChemistry\FormSerializer\Context;

interface NormalizationContextFactoryInterface
{

	/**
	 * @param mixed[] $context
	 */
	public function replaceAll(array $context): NormalizationContextFactoryInterface;

	public function set(int|string $key, mixed $value): NormalizationContextFactoryInterface;

	public function setAttributesByFormControls(bool $attributesByFormControls): NormalizationContextFactoryInterface;

	/**
	 * @return mixed[]
	 */
	public function create(): array;

}
