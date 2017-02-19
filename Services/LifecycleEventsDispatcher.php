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
use W3C\LifecycleEventsBundle\Annotation\Create;
use W3C\LifecycleEventsBundle\Annotation\Delete;
use W3C\LifecycleEventsBundle\Annotation\Update;
use W3C\LifecycleEventsBundle\Event\LifecycleEvent;
use W3C\LifecycleEventsBundle\Event\LifecyclePropertyChangedEvent;
use W3C\LifecycleEventsBundle\Event\LifecycleUpdateEvent;
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

        // Reinitialize to make sure no events are sent twice
        $this->init();
    }

    /**
     * Dispatch creation events to listeners of w3c.lifecycle.created (or custom event name)
     */
    private function dispatchCreationEvents()
    {
        foreach ($this->creations as $creation) {
            /** @var Create $annotation */
            $annotation = $creation[0];
            /** @var LifecycleEventArgs $eventArgs */
            $eventArgs  = $creation[1];
            $entity     = $eventArgs->getEntity();

            $eventName  = $annotation->event ? $annotation->event : 'w3c.lifecycle.created';
            $eventClass = $annotation->class ? $annotation->class : LifecycleEvent::class;
            $event      = new $eventClass($entity);

            $this->dispatcher->dispatch($eventName, $event);
        }
    }

    /**
     * Dispatch deletion events to listeners of w3c.lifecycle.deleted (or custom event name)
     */
    private function dispatchDeletionEvents()
    {
        foreach ($this->deletions as $deletion) {
            /** @var Delete $annotation */
            $annotation = $deletion[0];
            /** @var LifecycleEventArgs $eventArgs */
            $eventArgs  = $deletion[1];
            $entity     = $eventArgs->getEntity();

            $eventName  = $annotation->event ? $annotation->event : 'w3c.lifecycle.deleted';
            $eventClass = $annotation->class ? $annotation->class : LifecycleEvent::class;
            $event      = new $eventClass($entity);

            $this->dispatcher->dispatch($eventName, $event);
        }
    }

    /**
     * Dispatch update events to listeners of w3c.lifecycle.updated (or custom event name)
     */
    private function dispatchUpdateEvents()
    {
        foreach ($this->updates as $update) {
            /** @var Update $annotation */
            $annotation         = $update[0];
            $entity             = $update[1];
            $propertiesChanges  = $update[2];
            $collectionsChanges = $update[3];

            $eventName  = $annotation->event ? $annotation->event : 'w3c.lifecycle.updated';
            $eventClass = $annotation->class ? $annotation->class : LifecycleUpdateEvent::class;
            $event = new $eventClass($entity, $propertiesChanges, $collectionsChanges);

            $this->dispatcher->dispatch($eventName, $event);
        }
    }

    /**
     * Dispatch property change events to listeners of w3c.lifecycle.property_changed (or custom event name)
     */
    private function dispatchPropertyChangeEvents()
    {
        foreach ($this->propertyChanges as $propertyChange) {
            /** @var Update $annotation */
            $annotation = $propertyChange[0];
            $entity     = $propertyChange[1];
            $property   = $propertyChange[2];
            $oldValue   = $propertyChange[3];
            $newValue   = $propertyChange[4];

            $eventName  = $annotation->event ? $annotation->event : 'w3c.lifecycle.property_changed';
            $eventClass = $annotation->class ? $annotation->class : LifecyclePropertyChangedEvent::class;
            $event = new $eventClass($entity, $property, $oldValue, $newValue);

            $this->dispatcher->dispatch($eventName, $event);
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