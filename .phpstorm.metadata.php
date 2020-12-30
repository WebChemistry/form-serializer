<?php declare(strict_types = 1);

namespace PHPSTORM_META;

use WebChemistry\FormSerializer\Event\AfterDenormalizeEvent;
use WebChemistry\FormSerializer\Event\SuccessEvent;

override(SuccessEvent::getObject(0), map([
	'' => '@',
]));
override(AfterDenormalizeEvent::getObject(0), map([
	'' => '@',
]));
