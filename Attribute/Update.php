<?php

namespace W3C\LifecycleEventsBundle\Attribute;

use W3C\LifecycleEventsBundle\Event\Definitions\LifecycleEvents;
use W3C\LifecycleEventsBundle\Event\LifecycleUpdateEvent;

/**
 * @author Jean-Guilhem Rouel <jean-gui@w3.org>
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class Update
{
    public function __construct(
        public string $event = LifecycleEvents::UPDATED,
        public string $class = LifecycleUpdateEvent::class,
        public bool $monitor_collections = true,
        public bool $monitor_owning = false,
    ){}
}
