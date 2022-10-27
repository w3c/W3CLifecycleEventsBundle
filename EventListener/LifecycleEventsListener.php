<?php

namespace W3C\LifecycleEventsBundle\EventListener;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\PersistentCollection;
use ReflectionException;
use W3C\LifecycleEventsBundle\Annotation\Change;
use W3C\LifecycleEventsBundle\Annotation\Create;
use W3C\LifecycleEventsBundle\Annotation\Delete;
use W3C\LifecycleEventsBundle\Annotation\IgnoreClassUpdates;
use W3C\LifecycleEventsBundle\Annotation\Update;
use W3C\LifecycleEventsBundle\Services\AnnotationGetter;
use W3C\LifecycleEventsBundle\Services\LifecycleEventsDispatcher;

/**
 * Listen to Doctrine postPersist, postRemove and preUpdate to feed a LifecycleEventsDispatcher
 *
 * @author Jean-Guilhem Rouel <jean-gui@w3.org>
 */
class LifecycleEventsListener
{
    private LifecycleEventsDispatcher $dispatcher;
    private AnnotationGetter $annotationGetter;

    /**
     * Constructs a new instance
     *
     * @param LifecycleEventsDispatcher $dispatcher the dispatcher to feed
     * @param AnnotationGetter $annotationGetter
     */
    public function __construct(LifecycleEventsDispatcher $dispatcher, AnnotationGetter $annotationGetter)
    {
        $this->dispatcher       = $dispatcher;
        $this->annotationGetter = $annotationGetter;
    }

    /**
     * Called upon receiving postPersist events
     *
     * @param LifecycleEventArgs $args event to feed the dispatcher with
     *
     * @throws MappingException
     * @throws ReflectionException
     */
    public function postPersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();
        $class  = ClassUtils::getRealClass(get_class($entity));
        /** @var Create $annotation */
        $annotation = $this->annotationGetter->getAnnotation($class, Create::class);
        if ($annotation) {
            $this->dispatcher->addCreation($annotation, $args);
        }

