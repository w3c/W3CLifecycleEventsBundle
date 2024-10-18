<?php

namespace W3C\LifecycleEventsBundle\Tests\Services;

use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\Persistence\ObjectManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use W3C\LifecycleEventsBundle\Tests\Services\Fixtures\MySubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use W3C\LifecycleEventsBundle\Attribute\Change;
use W3C\LifecycleEventsBundle\Attribute\Create;
use W3C\LifecycleEventsBundle\Attribute\Delete;
use W3C\LifecycleEventsBundle\Attribute\Update;
use W3C\LifecycleEventsBundle\Event\Definitions\LifecycleEvents;
use W3C\LifecycleEventsBundle\Event\LifecycleCollectionChangedEvent;
use W3C\LifecycleEventsBundle\Event\LifecycleDeletionEvent;
use W3C\LifecycleEventsBundle\Event\LifecycleEvent;
use W3C\LifecycleEventsBundle\Event\LifecyclePropertyChangedEvent;
use W3C\LifecycleEventsBundle\Event\LifecycleUpdateEvent;
use W3C\LifecycleEventsBundle\Event\PreAutoDispatchEvent;
use W3C\LifecycleEventsBundle\Services\LifecycleEventsDispatcher;
use W3C\LifecycleEventsBundle\Tests\Attribute\Fixtures\Person;
use W3C\LifecycleEventsBundle\Tests\Attribute\Fixtures\User;
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

    /**
     * @var ClassMetadata|\PHPUnit_Framework_MockObject_MockObject
     */
    private $classMetadata;

    public function setUp() : void
    {
        parent::setUp();

        $this->sfDispatcher = $this
            ->getMockBuilder(EventDispatcher::class)
            ->enableProxyingToOriginalMethods()
            ->getMock();
        ;
        $this->dispatcher = new LifecycleEventsDispatcher($this->sfDispatcher, true);
        $this->objectManager = $this
            ->getMockBuilder(ObjectManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->classMetadata = $this
            ->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testGetDispatcher()
    {
        $this->assertEquals($this->sfDispatcher, $this->dispatcher->getDispatcher());
    }

    public function testDispatchCreationEvents()
    {
        $user = new User();
        $attribute = new Create();
        $args = new LifecycleEventArgs($user, $this->objectManager);
        $this->dispatcher->addCreation($attribute, $args);

        $this->assertCount(1, $this->dispatcher->getCreations());

        $expectedEvent = new LifecycleEvent($user);

        $this->sfDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($expectedEvent, LifecycleEvents::CREATED)
        ;

        $this->dispatcher->dispatchEvents();
    }

    /**
     * Test that if dispatchEvents is called recursively (could happen if flush happens in a listener),
     * events already fired aren't a second time.
     */
    public function testDispatchCreationEventsRecursive()
    {
        $user       = new User();
        $attribute = new Create();
        $args       = new LifecycleEventArgs($user, $this->objectManager);

        $this->sfDispatcher->addSubscriber(new MySubscriber($this->dispatcher, $attribute, $args));

        $this->dispatcher->addCreation($attribute, $args);

        $this->assertCount(1, $this->dispatcher->getCreations());

        // 2 === 1 addCreation above + 1 in MySubscriber::onCalled
        $this->sfDispatcher->expects($this->exactly(2))
            ->method('dispatch');

        $this->dispatcher->dispatchEvents();
    }

    public function testDispatchCreationEventsCustom()
    {
        $user       = new User();
        $attribute = new Create();
        $attribute->event = 'test';
        $attribute->class = MyLifecycleEvent::class;
        $args       = new LifecycleEventArgs($user, $this->objectManager);

        $this->dispatcher->addCreation($attribute, $args);

        $this->assertCount(1, $this->dispatcher->getCreations());

        $expectedEvent = new $attribute->class($user);
        $this->sfDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($expectedEvent, $attribute->event);

        $this->dispatcher->dispatchEvents();
    }

    public function testDispatchDeletionEvents()
    {
        $user       = new User();
        $attribute = new Delete();
        $args       = new LifecycleEventArgs($user, $this->objectManager);

        $this->objectManager->method('getClassMetadata')->willReturn($this->classMetadata);
        $this->classMetadata->expects($this->once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['name']);
        $this->classMetadata->expects($this->once())
            ->method('getIdentifierValues')
            ->willReturn(['toto']);

        $this->dispatcher->addDeletion($attribute, $args);

        $this->assertCount(1, $this->dispatcher->getDeletions());

        $expectedEvent = new LifecycleDeletionEvent($user, ['name' => 'toto']);
        $this->sfDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($expectedEvent, LifecycleEvents::DELETED);

        $this->dispatcher->dispatchEvents();
    }

    /**
     * Test that if dispatchEvents is called recursively (could happen if flush happens in a listener),
     * events already fired aren't a second time.
     */
    public function testDispatchDeletionEventsRecursive()
    {
        $user       = new User();
        $attribute = new Delete();
        $args       = new LifecycleEventArgs($user, $this->objectManager);

        $this->sfDispatcher->addSubscriber(new MySubscriber($this->dispatcher, $attribute, $args));

        $this->objectManager->method('getClassMetadata')->willReturn($this->classMetadata);
        $this->classMetadata->expects($this->once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['name']);
        $this->classMetadata->expects($this->once())
            ->method('getIdentifierValues')
            ->willReturn(['toto']);

        $this->dispatcher->addDeletion($attribute, $args);

        $this->assertCount(1, $this->dispatcher->getDeletions());

        // 2 === 1 addDeletion above + 1 addCreation in MySubscriber::onCalled
        $this->sfDispatcher->expects($this->exactly(2))
            ->method('dispatch');

        $this->dispatcher->dispatchEvents();
    }

    public function testDispatchDeletionEventsCustom()
    {
        $user       = new User();
        $attribute = new Delete();
        $attribute->event = 'test';
        $attribute->class = MyLifecycleEvent::class;

        $args       = new LifecycleEventArgs($user, $this->objectManager);

        $this->objectManager->method('getClassMetadata')->willReturn($this->classMetadata);
        $this->classMetadata->expects($this->once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['name']);
        $this->classMetadata->expects($this->once())
            ->method('getIdentifierValues')
            ->willReturn(['toto']);

        $this->dispatcher->addDeletion($attribute, $args);

        $this->assertCount(1, $this->dispatcher->getDeletions());

        $expectedEvent = new $attribute->class($user, ['name' => 'toto']);
        $this->sfDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->equalTo($expectedEvent), $attribute->event);

        $this->dispatcher->dispatchEvents();
    }

    public function testDispatchUpdatesEvents()
    {
        $user       = new User();
        $attribute = new Update();
        $this->dispatcher->addUpdate(
            $attribute,
            $user,
            ['name' => ['foo', 'bar']],
            []
        );

        $this->assertCount(1, $this->dispatcher->getUpdates());

        $expectedEvent = new LifecycleUpdateEvent($user, ['name' => ['foo', 'bar']], []);
        $this->sfDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($expectedEvent, LifecycleEvents::UPDATED);

        $this->dispatcher->dispatchEvents();
    }

    /**
     * Test that if dispatchEvents is called recursively (could happen if flush happens in a listener),
     * events already fired aren't a second time.
     */
    public function testDispatchUpdatesEventsRecursive()
    {
        $user       = new User();
        $attribute = new Update();
        $args       = new LifecycleEventArgs($user, $this->objectManager);

        $this->sfDispatcher->addSubscriber(new MySubscriber($this->dispatcher, $attribute, $args));

        $this->dispatcher->addUpdate(
            $attribute,
            $user,
            ['name' => ['foo', 'bar']],
            []
        );

        $this->assertCount(1, $this->dispatcher->getUpdates());

        // 2 === 1 addUpdate above + 1 addCreation in MySubscriber::onCalled
        $this->sfDispatcher->expects($this->exactly(2))
            ->method('dispatch');

        $this->dispatcher->dispatchEvents();
    }

    public function testDispatchUpdatesEventsCustom()
    {
        $user       = new User();
        $attribute = new Update();
        $attribute->event = 'test';
        $attribute->class = MyUpdatedEvent::class;

        $this->dispatcher->addUpdate(
            $attribute,
            $user,
            ['name' => ['foo', 'bar']],
            []
        );

        $this->assertCount(1, $this->dispatcher->getUpdates());

        $expectedEvent = new $attribute->class($user, ['name' => ['foo', 'bar']], []);
        $this->sfDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($expectedEvent, $attribute->event);

        $this->dispatcher->dispatchEvents();
    }

    public function testDispatchPropertyChangeEvents()
    {
        $user       = new User();
        $attribute = new Change();
        $this->dispatcher->addPropertyChange(
            $attribute,
            $user,
            'name',
            'foo',
            'bar'
        );

        $this->assertCount(1, $this->dispatcher->getPropertyChanges());

        $expectedEvent = new LifecyclePropertyChangedEvent($user, 'name', 'foo', 'bar');
        $this->sfDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($expectedEvent, LifecycleEvents::PROPERTY_CHANGED);

        $this->dispatcher->dispatchEvents();
    }

    /**
     * Test that if dispatchEvents is called recursively (could happen if flush happens in a listener),
     * events already fired aren't a second time.
     */
    public function testDispatchPropertyChangeEventsRecursive()
    {
        $user       = new User();
        $attribute = new Change();
        $args       = new LifecycleEventArgs($user, $this->objectManager);

        $this->sfDispatcher->addSubscriber(new MySubscriber($this->dispatcher, $attribute, $args));

        $this->dispatcher->addPropertyChange(
            $attribute,
            $user,
            'name',
            'foo',
            'bar'
        );

        $this->assertCount(1, $this->dispatcher->getPropertyChanges());

        // 2 === 1 addPropertyChange above + 1 addCreation in MySubscriber::onCalled
        $this->sfDispatcher->expects($this->exactly(2))
            ->method('dispatch');

        $this->dispatcher->dispatchEvents();
    }

    public function testDispatchPropertyChangeEventsCustom()
    {
        $user       = new User();
        $attribute = new Change();
        $attribute->event = 'test';
        $attribute->class = MyPropertyChangedEvent::class;

        $this->dispatcher->addPropertyChange(
            $attribute,
            $user,
            'name',
            'foo',
            'bar'
        );

        $this->assertCount(1, $this->dispatcher->getPropertyChanges());

        $expectedEvent = new $attribute->class($user, 'name', 'foo', 'bar');
        $this->sfDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($expectedEvent, $attribute->event);

        $this->dispatcher->dispatchEvents();
    }
    public function testDispatchCollectionChangeEvents()
    {
        $user       = new User();
        $attribute = new Change();
        $deleted = [new User()];
        $inserted = [new User(), new User()];
        $this->dispatcher->addCollectionChange(
            $attribute,
            $user,
            'friends',
            $deleted,
            $inserted
        );

        $this->assertCount(1, $this->dispatcher->getCollectionChanges());

        $expectedEvent = new LifecycleCollectionChangedEvent($user, 'friends', $deleted, $inserted);
        $this->sfDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($expectedEvent, LifecycleEvents::COLLECTION_CHANGED);

        $this->dispatcher->dispatchEvents();
    }

    /**
     * Test that if dispatchEvents is called recursively (could happen if flush happens in a listener),
     * events already fired aren't a second time.
     */
    public function testDispatchCollectionChangeEventsRecursive()
    {
        $user       = new User();
        $attribute = new Change();
        $args       = new LifecycleEventArgs($user, $this->objectManager);

        $this->sfDispatcher->addSubscriber(new MySubscriber($this->dispatcher, $attribute, $args));

        $deleted  = [new User()];
        $inserted = [new User(), new User()];
        $this->dispatcher->addCollectionChange(
            $attribute,
            $user,
            'friends',
            $deleted,
            $inserted
        );

        $this->assertCount(1, $this->dispatcher->getCollectionChanges());

        // 2 === 1 addCollectionChange above + 1 addCreation in MySubscriber::onCalled
        $this->sfDispatcher->expects($this->exactly(2))
            ->method('dispatch');

        $this->dispatcher->dispatchEvents();
    }

    public function testDispatchCollectionChangeEventsCustom()
    {
        $user       = new User();
        $attribute = new Change();
        $attribute->event = 'test';
        $attribute->class = MyCollectionChangedEvent::class;

        $deleted    = [new User()];
        $inserted   = [new User(), new User()];
        $this->dispatcher->addCollectionChange(
            $attribute,
            $user,
            'friends',
            $deleted,
            $inserted
        );

        $this->assertCount(1, $this->dispatcher->getCollectionChanges());

        $expectedEvent = new $attribute->class($user, 'friends', $deleted, $inserted);
        $this->sfDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($expectedEvent, $attribute->event);

        $this->dispatcher->dispatchEvents();
    }

    public function testPreAutoDispatch()
    {
        $this->sfDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(new PreAutoDispatchEvent($this->dispatcher),'w3c.lifecycle.preAutoDispatch');

        $this->dispatcher->preAutoDispatch();
    }

    public function testAutoDispatch()
    {
        $this->dispatcher->setAutoDispatch(true);
        $this->assertTrue($this->dispatcher->getAutoDispatch());

        $this->dispatcher->setAutoDispatch(false);
        $this->assertFalse($this->dispatcher->getAutoDispatch());
    }

    public function testGetUpdate()
    {
        $user       = new User();
        $attribute = new Update();

        $this->assertNull($this->dispatcher->getUpdate($user));

        $this->dispatcher->addUpdate(
            $attribute,
            new User(),
            ['name' => ['foo', 'bar']],
            []
        );

        $this->dispatcher->addUpdate(
            $attribute,
            $user,
            ['name' => ['foo', 'bar']],
            []
        );

        $this->dispatcher->addUpdate(
            $attribute,
            new User(),
            ['name' => ['foo', 'bar']],
            []
        );

        $this->assertSame([1, [$attribute, $user, ['name' => ['foo', 'bar']], []]], $this->dispatcher->getUpdate($user));
    }

    public function testAddUpdate()
    {
        $user       = new User();
        $attribute = new Update();

        $this->assertNull($this->dispatcher->getUpdate($user));

        $this->dispatcher->addUpdate(
            $attribute,
            new User(),
            ['name' => ['old' => 'foo', 'new' => 'bar']],
            []
        );

        $this->dispatcher->addUpdate(
            $attribute,
            $user,
            ['name' => ['old' => 'foo', 'new' => 'bar']],
            []
        );

        $this->assertCount(2, $this->dispatcher->getUpdates());

        $this->dispatcher->addUpdate(
            $attribute,
            $user,
            [],
            ['friends' => ['deleted' => 'foo', 'inserted' => 'bar']]
        );

        $this->assertCount(2, $this->dispatcher->getUpdates());
        $this->assertSame([
                1,
                [
                    $attribute,
                    $user,
                    ['name' => ['old' => 'foo', 'new' => 'bar']],
                    ['friends' => ['deleted' => 'foo', 'inserted' => 'bar']]
                ]
            ], $this->dispatcher->getUpdate($user));

        $this->dispatcher->addUpdate(
            $attribute,
            $user,
            ['foo' => ['old' => 'a', 'new' => 'b']],
            []
        );

        $this->assertCount(2, $this->dispatcher->getUpdates());
        $this->assertSame([
            1,
            [
                $attribute,
                $user,
                ['name' => ['old' => 'foo', 'new' => 'bar'], 'foo' => ['old' => 'a', 'new' => 'b']],
                ['friends' => ['deleted' => 'foo', 'inserted' => 'bar']]
            ]
        ], $this->dispatcher->getUpdate($user));
    }

    public function testGetCollectionChange()
    {
        $father     = new Person();
        $son1       = new Person();
        $son2       = new Person();
        $attribute = new Change();

        $this->assertNull($this->dispatcher->getCollectionChange($father, 'sons'));

        $this->dispatcher->addCollectionChange(
            $attribute,
            new Person(),
            'sons',
            [new Person(), new Person()],
            [new Person()]
        );

        $this->dispatcher->addCollectionChange(
            $attribute,
            $father,
            'sons',
            [$son1],
            [$son2]
        );

        $this->dispatcher->addCollectionChange(
            $attribute,
            new Person(),
            'foo',
            [],
            ['bar']
        );

        $this->assertSame([1, [$attribute, $father, 'sons', [$son1], [$son2]]],
            $this->dispatcher->getCollectionChange($father, 'sons'));
    }

    public function testAddCollectionUpdate()
    {
        $father     = new Person();
        $son1       = new Person();
        $son2       = new Person();
        $son3       = new Person();
        $attribute = new Change();

        $this->assertNull($this->dispatcher->getCollectionChange($father, 'sons'));

        $this->dispatcher->addCollectionChange(
            $attribute,
            new Person(),
            'sons',
            [new Person(), new Person()],
            [new Person()]
        );

        $this->dispatcher->addCollectionChange(
            $attribute,
            $father,
            'sons',
            [],
            [$son2]
        );

        $this->assertCount(2, $this->dispatcher->getCollectionChanges());

        $this->dispatcher->addCollectionChange(
            $attribute,
            $father,
            'sons',
            [$son1],
            [$son3]
        );

        $this->assertCount(2, $this->dispatcher->getCollectionChanges());
        $this->assertSame([
            1,
            [
                $attribute,
                $father,
                'sons',
                [$son1],
                [$son2, $son3]
            ]
        ], $this->dispatcher->getCollectionChange($father, 'sons'));

        $this->dispatcher->addCollectionChange(
            $attribute,
            $father,
            'foo',
            [$son1],
            [$son3]
        );

        $this->assertCount(3, $this->dispatcher->getCollectionChanges());
        $this->assertSame([
            1,
            [
                $attribute,
                $father,
                'sons',
                [$son1],
                [$son2, $son3]
            ]
        ], $this->dispatcher->getCollectionChange($father, 'sons'));
    }
}
