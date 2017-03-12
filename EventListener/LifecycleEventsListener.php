<?php

namespace W3C\LifecycleEventsBundle\EventListener;

use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Util\ClassUtils;
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
     * Events dispatcher.
     *
     * @var LifecycleEventsDispatcher
     */
    private $dispatcher;

    /**
     * @var Reader
     */
    private $reader;

    /**
     * Constructs a new instance.
     *
     * @param LifecycleEventsDispatcher $dispatcher the dispatcher to feed
     * @param Reader                    $reader
     */
    public function __construct(LifecycleEventsDispatcher $dispatcher, Reader $reader)
    {
        $this->dispatcher = $dispatcher;
        $this->reader = $reader;
    }

    /**
     * Called upon receiving postPersist events.
     *
     * @param LifecycleEventArgs $args event to feed the dispatcher with
     */
    public function postPersist(LifecycleEventArgs $args)
    {
        $class = ClassUtils::getRealClass(get_class($args->getEntity()));
        /** @var Create $annotation */
        $annotation = $this->getAnnotation($class, Create::class);
        if ($annotation) {
            $this->dispatcher->addCreation($annotation, $args);
        }
    }

    /**
     * Called upon receiving postRemove events.
     *
     * @param LifecycleEventArgs $args event to feed the dispatcher with
     */
    public function postRemove(LifecycleEventArgs $args)
    {
        $class = ClassUtils::getRealClass(get_class($args->getEntity()));
        /** @var Delete $annotation */
        $annotation = $this->getAnnotation($class, Delete::class);
        if ($annotation) {
            $this->dispatcher->addDeletion($annotation, $args);
        }
    }

    public function postSoftDelete(LifecycleEventArgs $args)
    {
        $this->postRemove($args);
    }

    /**
     * Called upon receiving preUpdate events.
     *
     * @param PreUpdateEventArgs $args event to feed the dispatcher with
     */
    public function preUpdate(PreUpdateEventArgs $args)
    {
        $entity = $args->getEntity();
        $class = ClassUtils::getRealClass(get_class($entity));

        /** @var Update $annotation */
        $annotation = $this->getAnnotation($class, Update::class);
        if ($annotation) {
            $this->dispatcher->addUpdate(
                $annotation,
                $entity,
                $this->buildChangeSet($args, $entity),
                $annotation->monitor_collections ? $this->buildCollectionChanges($args, $entity) : null
            );
        }
    }

    /**
     * Return an array of collection changes belonging to $entity ignoring those marked with  @IgnoreclassUpdates.
     *
     * @param PreUpdateEventArgs $args
     * @param mixed              $entity
     *
     * @return array
     */
    private function buildCollectionChanges(PreUpdateEventArgs $args, $entity)
    {
        $realClass = ClassUtils::getRealClass(get_class($entity));
        $classMetadata = $args->getEntityManager()->getClassMetadata($realClass);
        $collectionsChanges = null;

        /** @var PersistentCollection $u */
        foreach ($args->getEntityManager()->getUnitOfWork()->getScheduledCollectionUpdates() as $u) {
            $property = $u->getMapping()['fieldName'];

            // Make sure $u and the field belong to the entity we are working on
            if ($u->getOwner() !== $entity) {
                continue;
            }

            $ignoreAnnotation = $this->getIgnoreAnnotation($classMetadata, $property);

            if (!$ignoreAnnotation) {
                $collectionsChanges[$property] = [
                    'deleted' => $u->getDeleteDiff(),
                    'inserted' => $u->getInsertDiff(),
                ];
            }
        }

        return $collectionsChanges;
    }

    /**
     * Return an array of changes to properties (not including collections) ignoring those marked with @IgnoreclassUpdates.
     *
     * @param PreUpdateEventArgs $args
     * @param mixed              $entity
     *
     * @return array
     */
    private function buildChangeSet(PreUpdateEventArgs $args, $entity)
    {
        $realClass = ClassUtils::getRealClass(get_class($entity));
        $classMetadata = $args->getEntityManager()->getClassMetadata($realClass);
        $changes = [];

        foreach (array_keys($args->getEntityChangeSet()) as $property) {
            $ignoreAnnotation = $this->getIgnoreAnnotation($classMetadata, $property);

            if (!$ignoreAnnotation) {
                $changes[$property] = ['old' => $args->getOldValue($property), 'new' => $args->getNewValue($property)];
            }
        }

        return $changes;
    }

    /**
     * @param ClassMetadata $realClass
     * @param string        $property
     *
     * @throws \ReflectionException
     *
     * @return IgnoreClassUpdates
     */
    private function getIgnoreAnnotation(ClassMetadata $classMetadata, $property)
    {
        try {
            /** @var IgnoreClassUpdates $ignoreAnnotation */
            $ignoreAnnotation = $this->reader->getPropertyAnnotation(
                $classMetadata->getReflectionProperty($property),
                IgnoreClassUpdates::class
            );

            return $ignoreAnnotation;
        } catch (\ReflectionException $e) {
            throw new \ReflectionException(
                $e->getMessage().'. Could this be a private field of a parent class?',
                $e->getCode(),
                $e
            );
        }
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
}
