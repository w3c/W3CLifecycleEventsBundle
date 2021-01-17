<?php

namespace W3C\LifecycleEventsBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * LifecycleEvent is used when an entity is created
 *
 * @author Jean-Guilhem Rouel <jean-gui@w3.org>
 */
class LifecycleEvent extends Event
{
    protected $entity;

    public function __construct($entity)
    {
        $this->entity = $entity;
    }

    public function getEntity()
    {
        return $this->entity;
    }
}
