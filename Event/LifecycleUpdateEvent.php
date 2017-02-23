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
 * LifecycleUpdateEvent is used when an entity is updated
 */
class LifecycleUpdateEvent extends LifecycleEvent
{
    /**
     * @var array
     */
    private $propertiesChangeSet;

    /**
     * @var array
     */
    private $collectionsChangeSet;

    /**
     * Constructor.
     *
     * @param object $entity
     * @param array $propertiesChangeSet
     * @param array $collectionsChangeSet
     */
    public function __construct($entity, array $propertiesChangeSet = null, array $collectionsChangeSet = null)
    {
        parent::__construct($entity);

        $this->propertiesChangeSet = $propertiesChangeSet;
        $this->collectionsChangeSet = $collectionsChangeSet;
    }

    public function getChangedProperties()
    {
        return array_keys($this->propertiesChangeSet);
    }

    public function havePropertiesChanged()
    {
        return $this->propertiesChangeSet && count($this->propertiesChangeSet) > 0;
    }

    public function getChangedCollections()
    {
        return array_keys($this->collectionsChangeSet);
    }

    public function haveCollectionsChanged()
    {
        return $this->collectionsChangeSet && count($this->collectionsChangeSet) > 0;
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
        return isset($this->propertiesChangeSet[$field]) || isset($this->collectionsChangeSet[$field]);
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
        $this->assertValidProperty($field);

        return $this->propertiesChangeSet[$field][0];
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
        $this->assertValidProperty($field);

        return $this->propertiesChangeSet[$field][1];
    }

    public function getDeletedElements($field)
    {
        $this->assertValidCollection($field);

        return $this->collectionsChangeSet[$field]['deleted'];
    }

    public function getInsertedElements($field)
    {
        $this->assertValidCollection($field);

        return $this->collectionsChangeSet[$field]['inserted'];
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
    private function assertValidProperty($field)
    {
        if (!isset($this->propertiesChangeSet[$field])) {
            throw new \InvalidArgumentException(sprintf(
                'Field "%s" is not a valid field of the entity "%s" org has not changed.',
                $field,
                get_class($this->getEntity())
            ));
        }
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
    private function assertValidCollection($field)
    {
        if (!isset($this->collectionsChangeSet[$field])) {
            throw new \InvalidArgumentException(sprintf(
                'Field "%s" is not a valid collection of the entity "%s" org has not changed.',
                $field,
                get_class($this->getEntity())
            ));
        }
    }
}