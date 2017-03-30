<?php

namespace W3C\LifecycleEventsBundle\EventListener;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\PersistentCollection;
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
    /**
     * Events dispatcher
     *
     * @var LifecycleEventsDispatcher
     */
    private $dispatcher;

    /**
     * @var AnnotationGetter
     */
    private $annotationGetter;

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
     */
    public function postPersist(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        $class  = ClassUtils::getRealClass(get_class($entity));
        /** @var Create $annotation */
        $annotation = $this->annotationGetter->getAnnotation($class, Create::class);
        if ($annotation) {
            $this->dispatcher->addCreation($annotation, $args);
        }

        $classMetadata = $args->getEntityManager()->getClassMetadata($class);
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
        $annotation = $this->annotationGetter->getAnnotation($class, Delete::class);
        if ($annotation) {
            $this->dispatcher->addDeletion($annotation, $args);
        }

        $classMetadata = $args->getEntityManager()->getClassMetadata($class);
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
     */
    public function preUpdate(PreUpdateEventArgs $args)
    {
        $entity = $args->getEntity();
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
     * @param $class
     * @param $property
     * @param $change
     * @param $entity
     */
    private function collectionUpdateInverse(LifecycleEventArgs $args, $class, $property, $change, $entity)
    {
        $em = $args->getEntityManager();
        $classMetadata = $em->getClassMetadata($class);

        // it is indeed an association with a potential inverse side
        if ($classMetadata->hasAssociation($property)) {
            $mapping = $classMetadata->getAssociationMapping($property);
            /** @var Update $targetAnnotation */
            $targetAnnotation = $this->annotationGetter->getAnnotation($mapping['targetEntity'], Update::class);
            $inverseMetadata = $em->getClassMetadata($mapping['targetEntity']);
            /** @var Change $targetChangeAnnotation */
            $targetChangeAnnotation = $this->annotationGetter->getPropertyAnnotation($inverseMetadata,
                $mapping['inversedBy'], Change::class);

            // Is there a class level monitored inverse field?
            $inverseMonitoredGlobal = $targetAnnotation && $targetAnnotation->monitor_owning &&
                isset($mapping['inversedBy']) &&
                !$this->annotationGetter->getPropertyAnnotation($inverseMetadata, $mapping['inversedBy'],
                    IgnoreClassUpdates::class);
            // Is there a field level monitored inverse field?
            $inverseMonitoredField = isset($mapping['inversedBy']) && $targetChangeAnnotation && $targetChangeAnnotation->monitor_owning;

            foreach ($change['deleted'] as $deletion) {
                $em->initializeObject($deletion);

                if ($inverseMonitoredGlobal) {
                    $this->dispatcher->addUpdate(
                        $targetAnnotation,
                        $deletion,
                        [],
                        [$mapping['inversedBy'] => ['deleted' => [$entity], 'inserted' => []]]
                    );
                }

                if ($inverseMonitoredField) {
                    $this->dispatcher->addCollectionChange(
                        $targetChangeAnnotation,
                        $deletion,
                        $mapping['inversedBy'],
                        [$entity],
                        []
                    );
                }
            }

            foreach ($change['inserted'] as $insertion) {
                $em->initializeObject($insertion);

                if ($inverseMonitoredGlobal) {
                    $this->dispatcher->addUpdate(
                        $targetAnnotation,
                        $insertion,
                        [],
                        [$mapping['inversedBy'] => ['deleted' => [], 'inserted' => [$entity]]]
                    );
                }

                if ($inverseMonitoredField) {
                    $this->dispatcher->addCollectionChange(
                        $targetChangeAnnotation,
                        $insertion,
                        $mapping['inversedBy'],
                        [],
                        [$entity]
                    );
                }
            }
        }
    }

    /**
     * @param LifecycleEventArgs $args
     * @param $class
     * @param $property
     * @param $change
     * @param $entity
     */
    private function propertyUpdateInverse(LifecycleEventArgs $args, $class, $property, $change, $entity)
    {
        $em = $args->getEntityManager();
        $classMetadata = $em->getClassMetadata($class);

        $mapping = $classMetadata->getAssociationMapping($property);

        $inverseMetadata = $em->getClassMetadata($mapping['targetEntity']);
        /** @var Update $targetAnnotation */
        $targetAnnotation = $this->annotationGetter->getAnnotation($mapping['targetEntity'], Update::class);
        /** @var Change $targetChangeAnnotation */
        $targetChangeAnnotation = $this->annotationGetter->getPropertyAnnotation($inverseMetadata,
            $mapping['inversedBy'], Change::class);


        // If there is a monitored inverse side, we need to add an update to both former and new owners
        $inverseMonitoredGlobal = $targetAnnotation && $targetAnnotation->monitor_owning
            && isset($mapping['inversedBy']) && !$this->annotationGetter->getPropertyAnnotation($inverseMetadata,
                $mapping['inversedBy'], IgnoreClassUpdates::class);

        $inverseMonitoredField = isset($mapping['inversedBy']) && $targetChangeAnnotation && $targetChangeAnnotation->monitor_owning;

        // Inverse side is also single-valued (one-to-one)
        if ($inverseMetadata->isSingleValuedAssociation($mapping['inversedBy'])) {
            if (isset($change['old'])) {
                $em->initializeObject($change['old']);

                if ($inverseMonitoredGlobal) {
                    $this->dispatcher->addUpdate(
                        $targetAnnotation,
                        $change['old'],
                        [$mapping['inversedBy'] => ['old' => $entity, 'new' => null]],
                        []
                    );
                }

                if ($inverseMonitoredField) {
                    $this->dispatcher->addPropertyChange(
                        $targetChangeAnnotation,
                        $change['old'],
                        $mapping['inversedBy'],
                        $entity,
                        null
                    );
                }
            }
            if (isset($change['new'])) {
                $em->initializeObject($change['new']);

                if ($inverseMonitoredGlobal) {
                    $this->dispatcher->addUpdate(
                        $targetAnnotation,
                        $change['new'],
                        [$mapping['inversedBy'] => ['old' => null, 'new' => $entity]],
                        []
                    );
                }

                if ($inverseMonitoredField) {
                    $this->dispatcher->addPropertyChange(
                        $targetChangeAnnotation,
                        $change['new'],
                        $mapping['inversedBy'],
                        null,
                        $entity
                    );
                }
            }
        } // Inverse side is multi-valued (one-to-many)
        elseif ($inverseMetadata->isCollectionValuedAssociation($mapping['inversedBy'])) {
            if (isset($change['old']) && $change['old']) {
                $em->initializeObject($change['old']);

                if ($inverseMonitoredGlobal) {
                    $this->dispatcher->addUpdate(
                        $targetAnnotation,
                        $change['old'],
                        [],
                        [$mapping['inversedBy'] => ['deleted' => [$entity], 'inserted' => []]]
                    );
                }

                if ($inverseMonitoredField) {
                    $this->dispatcher->addCollectionChange(
                        $targetChangeAnnotation,
                        $change['old'],
                        $mapping['inversedBy'],
                        [$entity],
                        []
                    );
                }
            }
            if (isset($change['new']) && $change['new']) {
                $em->initializeObject($change['new']);

                if ($inverseMonitoredGlobal) {
                    $this->dispatcher->addUpdate(
                        $targetAnnotation,
                        $change['new'],
                        [],
                        [$mapping['inversedBy'] => ['deleted' => [], 'inserted' => [$entity]]]
                    );
                }

                if ($inverseMonitoredField) {
                    $this->dispatcher->addCollectionChange(
                        $targetChangeAnnotation,
                        $change['new'],
                        $mapping['inversedBy'],
                        [],
                        [$entity]
                    );
                }
            }
        }
    }
}
