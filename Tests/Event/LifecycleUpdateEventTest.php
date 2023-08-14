<?php

namespace W3C\LifecycleEventsBundle\Tests\Event;

use PHPUnit\Framework\TestCase;
use W3C\LifecycleEventsBundle\Event\LifecycleUpdateEvent;
use W3C\LifecycleEventsBundle\Tests\Attribute\Fixtures\User;

/**
 * @author Jean-Guilhem Rouel <jean-gui@w3.org>
 */
class LifecycleUpdateEventTest extends TestCase
{
    private $deleted;
    private $inserted;
    private $propertyChanges;
    private $collectionChanges;
    /**
     * @var LifecycleUpdateEvent
     */
    private $event;

    public function setUp() : void
    {
        $entity                  = new User();
        $this->propertyChanges   = ['name' => ['old' => 'foo', 'new' => 'bar']];
        $this->deleted           = [new User()];
        $this->inserted          = [new User()];
        $this->collectionChanges = ['friends' => ['deleted' => $this->deleted, 'inserted' => $this->inserted]];
        $this->event             = new LifecycleUpdateEvent($entity, $this->propertyChanges, $this->collectionChanges);
    }

    public function testAccessors()
    {
        $this->assertTrue($this->event->havePropertiesChanged());
        $this->assertTrue($this->event->haveCollectionsChanged());

        $this->assertEquals(['name'], $this->event->getChangedProperties());
        $this->assertEquals('bar', $this->event->getNewValue('name'));
        $this->assertEquals('foo', $this->event->getOldValue('name'));
        $this->assertTrue($this->event->hasChangedField('name'));
        $this->assertFalse($this->event->hasChangedField('family'));

        $this->assertEquals(['friends'], $this->event->getChangedCollections());
        $this->assertSame($this->inserted, $this->event->getInsertedElements('friends'));
        $this->assertSame($this->deleted, $this->event->getDeletedElements('friends'));
        $this->assertTrue($this->event->hasChangedField('friends'));
        $this->assertFalse($this->event->hasChangedField('tags'));
    }

    /**
     * @dataProvider provideTestInvalidCollection
     */
    public function testInvalidCollection($field)
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->event->getDeletedElements($field);
    }

    public function provideTestInvalidCollection()
    {
        return [['name'], ['tags']];
    }

    /**
     * @dataProvider provideTestInvalidProperty
     */
    public function testInvalidProperty($field)
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->event->getOldValue($field);
    }

    public function provideTestInvalidProperty()
    {
        return [['friends'], ['family']];
    }
}
