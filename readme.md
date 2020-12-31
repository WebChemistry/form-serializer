## Usage

```php
use WebChemistry\FormSerializer\FormSerializerAwareInterface;
use WebChemistry\FormSerializer\TFormSerializer;

class Form extends NetteForm implements FormSerializerAwareInterface {

	use TFormSerializer;

}
```

```php

use Nette\Application\UI\Form;
use WebChemistry\FormSerializer\FormSerializerFactoryInterface;

class ArticleForm {

	public function __construct(private FormSerializerFactoryInterface $formSerializerFactory) {}

	public function create() {
		$serializer = $this->formSerializerFactory->create($form = new Form(), Article::class);

		return $form;
	}

}

```
