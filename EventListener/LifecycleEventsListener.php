<?php

namespace W3C\LifecycleEventsBundle\EventListener;

use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\PersistentCollection;
use W3C\LifecycleEventsBundle\Annotation\Create;
use W3C\LifecycleEventsBundle\Annotation\Delete;
use W3C\LifecycleEventsBundle\Annotation\IgnoreClassUpdates;
use W3C\LifecycleEventsBundle\Annotation\Update;
use W3C\LifecycleEventsBundle\Services\LifecycleEventsDispatcher;

/**
 * Listen to Doctrine postPersist, postRemove and preUpdate to feed a LifecycleEventsDispatcher
 *
 * @author Jean-Guilhem Rouel <jean-gui@w3.org>
 */
class LifecycleEventsListener
{
    /**
     * Events dispatcher
     *
     * @var LifecycleEventsDispatcher
     */
    private $dispatcher;

    /**
     * @var Reader
     */
    private $reader;

    /**
     * Constructs a new instance
     *
     * @param LifecycleEventsDispatcher $dispatcher the dispatcher to feed
     * @param Reader $reader
     */
    public function __construct(LifecycleEventsDispatcher $dispatcher, Reader $reader)
    {
        $this->dispatcher = $dispatcher;
        $this->reader     = $reader;
    }

    /**
     * Called upon receiving postPersist events
     *
     * @param LifecycleEventArgs $args event to feed the dispatcher with
     */
    public function postPersist(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        $class  = ClassUtils::getRealClass(get_class($entity));
        /** @var Create $annotation */
        $annotation = $this->getAnnotation($class, Create::class);
        if ($annotation) {
            $this->dispatcher->addCreation($annotation, $args);
        }

        $classMetadata = $args->getEntityManager()->getClassMetadata($class);
        foreach ($classMetadata->getAssociationMappings() as $property => $associationMapping) {
            if (!$classMetadata->isAssociationInverseSide($property)) {
                if ($classMetadata->isSingleValuedAssociation($property)) {
                    $inverse = $classMetadata->reflFields[$property]->getValue($entity);
                    $change  = ['old' => null, 'new' => $inverse];
                    $this->propertyUpdateInverse($args->getEntityManager(), $class, $property, $change, $entity);
                } elseif ($classMetadata->isCollectionValuedAssociation($property)) {
                    $inverse = $classMetadata->reflFields[$property]->getValue($entity);
                    if ($inverse) {
                        $change = ['deleted' => [], 'inserted' => $inverse->toArray()];
                        $this->collectionUpdateInverse($args->getEntityManager(), $class, $property, $change, $entity);
                    }
                }
            }
        }
    }

    public function preSoftDelete(LifecycleEventArgs $args)
    {
        $this->preRemove($args);
    }

    /**
     * Called upon receiving preRemove events. Better than postRemove as we still have information about associated
     * objects
     *
     * @param LifecycleEventArgs $args event to feed the dispatcher with
     */
    public function preRemove(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        $class  = ClassUtils::getRealClass(get_class($entity));

        /** @var Delete $annotation */
        $annotation = $this->getAnnotation($class, Delete::class);
        if ($annotation) {
            $this->dispatcher->addDeletion($annotation, $args);
        }

        $classMetadata = $args->getEntityManager()->getClassMetadata($class);
        foreach ($classMetadata->getAssociationMappings() as $property => $associationMapping) {
            if (!$classMetadata->isAssociationInverseSide($property)) {
                if ($classMetadata->isSingleValuedAssociation($property)) {
                    $inverse = $classMetadata->reflFields[$property]->getValue($entity);
                    $change  = ['old' => $inverse, 'new' => null];
                    $this->propertyUpdateInverse($args->getEntityManager(), $class, $property, $change, $entity);
                } elseif ($classMetadata->isCollectionValuedAssociation($property)) {
                    $inverse = $classMetadata->reflFields[$property]->getValue($entity);
                    if ($inverse) {
                        $change = ['deleted' => $inverse->toArray(), 'inserted' => []];
                        $this->collectionUpdateInverse($args->getEntityManager(), $class, $property, $change, $entity);
                    }
                }
            }
        }
    }

