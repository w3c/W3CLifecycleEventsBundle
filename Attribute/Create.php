<?php

namespace W3C\LifecycleEventsBundle\Attribute;

use W3C\LifecycleEventsBundle\Event\Definitions\LifecycleEvents;
use W3C\LifecycleEventsBundle\Event\LifecycleEvent;

/**
 * @author Jean-Guilhem Rouel <jean-gui@w3.org>
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class Create
{
    public function __construct(
        public string $event = LifecycleEvents::CREATED,
        public string $class = LifecycleEvent::class,
    ){}
}
