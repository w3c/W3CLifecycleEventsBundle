<?php

namespace W3C\LifecycleEventsBundle\Tests\Services\Events;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * @author Jean-Guilhem Rouel <jean-gui@w3.org>
 */
class MyLifecycleEvent extends Event
{
    private $entity;
    private $identifier;

    public function __construct($entity, $identifier = null)
    {
        $this->entity = $entity;
        $this->identifier = $identifier;
    }
}
