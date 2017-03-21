<?php

namespace W3C\LifecycleEventsBundle\Tests\Services;

use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\Common\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use W3C\LifecycleEventsBundle\Annotation\Change;
use W3C\LifecycleEventsBundle\Annotation\Create;
use W3C\LifecycleEventsBundle\Annotation\Delete;
use W3C\LifecycleEventsBundle\Annotation\Update;
use W3C\LifecycleEventsBundle\Event\Definitions\LifecycleEvents;
use W3C\LifecycleEventsBundle\Event\LifecycleCollectionChangedEvent;
use W3C\LifecycleEventsBundle\Event\LifecycleEvent;
use W3C\LifecycleEventsBundle\Event\LifecyclePropertyChangedEvent;
use W3C\LifecycleEventsBundle\Event\LifecycleUpdateEvent;
use W3C\LifecycleEventsBundle\Event\PreAutoDispatchEvent;
use W3C\LifecycleEventsBundle\Services\LifecycleEventsDispatcher;
use W3C\LifecycleEventsBundle\Tests\Annotation\Fixtures\User;
use W3C\LifecycleEventsBundle\Tests\Services\Events\MyCollectionChangedEvent;
use W3C\LifecycleEventsBundle\Tests\Services\Events\MyLifecycleEvent;
use W3C\LifecycleEventsBundle\Tests\Services\Events\MyPropertyChangedEvent;
use W3C\LifecycleEventsBundle\Tests\Services\Events\MyUpdatedEvent;

/**
 * @author Jean-Guilhem Rouel <jean-gui@w3.org>
 */
class LifecycleEventsDispatcherTest extends TestCase
{
    /**
     * @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $sfDispatcher;

    /**
     * @var LifecycleEventsDispatcher
     */
    private $dispatcher;

