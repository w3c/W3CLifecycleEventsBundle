<?php
/**
 * LifecycleEventsDispatcher.php
 *
 * @author Jean-Guilhem Rouel <jean-gui@w3.org>
 *
 * @copyright Copyright <C2><A9> 2013 W3C <C2><AE> (MIT, ERCIM, Keio) {@link http://www.w3.org/Consortium/Legal/2002/ipr-notice-20021231 Usage policies apply}.
 */
namespace W3C\LifecycleEventsBundle\Services;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use W3C\LifecycleEventsBundle\Annotation\Create;
use W3C\LifecycleEventsBundle\Annotation\Delete;
use W3C\LifecycleEventsBundle\Annotation\Update;
use W3C\LifecycleEventsBundle\Event\LifecycleEvent;
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
     * @var ArrayCollection|LifecycleEventArgs[]
     */
    private $creations;

    /**
     * List of update events
     *
     * @var ArrayCollection|PreUpdateEventArgs[]
     */
    private $updates;

    /**
     * List of deletion events
     *
     * @var ArrayCollection|LifecycleEventArgs[]
     */
    private $deletions;

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
        $this->creations = new ArrayCollection();
        $this->deletions = new ArrayCollection();
        $this->updates = new ArrayCollection();
    }

    /**
     * Dispatch all types of events to their listeners
     */
    public function dispatchEvents()
    {
        $this->dispatchCreationEvents();
        $this->dispatchDeletionEvents();
        $this->dispatchUpdateEvents();

        // Reinitialize to make sure no events are sent twice
        $this->init();
    }

    /**
     * Dispatch creation events to listeners of w3c.lifecycle.created
     */
    private function dispatchCreationEvents()
    {
        foreach ($this->creations as $creation) {
            /** @var Create $annotation */
            $annotation = $creation[0];
            $entity     = $creation[1]->getEntity();

            if ($annotation->event) {
                $eventName = $annotation->event;
            } else {
                $eventName = 'w3c.lifecycle.created';
            }

            if ($annotation->class) {
                $event = new $annotation->class($entity);
            } else {
                $event = new LifecycleEvent($entity);
            }

            $this->dispatcher->dispatch($eventName, $event);
        }
    }

    /**
     * Dispatch deletion events to listeners of w3c.lifecycle.deleted
     */
    private function dispatchDeletionEvents()
    {
        foreach ($this->deletions as $deletion) {
            /** @var Delete $annotation */
            $annotation = $deletion[0];
            $entity     = $deletion[1]->getEntity();

            if ($annotation->event) {
                $eventName = $annotation->event;
            } else {
                $eventName = 'w3c.lifecycle.created';
            }

            if ($annotation->class) {
                $event = new $annotation->class($entity);
            } else {
                $event = new LifecycleEvent($entity);
            }

            $this->dispatcher->dispatch($eventName, $event);
        }
    }

    /**
     * Dispatch update events to listeners of w3c.lifecycle.updated
     */
    private function dispatchUpdateEvents()
    {
        foreach ($this->updates as $update) {
            /** @var Update $annotation */
            $annotation = $update[0];
            $entity     = $update[1]->getEntity();
            $changes    = $update[1]->getEntityChangeSet();

            if ($annotation->event) {
                $eventName = $annotation->event;
            } else {
                $eventName = 'w3c.lifecycle.updated';
            }

            if ($annotation->class) {
                $event = new $annotation->class($entity);
            } else {
                $event = new LifecycleUpdateEvent($entity, $changes);
            }

            $this->dispatcher->dispatch($eventName, $event);
        }
    }

    /**
     * Get the list of intercepted creation events
     *
     * @return ArrayCollection a list of LifecycleEventArgs events
     */
    public function getCreations()
    {
        return $this->creations;
    }

    /**
     * Get the list of intercepted deletion events
     *
     * @return ArrayCollection a list of LifecycleEventArgs events
     */
    public function getDeletions()
    {
        return $this->deletions;
    }

    /**
     * Get the list of intercepted update events
     *
     * @return ArrayCollection a list of PreUpdateEventArgs events
     */
    public function getUpdates()
    {
        return $this->updates;
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