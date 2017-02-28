<?php

namespace W3C\LifecycleEventsBundle\Event\Definitions;

/**
 * @author Jean-Guilhem Rouel <jean-gui@w3.org>
 */
final class LifecycleEvents {
    /**
     * Thrown each time an entity is created
     *
     * The event listener receives an
     * W3C\LifecycleEventsBundle\Event\LifecycleEvent instance.
     *
     * @var string
     */
    const CREATED = 'w3c.lifecycle.created';

    /**
     * Thrown each time a entity is updated
     *
     * The event listener receives an
     * W3C\LifecycleEventsBundle\Event\LifecycleUpdateEvent instance.
     *
     * @var string
     */
    const UPDATED = 'w3c.lifecycle.updated';

    /**
     * Thrown each time a property of an entity is changed
     *
     * The event listener receives an
     * W3C/LifecycleBundle\Event\LifecyclePropertyChangedEvent instance.
     *
     * @var string
     */
    const PROPERTY_CHANGED = 'w3c.lifecycle.property_changed';

    /**
     * Thrown each time a collection of an entity is changed
     *
     * The event listener receives an
     * W3C/LifecycleBundle\Event\LifecycleCollectionChangedEvent instance.
     *
     * @var string
     */
    const COLLECTION_CHANGED = 'w3c.lifecycle.collection_changed';

    /**
     * Thrown each time an entity is deleted
     *
     * The event listener receives an
     * W3C\LifecycleEventsBundle\Event\LifecycleEvent.
     *
     * @var string
     */
    const DELETED = 'w3c.lifecycle.deleted';
}