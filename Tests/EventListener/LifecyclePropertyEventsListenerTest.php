<?php

namespace W3C\LifecycleEventsBundle\Tests\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\OneToManyAssociationMapping;
use Doctrine\ORM\Mapping\PropertyAccessors\PropertyAccessorFactory;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\UnitOfWork;
use PHPUnit\Framework\TestCase;
use W3C\LifecycleEventsBundle\Attribute\Change;
use W3C\LifecycleEventsBundle\EventListener\LifecyclePropertyEventsListener;
use W3C\LifecycleEventsBundle\Services\AttributeGetter;
use W3C\LifecycleEventsBundle\Services\LifecycleEventsDispatcher;
use W3C\LifecycleEventsBundle\Tests\Attribute\Fixtures\User;
use W3C\LifecycleEventsBundle\Tests\EventListener\Fixtures\OtherEntity;
use W3C\LifecycleEventsBundle\Tests\EventListener\Fixtures\UserChange;
use W3C\LifecycleEventsBundle\Tests\EventListener\Fixtures\UserClassUpdateCollection;
use W3C\LifecycleEventsBundle\Tests\EventListener\Fixtures\UserNoAnnotation;

/**
 * @author Jean-Guilhem Rouel <jean-gui@w3.org>
 */
class LifecyclePropertyEventsListenerTest extends TestCase
{
    private LifecyclePropertyEventsListener $listener;
    private LifecycleEventsDispatcher $dispatcher;
    private EntityManagerInterface $manager;
    private UnitOfWork $uow;
    private ClassMetadata $classMetadata;

