<?php

namespace W3C\LifecycleEventsBundle\Annotation;

use W3C\LifecycleEventsBundle\Event\Definitions\LifecycleEvents;
use W3C\LifecycleEventsBundle\Event\LifecycleUpdateEvent;

/**
 * @Annotation
 * @Target("CLASS")
 *
 * @author Jean-Guilhem Rouel <jean-gui@w3.org>
 */
class Update
{
    /**
     * @var string
     */
    public $event = LifecycleEvents::UPDATED;

    /**
     * @var string
     */
    public $class = LifecycleUpdateEvent::class;

    /**
     * @var bool
     */
    public $monitor_collections = true;

    public $monitor_owning = false;
}
