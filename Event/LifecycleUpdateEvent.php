<?php
/**
 * LifecycleUpdateEvent.php
 *
 * @author Jean-Guilhem Rouel <jean-gui@w3.org>
 *
 * @copyright Copyright <C2><A9> 2011 W3C <C2><AE> (MIT, ERCIM, Keio) {@link http://www.w3.org/Consortium/Legal/2002/ipr-notice-20021231 Usage policies apply}.
 */
namespace W3C\LifecycleEventsBundle\Event;

/**
 * LifecycleUpdateEvent is used when an entity is created or deleted
 */
class LifecycleUpdateEvent extends LifecycleEvent
{
    /**
     * @var array
     */
    private $entityChangeSet;

    /**
     * Constructor.
     *
     * @param object $entity
     * @param array $changeSet
     */
    public function __construct($entity, array $changeSet)
    {
        parent::__construct($entity);

        $this->entityChangeSet = & $changeSet;
    }

    /**
     * Retrieves the entity changeset.
     *
     * @return array
     */
    public function getEntityChangeSet()
    {
        return $this->entityChangeSet;
    }

    /**
     * Checks if field has a changeset.
     *
     * @param string $field
     *
     * @return boolean
     */
    public function hasChangedField($field)
    {
        return isset($this->entityChangeSet[$field]);
    }

    /**
     * Gets the old value of the changeset of the changed field.
     *
     * @param string $field
     *
     * @return mixed
     */
    public function getOldValue($field)
    {
        $this->assertValidField($field);

        return $this->entityChangeSet[$field][0];
    }

    /**
     * Gets the new value of the changeset of the changed field.
     *
     * @param string $field
     *
     * @return mixed
     */
    public function getNewValue($field)
    {
        $this->assertValidField($field);

        return $this->entityChangeSet[$field][1];
    }

    /**
     * Sets the new value of this field.
     *
     * @param string $field
     * @param mixed $value
     *
     * @return void
     */
    public function setNewValue($field, $value)
    {
        $this->assertValidField($field);

        $this->entityChangeSet[$field][1] = $value;
    }

    /**
     * Asserts the field exists in changeset.
     *
     * @param string $field
     *
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    private function assertValidField($field)
    {
        if (!isset($this->entityChangeSet[$field])) {
            throw new \InvalidArgumentException(sprintf(
                'Field "%s" is not a valid field of the entity "%s" in PreUpdateEventArgs.',
                $field,
                get_class($this->getEntity())
            ));
        }
    }
}