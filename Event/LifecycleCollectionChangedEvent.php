<?php
/**
 * LifecycleCollectionChangedEvent.php
 *
 * @author Jean-Guilhem Rouel <jean-gui@w3.org>
 *
 * @copyright Copyright <C2><A9> 2011 W3C <C2><AE> (MIT, ERCIM, Keio) {@link http://www.w3.org/Consortium/Legal/2002/ipr-notice-20021231 Usage policies apply}.
 */
namespace W3C\LifecycleEventsBundle\Event;

/**
 * LifecycleCollectionChangedEvent is used when an entity's collection is modified
 */
class LifecycleCollectionChangedEvent extends LifecyclePropertyChangedEvent
{
    public function getDeletedElements()
    {
        return $this->getOldValue();
    }

    public function getInsertedElements()
    {
        return $this->getNewValue();
    }
}