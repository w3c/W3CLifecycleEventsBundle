<?php

namespace W3C\LifecycleEventsBundle\Annotation;

use W3C\LifecycleEventsBundle\Event\Definitions\LifecycleEvents;
use W3C\LifecycleEventsBundle\Event\LifecyclePropertyChangedEvent;

/**
 * @Annotation
 * @Target("PROPERTY")
 *
 * @author Jean-Guilhem Rouel <jean-gui@w3.org>
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Change
{
    public function __construct(
        public string $event = LifecycleEvents::PROPERTY_CHANGED,
        public string $class = LifecyclePropertyChangedEvent::class,
        /**
         * @deprecated to be removed in next major version and the class will always act as if it was set to true
         */
        public bool $monitor_owning = false
    ){}
}
