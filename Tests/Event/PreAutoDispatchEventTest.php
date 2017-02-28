<?php

namespace W3C\LifecycleEventsBundle\Test\Event;

use PHPUnit\Framework\TestCase;
use W3C\LifecycleEventsBundle\Event\PreAutoDispatchEvent;
use W3C\LifecycleEventsBundle\Services\LifecycleEventsDispatcher;

/**
 * @author Jean-Guilhem Rouel <jean-gui@w3.org>
 */
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
