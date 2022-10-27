<?php

namespace W3C\LifecycleEventsBundle\EventListener;

use Doctrine\ORM\Event\PostFlushEventArgs;
use W3C\LifecycleEventsBundle\Services\LifecycleEventsDispatcher;

/**
 * Listen to Doctrine postFlush to dispatch lifecycle events
 *
 * @author Jean-Guilhem Rouel <jean-gui@w3.org>
 */
class PostFlushListener
{
    private LifecycleEventsDispatcher $dispatcher;

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
    public function postFlush(PostFlushEventArgs $args): void
    {
        if ($this->dispatcher->getAutoDispatch()) {
            $this->dispatcher->preAutoDispatch();
            $this->dispatcher->dispatchEvents();
        }
    }

}