    public function setUp() : void
    {
        parent::setUp();

        $this->dispatcher = $this
            ->getMockBuilder(LifecycleEventsDispatcher::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->manager = $this
            ->getMockBuilder(EntityManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->classMetadata = $this
            ->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->uow = $this
            ->getMockBuilder(UnitOfWork::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getScheduledCollectionUpdates'])
            ->getMock();

        $this->listener = new LifecyclePropertyEventsListener($this->dispatcher, new AttributeGetter());
    }

    private function createScheduledCollection(object $owner, string $fieldName, array $deleted, array $inserted): PersistentCollection
    {
        $mapping = new OneToManyAssociationMapping($fieldName, User::class, User::class);
        $mapping->mappedBy = 'friend';

        $collection = new PersistentCollection(
            $this->manager,
            $this->classMetadata,
            new ArrayCollection($deleted)
        );

        $collection->setOwner($owner, $mapping);
        $collection->takeSnapshot();

        foreach ($deleted as $item) {
            $collection->removeElement($item);
        }

        foreach ($inserted as $item) {
            $collection->add($item);
        }

        return $collection;
    }

    public function testPreUpdateProperty()
    {
        $user = new UserChange();
        $changeSet = ['name' => ['foo', 'bar']];
        $event = new PreUpdateEventArgs($user, $this->manager, $changeSet);

        $reflection = new \ReflectionProperty(get_class($user), 'name');
        $attribute = $reflection->getAttributes(Change::class)[0]->newInstance();

        $this->manager->method('getUnitOfWork')->willReturn($this->uow);
        $this->uow->method('getScheduledCollectionUpdates')->willReturn([]);

        $this->manager
            ->method('getClassMetadata')
            ->with($user::class)
            ->willReturn($this->classMetadata);

        $this->classMetadata
            ->method('getName')
            ->willReturn($user::class);

        $this->classMetadata
            ->method('getPropertyAccessor')
            ->with('name')
            ->willReturn(PropertyAccessorFactory::createPropertyAccessor($user::class, 'name'));

        $this->dispatcher->expects($this->once())
            ->method('addPropertyChange')
            ->with($attribute, $user, 'name', 'foo', 'bar');

        $this->listener->preUpdate($event);
    }

    public function testPreUpdateCollection()
    {
        $user      = new UserChange();
        $changeSet = [];
        $event     = new PreUpdateEventArgs($user, $this->manager, $changeSet);

        $reflection = PropertyAccessorFactory::createPropertyAccessor($user::class, 'name')->getUnderlyingReflector();
        $attribute = $reflection->getAttributes(Change::class)[0]->newInstance();

        $deleted = [new User(), new User()];
        $inserted = [new User()];

        $pc = $this->createScheduledCollection($user, 'friends', $deleted, $inserted);

        $this->manager->method('getUnitOfWork')->willReturn($this->uow);
        $this->uow->method('getScheduledCollectionUpdates')->willReturn([$pc]);

        $this->manager
            ->method('getClassMetadata')
            ->with($user::class)
            ->willReturn($this->classMetadata);

        $this->classMetadata
            ->method('getName')
            ->willReturn($user::class);

        $this->classMetadata
            ->method('getPropertyAccessor')
            ->with('friends')
            ->willReturn(PropertyAccessorFactory::createPropertyAccessor($user::class, 'friends'));

        $this->dispatcher->expects($this->once())
            ->method('addCollectionChange')
            ->with($attribute, $user, 'friends', $deleted, $inserted);

        $this->listener->preUpdate($event);
    }

    public function testPreUpdateCollectionFieldDoesNotExist()
    {
        $this->expectException(\ReflectionException::class);

        $user      = new UserChange();
        $changeSet = [];
        $event     = new PreUpdateEventArgs($user, $this->manager, $changeSet);

        $deleted = [new User(), new User()];
        $inserted = [new User()];

        $pc = $this->createScheduledCollection($user, 'foo', $deleted, $inserted);

        $this->manager->method('getUnitOfWork')->willReturn($this->uow);
        $this->uow->method('getScheduledCollectionUpdates')->willReturn([$pc]);

        $this->manager
            ->method('getClassMetadata')
            ->with($user::class)
            ->willReturn($this->classMetadata);

        $this->classMetadata
            ->method('getName')
            ->willReturn($user::class);

        $this->classMetadata
            ->method('getReflectionProperty')
            ->with('foo')
            ->willReturn(null);

        $this->dispatcher->expects($this->never())
            ->method('addCollectionChange');

        $this->listener->preUpdate($event);
    }

    public function testPreUpdateCollectionFieldNotMonitored()
    {
        $user      = new UserClassUpdateCollection();
        $changeSet = [];
        $event     = new PreUpdateEventArgs($user, $this->manager, $changeSet);

        $deleted = [new User(), new User()];
        $inserted = [new User()];

        $pc = $this->createScheduledCollection($user, 'friends', $deleted, $inserted);

        $this->manager->method('getUnitOfWork')->willReturn($this->uow);
        $this->uow->method('getScheduledCollectionUpdates')->willReturn([$pc]);

        $this->manager
            ->method('getClassMetadata')
            ->with($user::class)
            ->willReturn($this->classMetadata);

        $this->classMetadata
            ->method('getName')
            ->willReturn($user::class);

        $this->classMetadata
            ->method('getPropertyAccessor')
            ->with('friends')
            ->willReturn(PropertyAccessorFactory::createPropertyAccessor($user::class, 'friends'));

        $this->dispatcher->expects($this->never())
            ->method('addCollectionChange');

        $this->listener->preUpdate($event);
    }

    public function testPreUpdateCollectionOtherEntity()
    {
        $user      = new UserChange();
        $user2      = new UserNoAnnotation();
        $changeSet = [];
        $event     = new PreUpdateEventArgs($user, $this->manager, $changeSet);

        $deleted = [new User(), new User()];
        $inserted = [new User()];

        $pc = $this->createScheduledCollection($user2, 'friends', $deleted, $inserted);

        $this->manager->method('getUnitOfWork')->willReturn($this->uow);
        $this->uow->method('getScheduledCollectionUpdates')->willReturn([$pc]);

        $this->dispatcher->expects($this->never())
            ->method('addCollectionChange');

        $this->listener->preUpdate($event);
    }

    public function testPreUpdateCollectionOtherClass()
    {
        $user      = new UserChange();
        $user2     = new OtherEntity();
        $changeSet = [];
        $event     = new PreUpdateEventArgs($user, $this->manager, $changeSet);

        $deleted = [new User(), new User()];
        $inserted = [new User()];

        $pc = $this->createScheduledCollection($user2, 'foo', $deleted, $inserted);

        $this->manager->method('getUnitOfWork')->willReturn($this->uow);
        $this->uow->method('getScheduledCollectionUpdates')->willReturn([$pc]);

        $this->dispatcher->expects($this->never())
            ->method('addCollectionChange');

        $this->listener->preUpdate($event);
    }
}
