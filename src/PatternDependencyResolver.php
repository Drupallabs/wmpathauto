<?php

namespace Drupal\wmpathauto;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Utility\Token;
use Drupal\pathauto\AliasCleanerInterface;
use Drupal\pathauto\PathautoPatternInterface;
use Drupal\token\TokenEntityMapperInterface;

class PatternDependencyResolver implements PatternDependencyResolverInterface
{
    /** @var Token */
    protected $token;
    /** @var TokenEntityMapperInterface */
    protected $tokenEntityMapper;
    /** @var AliasCleanerInterface */
    protected $aliasCleaner;
    /** @var AliasBuilderManager */
    protected $aliasBuilderManager;
    /** @var PatternBuilderManager */
    protected $patternBuilderManager;
    /** @var PatternDependenciesManager */
    protected $patternDependenciesManager;
    /** @var PatternTokenDependenciesManager */
    protected $patternTokenDependenciesManager;

    public function __construct(
        Token $token,
        TokenEntityMapperInterface $tokenEntityMapper,
        AliasCleanerInterface $aliasCleaner,
        AliasBuilderManager $aliasBuilderManager,
        PatternBuilderManager $patternBuilderManager,
        PatternDependenciesManager $patternDependenciesManager,
        PatternTokenDependenciesManager $patternTokenDependenciesManager
    ) {
        $this->token = $token;
        $this->tokenEntityMapper = $tokenEntityMapper;
        $this->aliasCleaner = $aliasCleaner;
        $this->aliasBuilderManager = $aliasBuilderManager;
        $this->patternBuilderManager = $patternBuilderManager;
        $this->patternDependenciesManager = $patternDependenciesManager;
        $this->patternTokenDependenciesManager = $patternTokenDependenciesManager;
    }

    public function getDependencies(PathautoPatternInterface $pattern, EntityInterface $entity): PatternDependencyCollectionInterface
    {
        $dependencies = new PatternDependencyCollection;

        $this->addDependenciesFromTokens($dependencies, $pattern, $entity);
        $this->addDependenciesFromPlugins($dependencies, $pattern, $entity);

        return $dependencies;
    }

    protected function addDependenciesFromTokens(PatternDependencyCollectionInterface $dependencies, PathautoPatternInterface $pattern, EntityInterface $entity): void
    {
        $tokensByType = $this->token->scan($pattern->getPattern());
        $entityTokenType = $this->tokenEntityMapper->getTokenTypeForEntityType($entity->getEntityTypeId());
        $data = [$entityTokenType => $entity];

        if (isset($tokensByType[$entityTokenType])) {
            // unset($tokensByType[$entityTokenType]);
        }

        foreach ($tokensByType as $type => $tokens) {
            if (!$this->patternTokenDependenciesManager->hasDefinition($type)) {
                continue;
            }

            $this->patternTokenDependenciesManager
                ->createInstance($type)
                ->addDependencies($tokens, $data, $dependencies);
        }
    }

    protected function addDependenciesFromPlugins(PatternDependencyCollectionInterface $dependencies, PathautoPatternInterface $pattern, EntityInterface $entity): void
    {
        $managers = [
            $this->patternDependenciesManager,
            $this->patternBuilderManager,
            $this->aliasBuilderManager,
        ];

        foreach ($managers as $manager) {
            if (!$manager->hasDefinition($pattern->id())) {
                continue;
            }

            $builder = $manager->createInstance($pattern->id());

            if (!$builder instanceof PatternDependenciesInterface) {
                continue;
            }

            $builder->addDependencies($dependencies, $entity);
        }
    }
}
