<?php

namespace W3C\LifecycleEventsBundle\Tests\Event;

use PHPUnit\Framework\TestCase;
use W3C\LifecycleEventsBundle\Event\LifecycleCollectionChangedEvent;
use W3C\LifecycleEventsBundle\Tests\Attribute\Fixtures\User;

/**
 * @author Jean-Guilhem Rouel <jean-gui@w3.org>
 */
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
