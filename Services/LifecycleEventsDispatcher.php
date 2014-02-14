<?php
/**
 * LifecycleEventsDispatcher.php
 *
 * @author Jean-Guilhem Rouel <jean-gui@w3.org>
 *
 * @copyright Copyright <C2><A9> 2013 W3C <C2><AE> (MIT, ERCIM, Keio) {@link http://www.w3.org/Consortium/Legal/2002/ipr-notice-20021231 Usage policies apply}.
 */
namespace W3C\LifecycleEventsBundle\Services;

use Symfony\Component\HttpKernel\Debug\TraceableEventDispatcher;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;

use W3C\LifecycleEventsBundle\Event\LifecycleEvent;
use W3C\LifecycleEventsBundle\Event\LifecycleUpdateEvent;

/**
 * LifecycleEventsDispatcher is meant to dispatch entity creation, deletion and updates
 * to listeners
 */
class LifecycleEventsDispatcher {
    private $creations;
    private $updates;
    private $deletions;

    /**
     * Symfony's event dispatcher
     */
    private $dispatcher;

    /**
     * Create a new instance
     *
     * @param Symfony\Component\HttpKernel\Debug\TraceableEventDispatcher $dispatcher a Symfony's event dispatcher
     */
    public function __construct(TraceableEventDispatcher $dispatcher) {
        $this->dispatcher = $dispatcher;
        $this->init();
    }

    /**
     * (Re-)Initialize event lists, emptying them
     */
    public function init() {
        $this->creations = new ArrayCollection();
        $this->deletions = new ArrayCollection();
        $this->updates   = new ArrayCollection();
    }

    /**
     * Dispatch all types of events to their listeners
     */
    function dispatchEvents() {
        $this->dispatchCreationEvents();
        $this->dispatchDeletionEvents();
        $this->dispatchUpdateEvents();

        // Reinitialize to make sure no events are sent twice
        $this->init();
    }

    /**
     * Dispatch creation events to listeners of w3c.lifecycle.created
     */
    private function dispatchCreationEvents() {
        foreach($this->creations as $creation) {
            // We could imagine sending more specialized events there (UserCreated, GroupCreated, ...)
            // We could also make more checks to avoid doing stupid checks as explained in the last paragraph
            $this->dispatcher->dispatch('w3c.lifecycle.created',
                                        new LifecycleEvent($creation->getEntity()));
        }
    }

    /**
     * Dispatch deletion events to listeners of w3c.lifecycle.deleted
     */
    private function dispatchDeletionEvents() {
        foreach($this->deletions as $deletion) {
            $this->dispatcher->dispatch('w3c.lifecycle.deleted',
                                        new LifecycleEvent($deletion->getEntity()));
        }
    }

    /**
     * Dispatch update events to listeners of w3c.lifecycle.updated
     */
    private function dispatchUpdateEvents() {
        foreach($this->updates as $update) {
            $this->dispatcher->dispatch('w3c.lifecycle.updated',
                                        new LifecycleUpdateEvent($update->getEntity(),
                                                                 $update->getEntityChangeSet()));
        }
    }

    /**
     * Get the list of intercepted creation events
     *
     * @return ArrayCollection a list of LifecycleEventArgs events
     */
    public function getCreations() {
        return $this->creations;
    }

    /**
     * Get the list of intercepted deletion events
     *
     * @return ArrayCollection a list of LifecycleEventArgs events
     */
    public function getDeletions() {
        return $this->deletions;
    }

    /**
     * Get the list of intercepted update events
     *
     * @return ArrayCollection a list of PreUpdateEventArgs events
     */
    public function getUpdates() {
        return $this->updates;
    }
}

?>