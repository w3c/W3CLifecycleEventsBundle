<?php

namespace W3C\LifecycleEventsBundle\Services;

use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\Common\Persistence\Event\PreUpdateEventArgs;
use Doctrine\Common\Util\ClassUtils;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use W3C\LifecycleEventsBundle\Annotation\Change;
use W3C\LifecycleEventsBundle\Annotation\Create;
use W3C\LifecycleEventsBundle\Annotation\Delete;
use W3C\LifecycleEventsBundle\Annotation\Update;
use W3C\LifecycleEventsBundle\Event\Definitions\LifecycleEvents;
use W3C\LifecycleEventsBundle\Event\LifecycleCollectionChangedEvent;
use W3C\LifecycleEventsBundle\Event\LifecyclePropertyChangedEvent;
use W3C\LifecycleEventsBundle\Event\PreAutoDispatchEvent;

/**
 * LifecycleEventsDispatcher is meant to dispatch entity creation, deletion and updates
 * to listeners
 *
 * @author Jean-Guilhem Rouel <jean-gui@w3.org>
 */
class LifecycleEventsDispatcher
{
    /**
     * List of creation events
     *
     * @var LifecycleEventArgs[]
     */
    private $creations = [];

    /**
     * List of update events
     *
     * @var PreUpdateEventArgs[]
     */
    private $updates = [];

    /**
     * List of deletion events
     *
     * @var LifecycleEventArgs[]
     */
    private $deletions = [];

    /**
     * List of property change events
     *
     * @var array
     */
    private $propertyChanges = [];

    /**
     * List of collection change events
     *
     * @var array
     */
    private $collectionChanges = [];

    /**
     * Symfony's event dispatcher
     *
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * Records whether events should be fired automatically after a successful flush
     *
     * @var boolean
     */
    private $autoDispatch;

    /**
     * Create a new instance
     *
     * @param EventDispatcherInterface $dispatcher a Symfony's event dispatcher
     * @param $autoDispatch
     */
    public function __construct(EventDispatcherInterface $dispatcher, $autoDispatch)
    {
        $this->dispatcher = $dispatcher;
        $this->autoDispatch = $autoDispatch;
    }

    /**
     * Dispatch all types of events to their listeners
     */
    public function dispatchEvents()
    {
        $this->dispatchCreationEvents();
        $this->dispatchDeletionEvents();
        $this->dispatchUpdateEvents();
        $this->dispatchPropertyChangeEvents();
        $this->dispatchCollectionChangeEvents();
    }

    /**
     * Dispatch creation events to listeners of w3c.lifecycle.created (or custom event name)
     */
    private function dispatchCreationEvents()
    {
        $creations = $this->creations;
        $this->creations = [];

        foreach ($creations as $creation) {
            $annotation = $creation[0];
            /** @var LifecycleEventArgs $eventArgs */
            $eventArgs  = $creation[1];
            $entity     = $eventArgs->getObject();

            $this->dispatcher->dispatch($annotation->event, new $annotation->class($entity));
        }
    }

    /**
     * Dispatch deletion events to listeners of w3c.lifecycle.deleted (or custom event name)
     */
    private function dispatchDeletionEvents()
    {
        $deletions = $this->deletions;
        $this->deletions = [];

        foreach ($deletions as $deletion) {
            $annotation = $deletion[0];
            /** @var LifecycleEventArgs $eventArgs */
            $eventArgs  = $deletion[1];
            $identifier = $deletion[2];
            $entity     = $eventArgs->getObject();

            $this->dispatcher->dispatch($annotation->event, new $annotation->class($entity, $identifier));
        }
    }

    /**
     * Dispatch update events to listeners of w3c.lifecycle.updated (or custom event name)
     */
    private function dispatchUpdateEvents()
    {
        $updates = $this->updates;
        $this->updates = [];

        foreach ($updates as $update) {
            list($annotation, $entity, $propertiesChanges, $collectionsChanges) = $update;

            $this->dispatcher->dispatch(
                $annotation->event,
                new $annotation->class($entity, $propertiesChanges, $collectionsChanges)
            );
        }
    }

    /**
     * Dispatch property change events to listeners of w3c.lifecycle.property_changed (or custom event name)
     */
    private function dispatchPropertyChangeEvents()
    {
        $propertyChanges = $this->propertyChanges;
        $this->propertyChanges = [];

        foreach ($propertyChanges as $propertyChange) {
            list($annotation, $entity, $property, $oldValue, $newValue) = $propertyChange;

            $this->dispatcher->dispatch(
                $annotation->event,
                new $annotation->class($entity, $property, $oldValue, $newValue)
            );
        }
    }

