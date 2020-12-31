<?php declare(strict_types = 1);

namespace WebChemistry\FormSerializer;

interface FormSerializerAwareInterface
{

	public function setSerializer(FormSerializer $serializer);

}
