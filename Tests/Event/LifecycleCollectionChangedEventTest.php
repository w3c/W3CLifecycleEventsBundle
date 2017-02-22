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
use W3C\LifecycleEventsBundle\Event\LifecycleCollectionChangedEvent;
use W3C\LifecycleEventsBundle\Tests\Annotation\Fixtures\User;

class LifecycleCollectionChangedEventTest extends TestCase
{
    public function testAccessors()
    {
        $deleted  = [new User()];
        $inserted = [new User()];
        $event    = new LifecycleCollectionChangedEvent(new User(), 'friends', $deleted, $inserted);

        $this->assertEquals('friends', $event->getProperty());
        $this->assertEquals($deleted, $event->getDeletedElements());
        $this->assertEquals($inserted, $event->getInsertedElements());
    }
}
