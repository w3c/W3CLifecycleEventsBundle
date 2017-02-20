<?php
/**
 * LifecycleEvents.php
 *
 * @author Jean-Guilhem Rouel <jean-gui@w3.org>
 * @author Denis Ah-Kang <denis@w3.org>
 * @author Vivien Lacourba <vivien@w3.org>
 *
 * @copyright Copyright © 2016 W3C ® (MIT, ERCIM, Keio, Beihang) {@link http://www.w3.org/Consortium/Legal/2002/ipr-notice-20021231 Usage policies apply}.
 */

namespace W3C\LifecycleEventsBundle\Event\Definitions;


/**
 * Class LifecycleEvents
 *
 * @package src\AppBundle\Event\Definitions
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