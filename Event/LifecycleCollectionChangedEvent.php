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
class LifecycleCollectionChangedEvent extends LifecycleEvent
{
    /**
     * @var string
     */
    private $property;

    /**
     * @var array
     */
    private $deletedElements;

    /**
     * @var array
     */
    private $insertedElements;

    /**
     * Constructor.
     *
     * @param object $entity
     * @param string $property
     * @param array $deletedElements
     * @param array $insertedElements
     */
    public function __construct($entity, $property, $deletedElements = null, $insertedElements = null)
    {
        parent::__construct($entity);

        $this->property         = $property;
        $this->deletedElements  = $deletedElements;
        $this->insertedElements = $insertedElements;
    }

    /**
     * @return string
     */
    public function getProperty()
    {
        return $this->property;
    }

    /**
     * @return array|null
     */
    public function getDeletedElements()
    {
        return $this->deletedElements;
    }

    /**
     * @return array|null
     */
    public function getInsertedElements()
    {
        return $this->insertedElements;
    }
}