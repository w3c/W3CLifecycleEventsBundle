<?php

namespace W3C\LifecycleEventsBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;
use W3C\LifecycleEventsBundle\Services\LifecycleEventsDispatcher;

/**
 * LifecycleEvent is used when an entity is created or deleted
 *
 * @author Jean-Guilhem Rouel <jean-gui@w3.org>
 */
class PreAutoDispatchEvent extends Event
{
    protected LifecycleEventsDispatcher $dispatcher;

    public function __construct(LifecycleEventsDispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public function getDispatcher(): LifecycleEventsDispatcher
    {
        return $this->dispatcher;
    }
}
