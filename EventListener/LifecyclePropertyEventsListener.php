<?php

namespace W3C\LifecycleEventsBundle\EventListener;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\PersistentCollection;
use ReflectionException;
use W3C\LifecycleEventsBundle\Attribute\Change;
use W3C\LifecycleEventsBundle\Services\AttributeGetter;
use W3C\LifecycleEventsBundle\Services\LifecycleEventsDispatcher;

/**
 * Listen to Doctrine preUpdate to feed a LifecycleEventsDispatcher
 *
 * @author Jean-Guilhem Rouel <jean-gui@w3.org>
 */
class LifecyclePropertyEventsListener
{
    private LifecycleEventsDispatcher $dispatcher;
    private AttributeGetter $attributeGetter;

    /**
     * Constructs a new instance
     *
     * @param LifecycleEventsDispatcher $dispatcher the dispatcher to feed
     * @param AttributeGetter $attributeGetter
     */
    public function __construct(LifecycleEventsDispatcher $dispatcher, AttributeGetter $attributeGetter)
    {
        $this->dispatcher       = $dispatcher;
        $this->attributeGetter = $attributeGetter;
    }

    /**
     * @param PreUpdateEventArgs $args
     *
     * @return void
     * @throws ReflectionException
     */
    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $this->addPropertyChanges($args);
        $this->addCollectionChanges($args);
    }

    /**
     * @param PreUpdateEventArgs $args
     *
     * @throws ReflectionException
     */
    private function addPropertyChanges(PreUpdateEventArgs $args): void
    {
        $entity        = $args->getObject();
        $realClass     = ClassUtils::getRealClass(get_class($entity));
        $classMetadata = $args->getObjectManager()->getClassMetadata($realClass);

        foreach ($args->getEntityChangeSet() as $property => $change) {
            /** @var Change $attribute */
            $attribute = $this->attributeGetter->getPropertyAttribute($classMetadata, $property, Change::class);

            if ($attribute) {
                $this->dispatcher->addPropertyChange(
                    $attribute,
                    $args->getObject(),
                    $property,
                    $change[0],
                    $change[1]
                );
            }
        }
    }

    /**
     * @param PreUpdateEventArgs $args
     *
     * @throws ReflectionException
     */
    private function addCollectionChanges(PreUpdateEventArgs $args): void
    {
        $entity        = $args->getObject();
        $realClass     = ClassUtils::getRealClass(get_class($entity));
        $classMetadata = $args->getObjectManager()->getClassMetadata($realClass);

        /** @var PersistentCollection $update */
        foreach ($args->getObjectManager()->getUnitOfWork()->getScheduledCollectionUpdates() as $update) {
            if ($update->getOwner() !== $entity) {
                continue;
            }

            $property   = $update->getMapping()['fieldName'];
            /** @var Change $attribute */
            $attribute = $this->attributeGetter->getPropertyAttribute($classMetadata, $property, Change::class);

            // Make sure $u belongs to the entity we are working on
            if (!$attribute) {
                continue;
            }

            $this->dispatcher->addCollectionChange(
                $attribute,
                $args->getObject(),
                $property,
                $update->getDeleteDiff(),
                $update->getInsertDiff()
            );
        }
    }
}
