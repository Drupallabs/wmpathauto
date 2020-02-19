<?php

namespace Drupal\wmpathauto;

use Drupal\Core\Entity\EntityInterface;

interface EntityAliasDependencyInterface
{
    public const TYPE_ENTITY = 'entity';
    public const TYPE_CONFIG = 'config';
    public const TYPE_PATH_ALIAS = 'pid';

    public function getDependentEntity(): ?EntityInterface;

    public function getDependencyType(): string;

    public function getDependencyValue(): string;
}
