<?php

namespace W3C\LifecycleEventsBundle\Attribute;

use W3C\LifecycleEventsBundle\Event\Definitions\LifecycleEvents;
use W3C\LifecycleEventsBundle\Event\LifecycleDeletionEvent;

/**
 * @author Jean-Guilhem Rouel <jean-gui@w3.org>
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class Delete
{
    public function __construct(
        public string $event = LifecycleEvents::DELETED,
        public string $class = LifecycleDeletionEvent::class,
    ){}
}
