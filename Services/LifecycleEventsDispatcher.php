<?php
/**
 * LifecycleEventsDispatcher.php
 *
 * @author Jean-Guilhem Rouel <jean-gui@w3.org>
 *
 * @copyright Copyright <C2><A9> 2013 W3C <C2><AE> (MIT, ERCIM, Keio) {@link http://www.w3.org/Consortium/Legal/2002/ipr-notice-20021231 Usage policies apply}.
 */
namespace W3C\LifecycleEventsBundle\Services;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use W3C\LifecycleEventsBundle\Event\Definitions\LifecycleEvents;
use W3C\LifecycleEventsBundle\Event\LifecycleCollectionChangedEvent;
use W3C\LifecycleEventsBundle\Event\LifecyclePropertyChangedEvent;
use W3C\LifecycleEventsBundle\Event\PreAutoDispatchEvent;

/**
 * LifecycleEventsDispatcher is meant to dispatch entity creation, deletion and updates
 * to listeners
 */
class LifecycleEventsDispatcher
{
    /**
     * List of creation events
     *
     * @var LifecycleEventArgs[]
     */
    private $creations;

    /**
     * List of update events
     *
     * @var PreUpdateEventArgs[]
     */
    private $updates;

    /**
     * List of deletion events
     *
     * @var LifecycleEventArgs[]
     */
    private $deletions;

    /**
     * List of property change events
     *
     * @var array
     */
    private $propertyChanges;

    /**
     * List of collection change events
     *
     * @var array
     */
    private $collectionChanges;

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
        $this->init();
    }

    /**
     * (Re-)Initialize event lists, emptying them
     */
    public function init()
    {
        $this->creations = [];
        $this->deletions = [];
        $this->updates = [];
        $this->propertyChanges = [];
        $this->collectionChanges = [];
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

        // Reinitialize to make sure no events are sent twice
        $this->init();
    }

    /**
     * Dispatch creation events to listeners of w3c.lifecycle.created (or custom event name)
     */
    private function dispatchCreationEvents()
    {
        foreach ($this->creations as $creation) {
            $annotation = $creation[0];
            /** @var LifecycleEventArgs $eventArgs */
            $eventArgs  = $creation[1];
            $entity     = $eventArgs->getEntity();

            $this->dispatcher->dispatch($annotation->event, new $annotation->class($entity));
        }
    }

    /**
     * Dispatch deletion events to listeners of w3c.lifecycle.deleted (or custom event name)
     */
    private function dispatchDeletionEvents()
    {
        foreach ($this->deletions as $deletion) {
            $annotation = $deletion[0];
            /** @var LifecycleEventArgs $eventArgs */
            $eventArgs  = $deletion[1];
            $entity     = $eventArgs->getEntity();

            $this->dispatcher->dispatch($annotation->event, new $annotation->class($entity));
        }
    }

    /**
     * Dispatch update events to listeners of w3c.lifecycle.updated (or custom event name)
     */
    private function dispatchUpdateEvents()
    {
        foreach ($this->updates as $update) {
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
        foreach ($this->propertyChanges as $propertyChange) {
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
        foreach ($this->collectionChanges as $collectionChange) {
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

    public function addCreation($array)
    {
        $this->creations[] = $array;
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

    public function addDeletion($array)
    {
        $this->deletions[] = $array;
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

    public function addUpdate($array)
    {
        $this->updates[] = $array;
    }

    public function getPropertyChanges()
    {
        return $this->propertyChanges;
    }

    public function addPropertyChange($array)
    {
        $this->propertyChanges[] = $array;
    }

    public function getCollectionChanges()
    {
        return $this->collectionChanges;
    }

    public function addCollectionChange($array)
    {
        $this->collectionChanges[] = $array;
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