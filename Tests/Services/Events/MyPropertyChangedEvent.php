<?php

namespace W3C\LifecycleEventsBundle\Tests\Services\Events;

use Symfony\Component\EventDispatcher\Event;

/**
 * @author Jean-Guilhem Rouel <jean-gui@w3.org>
 */
class MyPropertyChangedEvent extends Event
{
    public function __construct($entity, $property, $old, $new)
    {
    }
}
