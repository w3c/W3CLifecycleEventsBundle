<?php

namespace W3C\LifecycleEventsBundle\Tests\Event;

use PHPUnit\Framework\TestCase;
use W3C\LifecycleEventsBundle\Event\LifecycleDeletionEvent;
use W3C\LifecycleEventsBundle\Tests\Attribute\Fixtures\User;

/**
 * @author Jean-Guilhem Rouel <jean-gui@w3.org>
 */
class LifecycleDeletionEventTest extends TestCase
{
    public function testAccessors()
    {
        $entity = new User();
        $event  = new LifecycleDeletionEvent($entity, ['foo' => 'bar', 'baz' => 2]);

        $this->assertSame($entity, $event->getEntity());
        $this->assertSame(['foo' => 'bar', 'baz' => 2], $event->getIdentifier());
    }
}
