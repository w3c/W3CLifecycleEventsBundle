<?php

namespace W3C\LifecycleEventsBundle\Tests\EventListener;

use Doctrine\ORM\Event\PostFlushEventArgs;
use PHPUnit\Framework\TestCase;
use W3C\LifecycleEventsBundle\EventListener\PostFlushListener;
use W3C\LifecycleEventsBundle\Services\LifecycleEventsDispatcher;

/**
 * @author Jean-Guilhem Rouel <jean-gui@w3.org>
 */
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
