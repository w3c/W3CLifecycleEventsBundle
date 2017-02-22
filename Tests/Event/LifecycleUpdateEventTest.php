<?php
/*
 * Copyright 2017 Jean-Guilhem Rouel
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace W3C\LifecycleEventsBundle\Test\Event;

use PHPUnit\Framework\TestCase;
use W3C\LifecycleEventsBundle\Event\LifecycleUpdateEvent;
use W3C\LifecycleEventsBundle\Tests\Annotation\Fixtures\User;

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

    public function setUp()
    {
        $entity                  = new User();
        $this->propertyChanges   = ['name' => ['foo', 'bar']];
        $this->deleted           = [new User()];
        $this->inserted          = [new User()];
        $this->collectionChanges = ['friends' => ['deleted' => $this->deleted, 'inserted' => $this->inserted]];
        $this->event             = new LifecycleUpdateEvent($entity, $this->propertyChanges, $this->collectionChanges);
    }

    public function testAccessors()
    {
        $this->assertEquals($this->propertyChanges, $this->event->getPropertiesChangeSet());
        $this->assertEquals('bar', $this->event->getNewValue('name'));
        $this->assertEquals('foo', $this->event->getOldValue('name'));
        $this->assertTrue($this->event->hasChangedField('name'));
        $this->assertFalse($this->event->hasChangedField('family'));

        $this->assertSame($this->collectionChanges, $this->event->getCollectionsChangeSet());
        $this->assertSame($this->inserted, $this->event->getAddedElements('friends'));
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
