<?php


namespace W3C\LifecycleEventsBundle\Event;

/**
 * LifecycleEvent is used when an entity is deleted
 *
 * @author Jean-Guilhem Rouel <jean-gui@w3.org>
 */
class LifecycleDeletionEvent extends LifecycleEvent
{
    protected $identifier;

    public function __construct($entity, $identifier = null)
    {
        parent::__construct($entity);
        $this->identifier = $identifier;
    }

    public function getIdentifier()
    {
        return $this->identifier;
    }
}