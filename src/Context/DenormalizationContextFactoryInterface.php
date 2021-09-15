<?php declare(strict_types = 1);

namespace WebChemistry\FormSerializer\Context;

interface DenormalizationContextFactoryInterface
{

	/**
	 * @param mixed[] $context
	 */
	public function replaceAll(array $context): DenormalizationContextFactoryInterface;

	public function set(int|string $key, mixed $value): DenormalizationContextFactoryInterface;

	public function allowExtraAttributes(): DenormalizationContextFactoryInterface;

	/**
	 * @return mixed[]
	 */
	public function create(): array;

}
