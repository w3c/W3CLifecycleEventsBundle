<?php
/**
 * LifecyclePropertyChangedEvent.php
 *
 * @author Jean-Guilhem Rouel <jean-gui@w3.org>
 *
 * @copyright Copyright <C2><A9> 2011 W3C <C2><AE> (MIT, ERCIM, Keio) {@link http://www.w3.org/Consortium/Legal/2002/ipr-notice-20021231 Usage policies apply}.
 */
namespace W3C\LifecycleEventsBundle\Event;

/**
 * LifecyclePropertyChangedEvent is used when an entity is created or deleted
 */
class LifecyclePropertyChangedEvent extends LifecycleEvent
{
    /**
     * @var string
     */
    private $property;

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
    public function __construct($entity, $property, $oldValue = null, $newValue = null)
    {
        parent::__construct($entity);

        $this->property = $property;
        $this->oldValue = $oldValue;
        $this->newValue = $newValue;
    }

    /**
     * @return mixed
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * @param mixed $entity
     */
    public function setEntity($entity)
    {
        $this->entity = $entity;
    }

    /**
     * @return string
     */
    public function getProperty()
    {
        return $this->property;
    }

    /**
     * @param string $property
     */
    public function setProperty($property)
    {
        $this->property = $property;
    }

    /**
     * @return mixed
     */
    public function getOldValue()
    {
        return $this->oldValue;
    }

    /**
     * @param mixed $oldValue
     */
    public function setOldValue($oldValue)
    {
        $this->oldValue = $oldValue;
    }

    /**
     * @return mixed
     */
    public function getNewValue()
    {
        return $this->newValue;
    }

    /**
     * @param mixed $newValue
     */
    public function setNewValue($newValue)
    {
        $this->newValue = $newValue;
    }
}