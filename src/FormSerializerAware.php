<?php declare(strict_types = 1);

namespace WebChemistry\FormSerializer;

trait FormSerializerAware
{

	private FormSerializer $serializer;

	/**
	 * @return static
	 */
	public function setSerializer(FormSerializer $serializer)
	{
		$this->serializer = $serializer;

		return $this;
	}

	public function getSerializer(): FormSerializer
	{
		return $this->serializer;
	}

}
