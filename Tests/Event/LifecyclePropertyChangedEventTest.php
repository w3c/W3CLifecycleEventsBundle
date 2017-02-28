<?php

namespace W3C\LifecycleEventsBundle\Test\Event;

use PHPUnit\Framework\TestCase;
use W3C\LifecycleEventsBundle\Event\LifecyclePropertyChangedEvent;
use W3C\LifecycleEventsBundle\Tests\Annotation\Fixtures\User;

/**
 * @author Jean-Guilhem Rouel <jean-gui@w3.org>
 */
class LifecyclePropertyChangedEventTest extends TestCase
{
    public function testAccessors()
    {
        $entity = new User();
        $event  = new LifecyclePropertyChangedEvent($entity, 'name', 'foo', 'bar');

        $this->assertEquals('name', $event->getProperty());
        $this->assertEquals('foo', $event->getOldValue());
        $this->assertEquals('bar', $event->getNewValue());
    }
}
