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
class Delete
{
    /**
     * @var string
     */
    public $event = LifecycleEvents::DELETED;

    /**
     * @var string
     */
    public $class = LifecycleEvent::class;

}
