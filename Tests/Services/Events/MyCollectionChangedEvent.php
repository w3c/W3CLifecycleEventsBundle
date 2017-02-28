<?php

namespace W3C\LifecycleEventsBundle\Tests\Services\Events;

use Symfony\Component\EventDispatcher\Event;

/**
 * @author Jean-Guilhem Rouel <jean-gui@w3.org>
 */
class MyCollectionChangedEvent extends Event
{
    public function __construct($entity, $property, $deleted, $inserted)
    {
    }
}
