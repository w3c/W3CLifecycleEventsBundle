<?php
/**
 * LifecycleEventsListener.php
 *
 * @author Jean-Guilhem Rouel <jean-gui@w3.org>
 *
 * @copyright Copyright <C2><A9> 2014 W3C <C2><AE> (MIT, ERCIM, Keio) {@link http://www.w3.org/Consortium/Legal/2002/ipr-notice-20021231 Usage policies apply}.
 */
namespace W3C\LifecycleEventsBundle\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;

use W3C\LifecycleEventsBundle\Services\LifecycleEventsDispatcher;

/**
 * Listen to Doctrine postPersist, postRemove and preUpdate to feed a LifecycleEventsDispatcher
 */
class LifecycleEventsListener {
    private $dispatcher;

    /**
     * Constructs a new instance
     *
     * @param W3C\LifecycleEventsBundle\Services\LifecycleEventsDispatcher $dispatcher the dispatcher to fed
     */
    public function __construct(LifecycleEventsDispatcher $dispatcher) {
        $this->dispatcher = $dispatcher;
    }

    /**
     * Called upon receiving postPersist events
     *
     * @param Doctrine\ORM\Event\LifecycleEventArgs $e event to feed the dispatcher with
     */
    public function postPersist(LifecycleEventArgs $e) {
        $this->dispatcher->getCreations()->add($e);
    }

    /**
     * Called upon receiving postRemove events
     *
     * @param Doctrine\ORM\Event\LifecycleEventArgs $e event to feed the dispatcher with
     */
    public function postRemove(LifecycleEventArgs $e) {
        $this->dispatcher->getDeletions()->add($e);
    }

    /**
     * Called upon receiving preUpdate events
     *
     * @param Doctrine\ORM\Event\LifecycleEventArgs $e event to feed the dispatcher with
     */
    public function preUpdate(PreUpdateEventArgs $e) {
        $this->dispatcher->getUpdates()->add($e);
    }
}

?>