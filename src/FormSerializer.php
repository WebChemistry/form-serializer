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
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Tracy\Debugger;
use WebChemistry\FormSerializer\Context\DenormalizationContextFactory;
use WebChemistry\FormSerializer\Context\NormalizationContextFactory;
use WebChemistry\FormSerializer\Context\NormalizationContextFactoryInterface;
use WebChemistry\FormSerializer\Event\AfterDenormalizationEvent;
use WebChemistry\FormSerializer\Event\AfterNormalizationEvent;
use WebChemistry\FormSerializer\Event\AfterValidationEvent;
use WebChemistry\FormSerializer\Event\BeforeDenormalizationEvent;
use WebChemistry\FormSerializer\Event\ErrorEvent;
use WebChemistry\FormSerializer\Event\EventDispatcher;
use WebChemistry\FormSerializer\Event\FinalizeEvent;
use WebChemistry\FormSerializer\Event\SuccessEvent;

/**
 * beforeDenormalization -> (array -> object) -> afterDenormalization ->
 * (validate(object)) -> afterValidation ->
 * (persist(object)) -> success -> finalize
 */
final class FormSerializer
{

	private ?object $defaultObject = null;

	private bool $persistObject = true;

	private bool $validateObject = true;

	private NormalizationContextFactoryInterface $normalizationContextFactory;

	private DenormalizationContextFactory $denormalizationContextFactory;

	private EventDispatcher $eventDispatcher;

	/** @var string[] */
	private array $groups = [];

	/** @var mixed[] */
	private array $extraValues = [];

	public function __construct(
		private Form $form,
		private string $className,
		private Serializer $serializer,
		private ?EntityManagerInterface $em = null,
		private ?ValidatorInterface $validator = null,
	)
	{
		$this->eventDispatcher = new EventDispatcher();
		$this->normalizationContextFactory = new NormalizationContextFactory($this);
		$this->denormalizationContextFactory = new DenormalizationContextFactory($this);

		$this->prepare();
	}

	public function setClassName(string $className): void
	{
		$this->className = $className;
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

	public function getNormalizationContextFactory(): NormalizationContextFactoryInterface
	{
		return $this->normalizationContextFactory;
	}

	public function getDenormalizationContextFactory(): DenormalizationContextFactory
	{
		return $this->denormalizationContextFactory;
	}

	public function getForm(): Form
	{
		return $this->form;
	}

	public function getClassName(): string
	{
		return $this->className;
	}

	public function getEventDispatcher(): EventDispatcher
	{
		return $this->eventDispatcher;
	}

	public function setDefaults(array $defaults): self
	{
		$this->form->setDefaults($defaults);

		return $this;
	}

	public function setDefaultObject(?object $object): self
	{
		$this->defaultObject = $object;

		$this->applyDefaultObject(false);

		return $this;
	}

	public function getDefaultObject(): ?object
	{
		return $this->defaultObject;
	}

	public function hasDefaultObject(): bool
	{
		return (bool) $this->defaultObject;
	}

	public function addExtraValue(string|int $index, mixed $value): self
	{
		$this->extraValues[$index] = $value;

		return $this;
	}

	public function applyDefaultObject(bool $needObject = true): self
	{
		if (!$this->defaultObject) {
			if ($needObject) {
				throw new LogicException('Default object not set');
			}

			return $this;
		}

		if (!$this->defaultObject) {
			return $this;
		}

		$values = $this->serializer->normalize(
			$this->defaultObject,
			null,
			$this->normalizationContextFactory->create()
		);

		$this->eventDispatcher->dispatch($event = new AfterNormalizationEvent($this->defaultObject, $values, $this));

		$this->form->setDefaults($event->getValues());

		return $this;
	}

	private function prepare(): void
	{
		$this->form->onSuccess[] = function (Form $form, array $values): void {
			// extra values
			foreach ($this->extraValues as $index => $value) {
				if (array_key_exists($index, $values)) {
					throw new LogicException(
						sprintf('Form value with index %s already exists, please change extra value index name', $index)
					);
				}

				$values[$index] = $value;
			}

			// before denormalize
			$this->eventDispatcher->dispatch($event = new BeforeDenormalizationEvent($values, $form, $this));

			// denormalize
			$object = $this->serializer->denormalize(
				$event->getValues(),
				$this->className,
				null,
				$this->denormalizationContextFactory->create()
			);

			// after denormalize
			$this->eventDispatcher->dispatch(new AfterDenormalizationEvent($object, $form, $this));

			// validate
			if ($this->validateObject && $this->validator) {
				$errors = $this->validator->validate($object, null, $this->groups ?: null);

				if ($errors->count()) {
					/** @var ConstraintViolation $error */
					foreach ($errors as $error) {
						$property = $error->getPropertyPath();

						if ($property && $component = $form->getComponent($property, false)) {
							assert($component instanceof BaseControl);

							$component->addError($error->getMessage());
						} else {
							$form->addError($error->getMessage());
						}
					}

					return;
				}
			}

			$this->eventDispatcher->dispatch(new AfterValidationEvent($object, $form, $this));

			// persist
			if ($this->persistObject && $this->em) {
				$this->em->persist($object);
				$this->em->flush();
			}

			// success
			$this->eventDispatcher->dispatch(new SuccessEvent($object, $form, $this));

			// finalize
			$this->eventDispatcher->dispatch(new FinalizeEvent($object, $form, $this));
		};

		$this->form->onError[] = function (): void {
			$this->eventDispatcher->dispatch(new ErrorEvent($this->form, $this));
		};
	}

	public function debug(bool $withError = false): self
	{
		$this->setPersistObject(false);
		$this->getEventDispatcher()->addError(
			fn (ErrorEvent $event) => Debugger::barDump($event->getForm()->getUnsafeValues(), 'Form serializer $values')
		);
		$this->getEventDispatcher()->addError(
			fn (ErrorEvent $event) => Debugger::barDump($event->getForm()->getErrors(), 'Form serializer $errors')
		);
		$this->getEventDispatcher()->addBeforeDenormalization(
			fn (BeforeDenormalizationEvent $event) => Debugger::barDump($event->getValues(), 'Form serializer $values')
		);
		$this->getEventDispatcher()->addAfterDenormalization(
			fn (AfterDenormalizationEvent $event) => Debugger::barDump($event->getObject(), 'Form serializer $entity')
		);

		if ($withError) {
			$this->getForm()->onValidate[] = function (): void {
				$this->getForm()->addError('Failed because of debug.');
			};
		}

		return $this;
	}

}