    /**
     * @var ObjectManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $objectManager;

    public function setUp()
    {
        parent::setUp();

        $this->sfDispatcher = $this
            ->getMockBuilder(EventDispatcherInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        ;
        $this->dispatcher = new LifecycleEventsDispatcher($this->sfDispatcher, true);
        $this->objectManager = $this
            ->getMockBuilder(ObjectManager::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testDispatchCreationEvents()
    {
        $user = new User();
        $annotation = new Create();
        $args = new LifecycleEventArgs($user, $this->objectManager);
        $this->dispatcher->addCreation($annotation, $args);

        $this->assertCount(1, $this->dispatcher->getCreations());

        $expectedEvent = new LifecycleEvent($user);
        $this->sfDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(LifecycleEvents::CREATED, $expectedEvent)
        ;

        $this->dispatcher->dispatchEvents();
    }

    public function testDispatchCreationEventsCustom()
    {
        $user       = new User();
        $annotation = new Create();
        $annotation->event = 'test';
        $annotation->class = MyLifecycleEvent::class;
        $args       = new LifecycleEventArgs($user, $this->objectManager);
        $this->dispatcher->addCreation($annotation, $args);

        $this->assertCount(1, $this->dispatcher->getCreations());

        $expectedEvent = new $annotation->class($user);
        $this->sfDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($annotation->event, $expectedEvent);

        $this->dispatcher->dispatchEvents();
    }

    public function testDispatchDeletionEvents()
    {
        $user       = new User();
        $annotation = new Delete();
        $args       = new LifecycleEventArgs($user, $this->objectManager);
        $this->dispatcher->addDeletion($annotation, $args);

        $this->assertCount(1, $this->dispatcher->getDeletions());

        $expectedEvent = new LifecycleEvent($user);
        $this->sfDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(LifecycleEvents::DELETED, $expectedEvent);

        $this->dispatcher->dispatchEvents();
    }

    public function testDispatchDeletionEventsCustom()
    {
        $user       = new User();
        $annotation = new Delete();
        $annotation->event = 'test';
        $annotation->class = MyLifecycleEvent::class;

        $args       = new LifecycleEventArgs($user, $this->objectManager);
        $this->dispatcher->addDeletion($annotation, $args);

        $this->assertCount(1, $this->dispatcher->getDeletions());

        $expectedEvent = new $annotation->class($user);
        $this->sfDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($annotation->event, $expectedEvent);

        $this->dispatcher->dispatchEvents();
    }

    public function testDispatchUpdatesEvents()
    {
        $user       = new User();
        $annotation = new Update();
        $this->dispatcher->addUpdate(
            $annotation,
            $user,
            ['name' => ['foo', 'bar']],
            []
        );

        $this->assertCount(1, $this->dispatcher->getUpdates());

        $expectedEvent = new LifecycleUpdateEvent($user, ['name' => ['foo', 'bar']], []);
        $this->sfDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(LifecycleEvents::UPDATED, $expectedEvent);

        $this->dispatcher->dispatchEvents();
    }

    public function testDispatchUpdatesEventsCustom()
    {
        $user       = new User();
        $annotation = new Update();
        $annotation->event = 'test';
        $annotation->class = MyUpdatedEvent::class;

        $this->dispatcher->addUpdate(
            $annotation,
            $user,
            ['name' => ['foo', 'bar']],
            []
        );

        $this->assertCount(1, $this->dispatcher->getUpdates());

        $expectedEvent = new $annotation->class($user, ['name' => ['foo', 'bar']], []);
        $this->sfDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($annotation->event, $expectedEvent);

        $this->dispatcher->dispatchEvents();
    }

    public function testDispatchPropertyChangeEvents()
    {
        $user       = new User();
        $annotation = new Change();
        $this->dispatcher->addPropertyChange(
            $annotation,
            $user,
            'name',
            'foo',
            'bar'
        );

        $this->assertCount(1, $this->dispatcher->getPropertyChanges());

        $expectedEvent = new LifecyclePropertyChangedEvent($user, 'name', 'foo', 'bar');
        $this->sfDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(LifecycleEvents::PROPERTY_CHANGED, $expectedEvent);

        $this->dispatcher->dispatchEvents();
    }

    public function testDispatchPropertyChangeEventsCustom()
    {
        $user       = new User();
        $annotation = new Change();
        $annotation->event = 'test';
        $annotation->class = MyPropertyChangedEvent::class;

        $this->dispatcher->addPropertyChange(
            $annotation,
            $user,
            'name',
            'foo',
            'bar'
        );

        $this->assertCount(1, $this->dispatcher->getPropertyChanges());

        $expectedEvent = new $annotation->class($user, 'name', 'foo', 'bar');
        $this->sfDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($annotation->event, $expectedEvent);

        $this->dispatcher->dispatchEvents();
    }
    public function testDispatchCollectionChangeEvents()
    {
        $user       = new User();
        $annotation = new Change();
        $deleted = [new User()];
        $inserted = [new User(), new User()];
        $this->dispatcher->addCollectionChange(
            $annotation,
            $user,
            'friends',
            $deleted,
            $inserted
        );

        $this->assertCount(1, $this->dispatcher->getCollectionChanges());

        $expectedEvent = new LifecycleCollectionChangedEvent($user, 'friends', $deleted, $inserted);
        $this->sfDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(LifecycleEvents::COLLECTION_CHANGED, $expectedEvent);

        $this->dispatcher->dispatchEvents();
    }

    public function testDispatchCollectionChangeEventsCustom()
    {
        $user       = new User();
        $annotation = new Change();
        $annotation->event = 'test';
        $annotation->class = MyCollectionChangedEvent::class;

        $deleted    = [new User()];
        $inserted   = [new User(), new User()];
        $this->dispatcher->addCollectionChange(
            $annotation,
            $user,
            'friends',
            $deleted,
            $inserted
        );

        $this->assertCount(1, $this->dispatcher->getCollectionChanges());

        $expectedEvent = new $annotation->class($user, 'friends', $deleted, $inserted);
        $this->sfDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($annotation->event, $expectedEvent);

        $this->dispatcher->dispatchEvents();
    }

    public function testPreAutoDispatch()
    {
        $this->sfDispatcher->expects($this->once())
            ->method('dispatch')
            ->with('w3c.lifecycle.preAutoDispatch', new PreAutoDispatchEvent($this->dispatcher));

        $this->dispatcher->preAutoDispatch();
    }

    public function testAutoDispatch()
    {
        $this->dispatcher->setAutoDispatch(true);
        $this->assertTrue($this->dispatcher->getAutoDispatch());

        $this->dispatcher->setAutoDispatch(false);
        $this->assertFalse($this->dispatcher->getAutoDispatch());
    }
}
