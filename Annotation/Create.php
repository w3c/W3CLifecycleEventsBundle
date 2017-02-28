<?php

namespace W3C\LifecycleEventsBundle\Annotation;

use W3C\LifecycleEventsBundle\Event\Definitions\LifecycleEvents;
use W3C\LifecycleEventsBundle\Event\LifecycleEvent;

/**
 * @Annotation
 * @Target("CLASS")
 *
 * @author Jean-Guilhem Rouel <jean-gui@w3.org>
 */
class Create
{
    /**
     * @var string
     */
    public $event = LifecycleEvents::CREATED;

    /**
     * @var string
     */
    public $class = LifecycleEvent::class;

}