    /**
     * Dispatch collection change events to listeners of w3c.lifecycle.collection_changed (or custom event name)
     */
    private function dispatchCollectionChangeEvents()
    {
        $collectionChanges = $this->collectionChanges;
        $this->collectionChanges = [];

        foreach ($collectionChanges as $collectionChange) {
            list($annotation, $entity, $property, $deleted, $added) = $collectionChange;
            if ($annotation->event === LifecycleEvents::PROPERTY_CHANGED) {
                $annotation->event = LifecycleEvents::COLLECTION_CHANGED;
            }
            if($annotation->class === LifecyclePropertyChangedEvent::class) {
                $annotation->class = LifecycleCollectionChangedEvent::class;
            }

            $this->dispatcher->dispatch(
                $annotation->event,
                new $annotation->class($entity, $property, $deleted, $added)
            );
        }
    }

    /**
     * Get the list of intercepted creation events
     *
     * @return LifecycleEventArgs[] a list of LifecycleEventArgs events
     */
    public function getCreations()
    {
        return $this->creations;
    }

    public function addCreation(Create $annotation, LifecycleEventArgs $args)
    {
        $this->creations[] = [$annotation, $args];
    }

    /**
     * Get the list of intercepted deletion events
     *
     * @return LifecycleEventArgs[] a list of LifecycleEventArgs events
     */
    public function getDeletions()
    {
        return $this->deletions;
    }

    public function addDeletion(Delete $annotation, LifecycleEventArgs $args)
    {
        $classMetadata = $args->getObjectManager()->getClassMetadata(ClassUtils::getRealClass(get_class($args->getObject())));
        $this->deletions[] = [
            $annotation,
            $args,
            array_combine(
                $classMetadata->getIdentifierFieldNames(),
                $classMetadata->getIdentifierValues($args->getObject())
            )
        ];
    }

    /**
     * Get the list of intercepted update events
     *
     * @return PreUpdateEventArgs[] a list of PreUpdateEventArgs events
     */
    public function getUpdates()
    {
        return $this->updates;
    }

    public function addUpdate(Update $annotation, $entity, array $propertyChangeSet = null, array $collectionChangeSet = null)
    {
        if (list($key, $update) = $this->getUpdate($entity)) {
            $update[2] = array_merge_recursive((array)$update[2], (array)$propertyChangeSet);
            $update[3] = array_merge_recursive((array)$update[3], (array)$collectionChangeSet);
            $this->updates[$key] = $update;
        } else {
            $this->updates[] = [$annotation, $entity, $propertyChangeSet, $collectionChangeSet];
        }
    }

    public function getUpdate($entity)
    {
        foreach ($this->updates as $key => $update) {
            if ($update[1] === $entity) {
                return [$key, $update];
            }
        }

        return null;
    }

    public function getPropertyChanges()
    {
        return $this->propertyChanges;
    }

    public function addPropertyChange(Change $annotation, $entity, $property, $oldValue = null, $newValue = null)
    {
        $this->propertyChanges[] = [$annotation, $entity, $property, $oldValue, $newValue];
    }

    public function getCollectionChanges()
    {
        return $this->collectionChanges;
    }

    public function addCollectionChange(Change $annotation, $entity, $property, $deletedElements = [], $insertedElements = [])
    {
        if (list($key, $change) = $this->getCollectionChange($entity, $property)) {
            $change[3] = array_merge_recursive((array)$change[3], (array)$deletedElements);
            $change[4] = array_merge_recursive((array)$change[4], (array)$insertedElements);
            $this->collectionChanges[$key] = $change;
        } else {
            $this->collectionChanges[] = [$annotation, $entity, $property, $deletedElements, $insertedElements];
        }
    }

    public function getCollectionChange($entity, $property)
    {
        foreach ($this->collectionChanges as $key => $update) {
            if ($update[1] === $entity && $update[2] === $property) {
                return [$key, $update];
            }
        }

        return null;
    }

    /**
     * Is automatic dispatching of events active.
     * This value has no direct effect on this class but can be use elsewhere
     * (e.g. in LifecycleEventListener::postFlush())
     *
     * @return bool
     */
    public function getAutoDispatch()
    {
        return $this->autoDispatch;
    }

    /**
     * Set automatic dispatching of events
     *
     * @param $autoDispatch
     * @return $this
     */
    public function setAutoDispatch($autoDispatch)
    {
        $this->autoDispatch = $autoDispatch;

        return $this;
    }

    /**
     * Send out a preAutoDispatch event
     */
    public function preAutoDispatch()
    {
        $this->dispatcher->dispatch('w3c.lifecycle.preAutoDispatch', new PreAutoDispatchEvent($this));
    }
}
