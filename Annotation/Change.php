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
class Change
{
    /**
     * @var string
     */
    public $event = LifecycleEvents::PROPERTY_CHANGED;

    /**
     * @var string
     */
    public $class = LifecyclePropertyChangedEvent::class;

    /**
     * @var bool
     * @deprecated to be removed in next major version and the class will always act as if it was set to true
     */
    public $monitor_owning = false;
}