        $classMetadata = $args->getObjectManager()->getClassMetadata($class);
        foreach ($classMetadata->getAssociationMappings() as $property => $associationMapping) {
            if (!$classMetadata->isAssociationInverseSide($property)) {
                if ($classMetadata->isSingleValuedAssociation($property)) {
                    $inverse = $classMetadata->reflFields[$property]->getValue($entity);
                    $change  = ['old' => null, 'new' => $inverse];
                    $this->propertyUpdateInverse($args, $class, $property, $change, $entity);
                } elseif ($classMetadata->isCollectionValuedAssociation($property)) {
                    $inverse = $classMetadata->reflFields[$property]->getValue($entity);
                    if ($inverse) {
                        $change = ['deleted' => [], 'inserted' => $inverse->toArray()];
                        $this->collectionUpdateInverse($args, $class, $property, $change, $entity);
                    }
                }
            }
        }
    }

    /**
     * @param LifecycleEventArgs $args
     *
     * @return void
     * @throws MappingException
     * @throws ReflectionException
     */
    public function preSoftDelete(LifecycleEventArgs $args): void
    {
        $this->preRemove($args);
    }

    /**
     * Called upon receiving preRemove events. Better than postRemove as we still have information about associated
     * objects
     *
     * @param LifecycleEventArgs $args event to feed the dispatcher with
     *
     * @throws MappingException
     * @throws ReflectionException
     */
    public function preRemove(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();
        $class  = ClassUtils::getRealClass(get_class($entity));

        /** @var Delete $annotation */
        $annotation = $this->annotationGetter->getAnnotation($class, Delete::class);
        if ($annotation) {
            $this->dispatcher->addDeletion($annotation, $args);
        }

        $classMetadata = $args->getObjectManager()->getClassMetadata($class);
        foreach ($classMetadata->getAssociationMappings() as $property => $associationMapping) {
            if (!$classMetadata->isAssociationInverseSide($property)) {
                if ($classMetadata->isSingleValuedAssociation($property)) {
                    $inverse = $classMetadata->reflFields[$property]->getValue($entity);
                    $change  = ['old' => $inverse, 'new' => null];
                    $this->propertyUpdateInverse($args, $class, $property, $change, $entity);
                } elseif ($classMetadata->isCollectionValuedAssociation($property)) {
                    $inverse = $classMetadata->reflFields[$property]->getValue($entity);
                    if ($inverse) {
                        $change = ['deleted' => $inverse->toArray(), 'inserted' => []];
                        $this->collectionUpdateInverse($args, $class, $property, $change, $entity);
                    }
                }
            }
        }
    }

    /**
     * Called upon receiving preUpdate events
     *
     * @param PreUpdateEventArgs $args event to feed the dispatcher with
     *
     * @throws MappingException
     * @throws ReflectionException
     */
    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $entity = $args->getObject();
        $class  = ClassUtils::getRealClass(get_class($entity));

        /** @var Update $annotation */
        $annotation = $this->annotationGetter->getAnnotation($class, Update::class);

        // Build properties and collections changes, also take care of inverse side
        $changeSet = $this->buildChangeSet($args, $entity);

        $collectionChanges = $annotation && $annotation->monitor_collections ? $this->buildCollectionChanges($args, $entity) : [];

        if ($annotation) {
            // Add changes to the entity
            $this->dispatcher->addUpdate(
                $annotation,
                $entity,
                $changeSet,
                $collectionChanges
            );
        }
    }

    /**
     * Return an array of collection changes belonging to $entity ignoring those marked with  @IgnoreclassUpdates
     *
     * @param PreUpdateEventArgs $args
     * @param object             $entity
     *
     * @return array
     * @throws MappingException
     * @throws ReflectionException
     */
    private function buildCollectionChanges(PreUpdateEventArgs $args, object $entity): array
    {
        $realClass          = ClassUtils::getRealClass(get_class($entity));
        $classMetadata      = $args->getObjectManager()->getClassMetadata($realClass);
        $collectionsChanges = [];

        /** @var PersistentCollection $u */
        foreach ($args->getObjectManager()->getUnitOfWork()->getScheduledCollectionUpdates() as $u) {
            $property = $u->getMapping()['fieldName'];

            // Make sure $u and the field belong to the entity we are working on
            if ($u->getOwner() !== $entity) {
                continue;
            }

            $ignoreAnnotation = $this->annotationGetter->getPropertyAnnotation(
                $classMetadata,
                $property,
                IgnoreClassUpdates::class
            );
            $change = [
                'deleted'  => $u->getDeleteDiff(),
                'inserted' => $u->getInsertDiff()
            ];
            if (!$ignoreAnnotation) {
                $collectionsChanges[$property] = $change;
            }

            $this->collectionUpdateInverse($args, $realClass, $property, $change, $entity);
        }
        return $collectionsChanges;
    }

    /**
     * Return an array of changes to properties (not including collections) ignoring those marked with @IgnoreclassUpdates
     *
     * @param PreUpdateEventArgs $args
     * @param mixed              $entity
     *
     * @return array
     * @throws MappingException
     * @throws ReflectionException
     */
    private function buildChangeSet(PreUpdateEventArgs $args, $entity): array
    {
        $realClass     = ClassUtils::getRealClass(get_class($entity));
        $classMetadata = $args->getObjectManager()->getClassMetadata($realClass);
        $changes       = [];
        foreach (array_keys($args->getEntityChangeSet()) as $property) {
            $ignoreAnnotation = $this->annotationGetter->getPropertyAnnotation(
                $classMetadata,
                $property,
                IgnoreClassUpdates::class
            );
            $change = ['old' => $args->getOldValue($property), 'new' => $args->getNewValue($property)];
            if (!$ignoreAnnotation) {
                $changes[$property] = $change;
            }

            if ($classMetadata->hasAssociation($property)) {
                $this->propertyUpdateInverse($args, $realClass, $property, $change, $entity);
            }
        }
        return $changes;
    }

    /**
     * @param LifecycleEventArgs $args
     * @param string             $class
     * @param string             $property
     * @param array              $change
     * @param object             $entity
     *
     * @throws MappingException
     * @throws ReflectionException
     */
    private function collectionUpdateInverse(
        LifecycleEventArgs $args,
        string $class,
        string $property,
        array $change,
        object $entity
    ): void {
        $em = $args->getObjectManager();
        $classMetadata = $em->getClassMetadata($class);

        // it is indeed an association with a potential inverse side
        if ($classMetadata->hasAssociation($property)) {
            $mapping = $classMetadata->getAssociationMapping($property);

            foreach ($change['deleted'] as $deletion) {
                $this->updateDeletedInverse($deletion, $entity, $args, $mapping);
            }

            foreach ($change['inserted'] as $insertion) {
                $this->updateInsertedInverse($insertion, $entity, $args, $mapping);
            }
        }
    }

    /**
     * @param LifecycleEventArgs $args
     * @param string             $class
     * @param string             $property
     * @param array              $change
     * @param object             $entity
     *
     * @throws MappingException
     * @throws ReflectionException
     */
    private function propertyUpdateInverse(
        LifecycleEventArgs $args,
        string $class,
        string $property,
        array $change,
        object $entity
    ): void
    {
        $em = $args->getObjectManager();
        $classMetadata = $em->getClassMetadata($class);

        $mapping = $classMetadata->getAssociationMapping($property);

        if (isset($change['new']) && $change['new']) {
            $newInverseMetadata = $em->getClassMetadata(ClassUtils::getRealClass(get_class($change['new'])));
        } else {
            $newInverseMetadata = $em->getClassMetadata($mapping['targetEntity']);
        }

        if (isset($change['old']) && $change['old']) {
            $oldInverseMetadata = $em->getClassMetadata(ClassUtils::getRealClass(get_class($change['old'])));
        } else {
            $oldInverseMetadata = $em->getClassMetadata($mapping['targetEntity']);
        }

        // Inverse side should always be similar for old and new entities, but in case that's not the case (because of
        // some weird inheritance, we consider old and new metadata

        // Old Inverse side is also single-valued (one-to-one)
        if ($oldInverseMetadata->isSingleValuedAssociation($mapping['inversedBy'])) {
            $this->updateOldInverse($change['old'], $entity, $args,$mapping);
        } // Old Inverse side is multi-valued (one-to-many)
        elseif ($oldInverseMetadata->isCollectionValuedAssociation($mapping['inversedBy'])) {
            $this->updateDeletedInverse($change['old'], $entity, $args,$mapping);
        }

        // New Inverse side is also single-valued (one-to-one)
        if ($newInverseMetadata->isSingleValuedAssociation($mapping['inversedBy'])) {
            $this->updateNewInverse($change['new'], $entity, $args, $mapping);
        } // New Inverse side is multi-valued (one-to-many)
        elseif ($newInverseMetadata->isCollectionValuedAssociation($mapping['inversedBy'])) {
            $this->updateInsertedInverse($change['new'], $entity, $args, $mapping);
        }
    }

    /**
     * @param object|null        $oldEntity
     * @param object             $owningEntity
     * @param LifecycleEventArgs $args
     * @param array              $mapping
     *
     * @throws ReflectionException
     */
    private function updateOldInverse(
        ?object $oldEntity,
        object $owningEntity,
        LifecycleEventArgs $args,
        array $mapping
    ): void {
        $inverseField = $mapping['inversedBy'] ?? null;
        if ($inverseField && $oldEntity) {
            $em = $args->getObjectManager();

            $oldClass = ClassUtils::getRealClass(get_class($oldEntity));
            $inverseMetadata = $em->getClassMetadata($oldClass);

            /** @var Update $targetAnnotation */
            $targetAnnotation = $this->annotationGetter->getAnnotation($oldClass, Update::class);
            /** @var Change $targetChangeAnnotation */
            $targetChangeAnnotation = $this->annotationGetter->getPropertyAnnotation($inverseMetadata,
                $inverseField, Change::class);

            $inverseMonitoredGlobal = $targetAnnotation && $targetAnnotation->monitor_owning
                && !$this->annotationGetter->getPropertyAnnotation($inverseMetadata,
                    $inverseField, IgnoreClassUpdates::class);

            $inverseMonitoredField = $targetChangeAnnotation && $targetChangeAnnotation->monitor_owning;

            $em->initializeObject($oldEntity);

            if ($inverseMonitoredGlobal) {
                $this->dispatcher->addUpdate(
                    $targetAnnotation,
                    $oldEntity,
                    [$inverseField => ['old' => $owningEntity, 'new' => null]],
                    []
                );
            }

            if ($inverseMonitoredField) {
                $this->dispatcher->addPropertyChange(
                    $targetChangeAnnotation,
                    $oldEntity,
                    $mapping['inversedBy'],
                    $owningEntity
                );
            }
        }
    }

    /**
     * @param object|null        $newEntity
     * @param object             $owningEntity
     * @param LifecycleEventArgs $args
     * @param array              $mapping
     *
     * @return void
     * @throws ReflectionException
     */
    private function updateNewInverse(
        ?object $newEntity,
        object $owningEntity,
        LifecycleEventArgs $args,
        array $mapping
    ): void {
        $inverseField = $mapping['inversedBy'] ?? null;
        if ($inverseField && $newEntity) {
            $em = $args->getObjectManager();

            $newClass        = ClassUtils::getRealClass(get_class($newEntity));
            $inverseMetadata = $em->getClassMetadata($newClass);

            /** @var Update $targetAnnotation */
            $targetAnnotation = $this->annotationGetter->getAnnotation($newClass, Update::class);
            /** @var Change $targetChangeAnnotation */
            $targetChangeAnnotation = $this->annotationGetter->getPropertyAnnotation($inverseMetadata,
                $inverseField, Change::class);

            $inverseMonitoredGlobal = $targetAnnotation && $targetAnnotation->monitor_owning
                && !$this->annotationGetter->getPropertyAnnotation($inverseMetadata,
                    $inverseField, IgnoreClassUpdates::class);

            $inverseMonitoredField = $targetChangeAnnotation && $targetChangeAnnotation->monitor_owning;

            $em->initializeObject($newEntity);

            if ($inverseMonitoredGlobal) {
                $this->dispatcher->addUpdate(
                    $targetAnnotation,
                    $newEntity,
                    [$inverseField => ['old' => null, 'new' => $owningEntity]],
                    []
                );
            }

            if ($inverseMonitoredField) {
                $this->dispatcher->addPropertyChange(
                    $targetChangeAnnotation,
                    $newEntity,
                    $mapping['inversedBy'],
                    null,
                    $owningEntity
                );
            }
        }
    }

    /**
     * @param object|null        $deletedEntity
     * @param object             $owningEntity
     * @param LifecycleEventArgs $args
     * @param array              $mapping
     *
     * @return void
     * @throws ReflectionException
     */
    private function updateDeletedInverse(
        ?object $deletedEntity,
        object $owningEntity,
        LifecycleEventArgs $args,
        array $mapping
    ): void {
        $inverseField = $mapping['inversedBy'] ?? null;
        if ($inverseField && $deletedEntity) {
            $em = $args->getObjectManager();

            $deletedClass    = ClassUtils::getRealClass(get_class($deletedEntity));
            $inverseMetadata = $em->getClassMetadata($deletedClass);

            /** @var Update $targetAnnotation */
            $targetAnnotation = $this->annotationGetter->getAnnotation($deletedClass, Update::class);
            /** @var Change $targetChangeAnnotation */
            $targetChangeAnnotation = $this->annotationGetter->getPropertyAnnotation($inverseMetadata,
                $inverseField, Change::class);

            $inverseMonitoredGlobal = $targetAnnotation && $targetAnnotation->monitor_owning
                && !$this->annotationGetter->getPropertyAnnotation($inverseMetadata,
                    $inverseField, IgnoreClassUpdates::class);

            $inverseMonitoredField = $targetChangeAnnotation && $targetChangeAnnotation->monitor_owning;

            $em->initializeObject($deletedEntity);

            if ($inverseMonitoredGlobal) {
                $this->dispatcher->addUpdate(
                    $targetAnnotation,
                    $deletedEntity,
                    [],
                    [$mapping['inversedBy'] => ['deleted' => [$owningEntity], 'inserted' => []]]
                );
            }

            if ($inverseMonitoredField) {
                $this->dispatcher->addCollectionChange(
                    $targetChangeAnnotation,
                    $deletedEntity,
                    $mapping['inversedBy'],
                    [$owningEntity],
                    []
                );
            }
        }
    }

    /**
     * @param object|null        $insertedEntity
     * @param object             $owningEntity
     * @param LifecycleEventArgs $args
     * @param array              $mapping
     *
     * @return void
     * @throws ReflectionException
     */
    private function updateInsertedInverse(
        ?object $insertedEntity,
        object $owningEntity,
        LifecycleEventArgs $args,
        array $mapping
    ): void {
        $inverseField = $mapping['inversedBy'] ?? null;
        if ($inverseField && $insertedEntity) {
            $em = $args->getObjectManager();

            $deletedClass    = ClassUtils::getRealClass(get_class($insertedEntity));
            $inverseMetadata = $em->getClassMetadata($deletedClass);

            /** @var Update $targetAnnotation */
            $targetAnnotation = $this->annotationGetter->getAnnotation($deletedClass, Update::class);
            /** @var Change $targetChangeAnnotation */
            $targetChangeAnnotation = $this->annotationGetter->getPropertyAnnotation($inverseMetadata,
                $inverseField, Change::class);

            $inverseMonitoredGlobal = $targetAnnotation && $targetAnnotation->monitor_owning
                && !$this->annotationGetter->getPropertyAnnotation($inverseMetadata,
                    $inverseField, IgnoreClassUpdates::class);

            $inverseMonitoredField = $targetChangeAnnotation && $targetChangeAnnotation->monitor_owning;

            $em->initializeObject($insertedEntity);

            if ($inverseMonitoredGlobal) {
                $this->dispatcher->addUpdate(
                    $targetAnnotation,
                    $insertedEntity,
                    [],
                    [$mapping['inversedBy'] => ['deleted' => [], 'inserted' => [$owningEntity]]]
                );
            }

            if ($inverseMonitoredField) {
                $this->dispatcher->addCollectionChange(
                    $targetChangeAnnotation,
                    $insertedEntity,
                    $mapping['inversedBy'],
                    [],
                    [$owningEntity]
                );
            }
        }
    }
}
