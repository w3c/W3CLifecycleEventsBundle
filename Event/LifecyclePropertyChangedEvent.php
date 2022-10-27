<?php

namespace W3C\LifecycleEventsBundle\Event;

/**
 * LifecyclePropertyChangedEvent is used when an entity is created or deleted
 *
 * @author Jean-Guilhem Rouel <jean-gui@w3.org>
 */
class LifecyclePropertyChangedEvent extends LifecycleEvent
{
    /**
     * @var string
     */
    private string $property;

    /**
     * @var mixed
     */
    private $oldValue;

    /**
     * @var mixed
     */
    private $newValue;

    /**
     * Constructor.
     *
     * @param object $entity
     * @param string $property
     * @param mixed $oldValue
     * @param mixed $newValue
     */
    public function __construct(object $entity, string $property, $oldValue = null, $newValue = null)
    {
        parent::__construct($entity);

        $this->property = $property;
        $this->oldValue = $oldValue;
        $this->newValue = $newValue;
    }

    /**
     * @return string
     */
    public function getProperty(): string
    {
        return $this->property;
    }

    /**
     * @return mixed
     */
    public function getOldValue()
    {
        return $this->oldValue;
    }

    /**
     * @return mixed
     */
    public function getNewValue()
    {
        return $this->newValue;
    }
}
