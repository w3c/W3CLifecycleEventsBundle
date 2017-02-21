<?php
/**
 * PostFlushListener.php
 *
 * @author Jean-Guilhem Rouel <jean-gui@w3.org>
 *
 * @copyright Copyright <C2><A9> 2014 W3C <C2><AE> (MIT, ERCIM, Keio) {@link http://www.w3.org/Consortium/Legal/2002/ipr-notice-20021231 Usage policies apply}.
 */
namespace W3C\LifecycleEventsBundle\EventListener;

use Doctrine\ORM\Event\PostFlushEventArgs;
use W3C\LifecycleEventsBundle\Services\LifecycleEventsDispatcher;

/**
 * Listen to Doctrine postFlush to dispatch lifecycle events
 */
class PostFlushListener
{
    /**
     * Events dispatcher
     *
     * @var LifecycleEventsDispatcher
     */
    private $dispatcher;

    /**
     * Constructs a new instance
     *
     * @param LifecycleEventsDispatcher $dispatcher the dispatcher to fed
     */
    public function __construct(LifecycleEventsDispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * Called upon receiving postFlush events
     * Dispatches all gathered events
     *
     * @param PostFlushEventArgs $args post flush event
     */
    public function postFlush(PostFlushEventArgs $args)
    {
        if ($this->dispatcher->getAutoDispatch()) {
            $this->dispatcher->preAutoDispatch();
            $this->dispatcher->dispatchEvents();
        }
    }

}

?>