    /**
     * Called upon receiving preUpdate events
     *
     * @param PreUpdateEventArgs $args event to feed the dispatcher with
     */
    public function preUpdate(PreUpdateEventArgs $args)
    {
        $entity = $args->getEntity();
        $class  = ClassUtils::getRealClass(get_class($entity));

        /** @var Update $annotation */
        $annotation = $this->getAnnotation($class, Update::class);

        // Build properties and collections changes, also take care of inverse side
        $changeSet = $this->buildChangeSet($args, $entity);
        $collectionChanges = $annotation->monitor_collections ? $this->buildCollectionChanges($args, $entity) : [];

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
     * @param mixed $entity
     *
     * @return array
     */
    private function buildCollectionChanges(PreUpdateEventArgs $args, $entity)
    {
        $realClass          = ClassUtils::getRealClass(get_class($entity));
        $classMetadata      = $args->getEntityManager()->getClassMetadata($realClass);
        $collectionsChanges = [];

        /** @var PersistentCollection $u */
        foreach ($args->getEntityManager()->getUnitOfWork()->getScheduledCollectionUpdates() as $u) {
            $property = $u->getMapping()['fieldName'];

            // Make sure $u and the field belong to the entity we are working on
            if ($u->getOwner() !== $entity) {
                continue;
            }

            $ignoreAnnotation = $this->getIgnoreAnnotation($classMetadata, $property);

            $change = [
                'deleted'  => $u->getDeleteDiff(),
                'inserted' => $u->getInsertDiff()
            ];
            if (!$ignoreAnnotation) {
                $collectionsChanges[$property] = $change;
            }

            $this->collectionUpdateInverse($args->getEntityManager(), $realClass, $property, $change, $entity);
        }
        return $collectionsChanges;
    }

    /**
     * Return an array of changes to properties (not including collections) ignoring those marked with @IgnoreclassUpdates
     *
     * @param PreUpdateEventArgs $args
     * @param mixed $entity
     *
     * @return array
     */
    private function buildChangeSet(PreUpdateEventArgs $args, $entity)
    {
        $realClass     = ClassUtils::getRealClass(get_class($entity));
        $classMetadata = $args->getEntityManager()->getClassMetadata($realClass);
        $changes       = [];
        foreach (array_keys($args->getEntityChangeSet()) as $property) {
            $ignoreAnnotation = $this->getIgnoreAnnotation($classMetadata, $property);
            $change = ['old' => $args->getOldValue($property), 'new' => $args->getNewValue($property)];
            if (!$ignoreAnnotation) {
                $changes[$property] = $change;
            }

            if ($classMetadata->hasAssociation($property)) {
                $this->propertyUpdateInverse($args->getEntityManager(), $realClass, $property, $change, $entity);
            }
        }
        return $changes;
    }

    /**
     * @param ClassMetadata $classMetadata
     * @param string $property
     *
     * @return IgnoreClassUpdates
     * @throws \ReflectionException
     */
    private function getIgnoreAnnotation(ClassMetadata $classMetadata, $property)
    {
        $reflProperty = $classMetadata->getReflectionProperty($property);

        if ($reflProperty) {
            /** @var IgnoreClassUpdates $ignoreAnnotation */
            $ignoreAnnotation = $this->reader->getPropertyAnnotation(
                $reflProperty,
                IgnoreClassUpdates::class
            );
            return $ignoreAnnotation;
        }

        throw new \ReflectionException(
            $classMetadata->getName() . '.' . $property . ' not found. Could this be a private field of a parent class?'
        );
    }

    /**
     * @param string $class
     * @param string $annotationClass
     *
     * @return object
     */
    private function getAnnotation($class, $annotationClass)
    {
        $annotation = $this->reader->getClassAnnotation(
            new \ReflectionClass($class),
            $annotationClass
        );
        return $annotation;
    }

    /**
     * @param EntityManagerInterface $em
     * @param $class
     * @param $property
     * @param $change
     * @param $entity
     */
    private function collectionUpdateInverse(EntityManagerInterface $em, $class, $property, $change, $entity)
    {
        $classMetadata = $em->getClassMetadata($class);

        // it is indeed an association with a potential inverse side
        if ($classMetadata->hasAssociation($property)) {
            $mapping = $classMetadata->getAssociationMapping($property);
            /** @var Update $targetAnnotation */
            $targetAnnotation = $this->getAnnotation($mapping['targetEntity'], Update::class);

            // If there is a monitored inverse side (multi-valued since it can only be a many-to-many assoc)
            // we need to add updates to both former and new owners for each value
            if (
                $targetAnnotation && $targetAnnotation->monitor_owning &&
                isset($mapping['inversedBy']) && !$this->getIgnoreAnnotation($classMetadata, $mapping['inversedBy'])
            ) {
                foreach ($change['deleted'] as $deletion) {
                    $em->initializeObject($deletion);
                    $this->dispatcher->addUpdate(
                        $targetAnnotation,
                        $deletion,
                        [],
                        [$mapping['inversedBy'] => ['deleted' => [$entity], 'inserted' => []]]
                    );
                }

                foreach ($change['inserted'] as $insertion) {
                    $em->initializeObject($insertion);
                    $this->dispatcher->addUpdate(
                        $targetAnnotation,
                        $insertion,
                        [],
                        [$mapping['inversedBy'] => ['deleted' => [], 'inserted' => [$entity]]]
                    );
                }
            }
        }
    }

    /**
     * @param EntityManagerInterface $em
     * @param $class
     * @param $property
     * @param $change
     * @param $entity
     */
    private function propertyUpdateInverse(EntityManagerInterface $em, $class, $property, $change, $entity)
    {
        $classMetadata = $em->getClassMetadata($class);

        $mapping = $classMetadata->getAssociationMapping($property);

        /** @var Update $targetAnnotation */
        $targetAnnotation = $this->getAnnotation($mapping['targetEntity'], Update::class);

        // If there is a monitored inverse side, we need to add an update to both former and new owners
        if (
            $targetAnnotation && $targetAnnotation->monitor_owning
            && isset($mapping['inversedBy']) && !$this->getIgnoreAnnotation($classMetadata, $mapping['inversedBy'])
        ) {
            $inverseMetadata = $em->getClassMetadata($mapping['targetEntity']);

            // Inverse side is also single-valued (one-to-one)
            if ($inverseMetadata->isSingleValuedAssociation($mapping['inversedBy'])) {
                if (isset($change['old'])) {
                    $em->initializeObject($change['old']);
                    $this->dispatcher->addUpdate(
                        $targetAnnotation,
                        $change['old'],
                        [$mapping['inversedBy'] => ['old' => $entity, 'new' => null]],
                        []
                    );
                }
                if (isset($change['new'])) {
                    $em->initializeObject($change['new']);
                    $this->dispatcher->addUpdate(
                        $targetAnnotation,
                        $change['new'],
                        [$mapping['inversedBy'] => ['old' => null, 'new' => $entity]],
                        []
                    );
                }
            } // Inverse side is multi-valued (one-to-many)
            elseif ($inverseMetadata->isCollectionValuedAssociation($mapping['inversedBy'])) {
                if (isset($change['old']) && $change['old']) {
                    $em->initializeObject($change['old']);
                    $this->dispatcher->addUpdate(
                        $targetAnnotation,
                        $change['old'],
                        [],
                        [$mapping['inversedBy'] => ['deleted' => [$entity], 'inserted' => []]]
                    );
                }
                if (isset($change['new']) && $change['new']) {
                    $em->initializeObject($change['new']);
                    $this->dispatcher->addUpdate(
                        $targetAnnotation,
                        $change['new'],
                        [],
                        [$mapping['inversedBy'] => ['deleted' => [], 'inserted' => [$entity]]]
                    );
                }
            }
        }
    }
}
