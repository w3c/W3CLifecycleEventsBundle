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
    protected object $entity;

    public function __construct(object $entity)
    {
        $this->entity = $entity;
    }

    public function getEntity(): object
    {
        return $this->entity;
    }
}
