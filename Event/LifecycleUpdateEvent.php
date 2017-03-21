<?php

namespace W3C\LifecycleEventsBundle\Event;

/**
 * LifecycleUpdateEvent is used when an entity is updated
 *
 * @author Jean-Guilhem Rouel <jean-gui@w3.org>
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
     * @param object $entity the entity being modified
     * @param array $propertiesChangeSet list of changes to properties
     * @param array $collectionsChangeSet list of changes to collections
     */
    public function __construct($entity, array $propertiesChangeSet = [], array $collectionsChangeSet = [])
    {
        parent::__construct($entity);

        $this->propertiesChangeSet = $propertiesChangeSet;
        $this->collectionsChangeSet = $collectionsChangeSet;
    }

    /**
     * Return the list of properties that have changed
     *
     * @return array
     */
    public function getChangedProperties()
    {
        return array_keys($this->propertiesChangeSet);
    }

    /**
     * Return whether some properties have changed
     *
     * @return bool
     */
    public function havePropertiesChanged()
    {
        return $this->propertiesChangeSet && count($this->propertiesChangeSet) > 0;
    }

    /**
     * Return the list of collections that have changed
     *
     * @return array
     */
    public function getChangedCollections()
    {
        return array_keys($this->collectionsChangeSet);
    }

    /**
     * Return whether some collections have changed
     *
     * @return bool
     */
    public function haveCollectionsChanged()
    {
        return $this->collectionsChangeSet && count($this->collectionsChangeSet) > 0;
    }

    /**
     * Check if field has a changeset.
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
     * Get the old value of the changeset of the changed field.
     *
     * @param string $field
     *
     * @return mixed
     */
    public function getOldValue($field)
    {
        $this->assertValidProperty($field);

        return $this->propertiesChangeSet[$field]['old'];
    }

    /**
     * Get the new value of the changeset of the changed field.
     *
     * @param string $field
     *
     * @return mixed
     */
    public function getNewValue($field)
    {
        $this->assertValidProperty($field);

        return $this->propertiesChangeSet[$field]['new'];
    }

    /**
     * Get the list of elements deleted from the collection $field
     *
     * @param string $field
     *
     * @return array
     */
    public function getDeletedElements($field)
    {
        $this->assertValidCollection($field);

        return $this->collectionsChangeSet[$field]['deleted'];
    }

    /**
     * Get the list of elements inserted to the collection $field
     *
     * @param string $field
     *
     * @return array
     */
    public function getInsertedElements($field)
    {
        $this->assertValidCollection($field);

        return $this->collectionsChangeSet[$field]['inserted'];
    }

    /**
     * Assert if the field exists in changeset.
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
     * Assert if the field exists in changeset.
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
