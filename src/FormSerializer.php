<?php declare(strict_types = 1);

namespace WebChemistry\FormSerializer;

use CallbackFilterIterator;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\BaseControl;
use Nette\Forms\Controls\Button;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use WebChemistry\FormSerializer\Event\AfterDenormalizeEvent;
use WebChemistry\FormSerializer\Event\AfterValidationEvent;
use WebChemistry\FormSerializer\Event\BeforeDenormalizeEvent;
use WebChemistry\FormSerializer\Event\EventDispatcher;
use WebChemistry\FormSerializer\Event\SuccessEvent;
use WebChemistry\Validator\Validator;

/**
 * beforeDenormalize -> (array -> object) -> afterDenormalize ->
 * (validator(object)) -> afterValidation ->
 * (persist(object)) -> success
 */
final class FormSerializer
{

	private ?object $defaultObject = null;

	private bool $allowExtraAttributes = false;

	private bool $persistObject = true;

	private bool $validateObject = true;

	/** @var mixed[] */
	private array $normalizerContext = [];

	/** @var mixed[] */
	private array $denormalizerContext = [];

	private bool $attributesByFormControls = false;

	private EventDispatcher $eventDispatcher;

	/** @var string[] */
	private array $groups = [];

	public function __construct(
		private Form $form,
		private string $class,
		private Serializer $serializer,
		private EntityManagerInterface $em,
		private ?Validator $validator = null,
	)
	{
		$this->eventDispatcher = new EventDispatcher();

		$this->prepare();
	}

	/**
	 * @param string[] $groups
	 */
	public function setGroups(array $groups): void
	{
		$this->groups = $groups;
	}

	public function setPersistObject(bool $persistObject): self
	{
		$this->persistObject = $persistObject;

		return $this;
	}

	public function setValidateObject(bool $validateObject): void
	{
		$this->validateObject = $validateObject;
	}

	public function setAttributesByFormControls(bool $attributesByFormControls = true): self
	{
		$this->attributesByFormControls = $attributesByFormControls;

		return $this;
	}

	public function setNormalizerContext(array $normalizerContext): self
	{
		$this->normalizerContext = $normalizerContext;

		return $this;
	}

	public function addToNormalizerContext(string $key, mixed $value): self
	{
		$this->normalizerContext[$key] = isset($this->normalizerContext[$key]) && is_array($this->normalizerContext[$key]) && is_array($value) ? array_merge($this->normalizerContext[$key], $value) : $value;

		return $this;
	}

	public function setDenormalizerContext(array $denormalizerContext): self
	{
		$this->denormalizerContext = $denormalizerContext;

		return $this;
	}

	public function addToDenormalizerContext(string $key, mixed $value): self
	{
		$this->denormalizerContext[$key] = isset($this->denormalizerContext[$key]) && is_array($this->denormalizerContext[$key]) && is_array($value) ? array_merge($this->denormalizerContext[$key], $value) : $value;

		return $this;
	}

	public function getForm(): Form
	{
		return $this->form;
	}

	public function allowExtraAttributes(): void
	{
		$this->allowExtraAttributes = true;
	}

	public function setDefaults(array $defaults): self
	{
		$this->form->setDefaults($defaults);

		return $this;
	}

	public function setDefaultObject(?object $object): self
	{
		$this->defaultObject = $object;

		$this->repeatDefaultObject(false);

		return $this;
	}

	public function repeatDefaultObject(bool $needObject = true): self
	{
		if (!$this->defaultObject) {
			if ($needObject) {
				throw new LogicException('Default object not set');
			}

			return $this;
		}

		$this->form->setDefaults(
			$this->serializer->normalize($this->defaultObject, null, $this->createNormalizationContext())
		);

		return $this;
	}

	private function prepare(): void
	{
		$this->form->onSuccess[] = function (Form $form, array $values): void {
			// before denormalize
			$this->eventDispatcher->dispatch($event = new BeforeDenormalizeEvent($values, $form));

			// denormalize
			$object = $this->serializer->denormalize($event->getValues(), $this->class, null, $this->createDenormalizationContext());

			// after denormalize
			$this->eventDispatcher->dispatch(new AfterDenormalizeEvent($object, $form));

			// validate
			if ($this->validateObject && $this->validator) {
				$errors = $this->validator->validate($object, null, $this->groups ?: null);

				if ($errors) {
					foreach ($errors->getViolations() as $error) {
						$form->addError($error->getError());
					}

					return;
				}
			}

			$this->eventDispatcher->dispatch(new AfterValidationEvent($object, $form));

			// persist
			if ($this->persistObject) {
				$this->em->persist($object);
				$this->em->flush();
			}

			// success
			$this->eventDispatcher->dispatch(new SuccessEvent($object, $form));
		};
	}

	public function addSuccessListener(callable $callback): self
	{
		$this->eventDispatcher->addSuccess($callback);

		return $this;
	}

	public function addBeforeDenormalizeListener(callable $callback): self
	{
		$this->eventDispatcher->addBeforeDenormalize($callback);

		return $this;
	}

	public function addAfterDenormalizeListener(callable $callback): self
	{
		$this->eventDispatcher->addAfterDenormalize($callback);

		return $this;
	}

	/**
	 * @return mixed[]
	 */
	private function createDenormalizationContext(): array
	{
		$context = $this->denormalizerContext;
		if ($this->defaultObject instanceof $this->class) {
			$context[AbstractObjectNormalizer::OBJECT_TO_POPULATE] = $this->defaultObject ?? null;
		}

		$context[AbstractObjectNormalizer::ALLOW_EXTRA_ATTRIBUTES] = $this->allowExtraAttributes;

		return $context;
	}

	/**
	 * @return mixed[]
	 */
	private function createNormalizationContext(): array
	{
		$context = $this->normalizerContext;

		if ($this->attributesByFormControls) {
			$controls = $this->form->getComponents(false, BaseControl::class);
			$controls = new CallbackFilterIterator($controls, fn (BaseControl $control) => !$control instanceof Button);

			$context[AbstractObjectNormalizer::ATTRIBUTES] = array_keys(iterator_to_array($controls));
		}

		return $context;
	}

}
