<?php

namespace W3C\LifecycleEventsBundle\Tests\Event;

use PHPUnit\Framework\TestCase;
use W3C\LifecycleEventsBundle\Event\LifecycleEvent;
use W3C\LifecycleEventsBundle\Tests\Annotation\Fixtures\User;

/**
 * @author Jean-Guilhem Rouel <jean-gui@w3.org>
 */
class LifecycleEventTest extends TestCase
{
    public function testAccessors()
    {
        $entity = new User();
        $event  = new LifecycleEvent($entity);

        $this->assertSame($entity, $event->getEntity());
    }
}
