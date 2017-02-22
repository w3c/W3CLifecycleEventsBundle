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
use W3C\LifecycleEventsBundle\Event\PreAutoDispatchEvent;
use W3C\LifecycleEventsBundle\Services\LifecycleEventsDispatcher;

class PreAutoDispatchEventTest extends TestCase
{
    public function testAccessors()
    {
        /** @var LifecycleEventsDispatcher $dispatcher */
        $dispatcher = $this->getMockBuilder(LifecycleEventsDispatcher::class)
            ->disableOriginalConstructor()
            ->getMock();
        $event      = new PreAutoDispatchEvent($dispatcher);

        $this->assertSame($dispatcher, $event->getDispatcher());
    }
}
