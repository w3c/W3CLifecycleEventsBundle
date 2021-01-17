<?php

namespace W3C\LifecycleEventsBundle\Tests\Services\Events;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * @author Jean-Guilhem Rouel <jean-gui@w3.org>
 */
class MyUpdatedEvent extends Event
{
    public function __construct($entity, $propertiesChanges, $collectionChanges)
    {
    }
}
