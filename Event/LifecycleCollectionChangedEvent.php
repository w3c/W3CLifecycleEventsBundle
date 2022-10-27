<?php

namespace W3C\LifecycleEventsBundle\Event;

/**
 * LifecycleCollectionChangedEvent is used when an entity's collection is modified
 *
 * @author Jean-Guilhem Rouel <jean-gui@w3.org>
 */
class LifecycleCollectionChangedEvent extends LifecycleEvent
{
    private string $property;
    private ?array $deletedElements;
    private ?array $insertedElements;

    /**
     * Constructor.
     *
     * @param object     $entity
     * @param string     $property
     * @param array|null $deletedElements
     * @param array|null $insertedElements
     */
    public function __construct(object $entity, string $property, array $deletedElements = null, array $insertedElements = null)
    {
        parent::__construct($entity);

        $this->property         = $property;
        $this->deletedElements  = $deletedElements;
        $this->insertedElements = $insertedElements;
    }

    public function getProperty(): string
    {
        return $this->property;
    }

    public function getDeletedElements(): ?array
    {
        return $this->deletedElements;
    }

    public function getInsertedElements(): ?array
    {
        return $this->insertedElements;
    }
}
