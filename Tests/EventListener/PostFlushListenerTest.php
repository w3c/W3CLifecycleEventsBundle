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

namespace W3C\LifecycleEventsBundle\Tests\EventListener;


use Doctrine\ORM\Event\PostFlushEventArgs;
use PHPUnit\Framework\TestCase;
use W3C\LifecycleEventsBundle\EventListener\PostFlushListener;
use W3C\LifecycleEventsBundle\Services\LifecycleEventsDispatcher;

class PostFlushListenerTest extends TestCase
{
    /**
     * @var LifecycleEventsDispatcher|\PHPUnit_Framework_MockObject_MockObject
     */
    private $dispatcher;

    /**
     * @var PostFlushEventArgs|\PHPUnit_Framework_MockObject_MockObject
     */
    private $event;

    public function setUp()
    {
        parent::setUp();

        $this->dispatcher = $this
            ->getMockBuilder(LifecycleEventsDispatcher::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->event = $this
            ->getMockBuilder(PostFlushEventArgs::class)
            ->disableOriginalConstructor()
            ->getMock();
    }


    public function testPostFlushDisabled()
    {
        $listener = new PostFlushListener($this->dispatcher);

        $this->dispatcher->expects($this->once())
            ->method('getAutoDispatch')
            ->willReturn(false);

        $this->dispatcher->expects($this->never())
            ->method('preAutoDispatch');

        $this->dispatcher->expects($this->never())
            ->method('dispatchEvents');

        $listener->postFlush($this->event);
    }

    public function testPostFlushEnabled()
    {
        $listener = new PostFlushListener($this->dispatcher);

        $this->dispatcher->expects($this->once())
            ->method('getAutoDispatch')
            ->willReturn(true);

        $this->dispatcher->expects($this->once())
            ->method('preAutoDispatch');

        $this->dispatcher->expects($this->once())
            ->method('dispatchEvents');

        $listener->postFlush($this->event);
    }

}
