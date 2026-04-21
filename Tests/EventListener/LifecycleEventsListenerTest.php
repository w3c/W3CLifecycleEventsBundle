<?php

namespace W3C\LifecycleEventsBundle\Tests\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\OneToManyAssociationMapping;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\UnitOfWork;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use W3C\LifecycleEventsBundle\Attribute\Update;
use W3C\LifecycleEventsBundle\EventListener\LifecycleEventsListener;
use W3C\LifecycleEventsBundle\Services\AttributeGetter;
use W3C\LifecycleEventsBundle\Services\LifecycleEventsDispatcher;
use W3C\LifecycleEventsBundle\Tests\Attribute\Fixtures\User;
use W3C\LifecycleEventsBundle\Tests\EventListener\Fixtures\UserClassUpdateCollection;
use W3C\LifecycleEventsBundle\Tests\EventListener\Fixtures\UserClassUpdateIgnoreCollection;
use W3C\LifecycleEventsBundle\Tests\EventListener\Fixtures\UserClassUpdateIgnoreNoCollection;
use W3C\LifecycleEventsBundle\Tests\EventListener\Fixtures\UserClassUpdateNoCollection;
use W3C\LifecycleEventsBundle\Tests\EventListener\Fixtures\UserNoAnnotation;

/**
 * @author Jean-Guilhem Rouel <jean-gui@w3.org>
 */
class LifecycleEventsListenerTest extends TestCase
{
    private LifecycleEventsListener $listener;
    private LifecycleEventsDispatcher $dispatcher;
    private EntityManagerInterface $manager;
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

        $this->listener = new LifecycleEventsListener($this->dispatcher, new AttributeGetter());
    }

    public function testPostPersist()
    {
        $user = new User();
        $event = new PostPersistEventArgs($user, $this->manager);

        $this->dispatcher->expects($this->once())
            ->method('addCreation');

        $this->manager
            ->method('getClassMetadata')
            ->with($user::class)
            ->willReturn($this->classMetadata);

        $this->classMetadata
            ->method('getAssociationMappings')
            ->willReturn(['friends' => []]);

        $this->classMetadata
            ->method('getName')
            ->willReturn($user::class);

        $this->listener->postPersist($event);
    }

    public function testPostPersistNoCreationAnnotation()
    {
        $user  = new UserNoAnnotation();
        $event = new PostPersistEventArgs($user, $this->manager);

        $this->dispatcher->expects($this->never())
            ->method('addCreation');

        $this->manager
            ->method('getClassMetadata')
            ->with($user::class)
            ->willReturn($this->classMetadata);

        $this->classMetadata
            ->method('getAssociationMappings')
            ->willReturn(['friends' => []]);

        $this->classMetadata
            ->method('getName')
            ->willReturn($user::class);

        $this->listener->postPersist($event);
    }

    public function testPreRemove()
    {
        $user  = new User();
        $event = new PreRemoveEventArgs($user, $this->manager);

        $this->dispatcher->expects($this->once())
            ->method('addDeletion');

        $this->manager
            ->method('getClassMetadata')
            ->with($user::class)
            ->willReturn($this->classMetadata);

        $this->classMetadata
            ->method('getAssociationMappings')
            ->willReturn(['friends' => []]);

        $this->classMetadata
            ->method('getName')
            ->willReturn($user::class);

        $this->listener->preRemove($event);
    }

    public function testPreRemoveNoAnnotation()
    {
        $user  = new UserNoAnnotation();
        $event = new PreRemoveEventArgs($user, $this->manager);

        $this->dispatcher->expects($this->never())
            ->method('addDeletion');

        $this->manager
            ->method('getClassMetadata')
            ->with($user::class)
            ->willReturn($this->classMetadata);

        $this->classMetadata
            ->method('getAssociationMappings')
            ->willReturn([]);

        $this->classMetadata
            ->method('getName')
            ->willReturn($user::class);

        $this->listener->preRemove($event);
    }

    public function testPreSoftDelete()
    {
        $user  = new User();
        $event = new PreRemoveEventArgs($user, $this->manager);

        $this->dispatcher->expects($this->once())
            ->method('addDeletion');

        $this->manager
            ->method('getClassMetadata')
            ->with($user::class)
            ->willReturn($this->classMetadata);

        $this->classMetadata
            ->method('getAssociationMappings')
            ->willReturn(['friends' => []]);

        $this->classMetadata
            ->method('getName')
            ->willReturn($user::class);

        $this->listener->preSoftDelete($event);
    }

    public function testPreSoftDeleteNoAnnotation()
    {
        $user  = new UserNoAnnotation();
        $event = new PreRemoveEventArgs($user, $this->manager);

        $this->dispatcher->expects($this->never())
            ->method('addDeletion');

        $this->manager
            ->method('getClassMetadata')
            ->with($user::class)
            ->willReturn($this->classMetadata);

        $this->classMetadata
            ->method('getName')
            ->willReturn($user::class);

        $this->classMetadata
            ->method('getAssociationMappings')
            ->willReturn([]);

        $this->listener->preSoftDelete($event);
    }

    public function testPreUpdateNoCollection()
    {
        $user = new UserClassUpdateNoCollection();
        $changeSet = ['name' => [null, 'foo']];
        $event = new PreUpdateEventArgs($user, $this->manager, $changeSet);

        $reflection = new \ReflectionClass($user);
        $attribute = $reflection->getAttributes(Update::class)[0]->newInstance();

        $this->manager
            ->method('getClassMetadata')
            ->with($user::class)
            ->willReturn($this->classMetadata);

        $this->classMetadata
            ->method('getName')
            ->willReturn($user::class);

        $this->classMetadata
            ->method('getReflectionProperty')
            ->with('name')
            ->willReturn(new \ReflectionProperty($user, 'name'));

        $this->dispatcher->expects($this->once())
            ->method('addUpdate')
            ->with($attribute, $user, ['name' => ['old' => null, 'new' => 'foo']], []);

        $this->listener->preUpdate($event);
    }

    public function testPreUpdateIgnoreNoCollection()
    {
        $user      = new UserClassUpdateIgnoreNoCollection();
        $changeSet = ['name' => [null, 'foo']];
        $event     = new PreUpdateEventArgs($user, $this->manager, $changeSet);

        $reflection = new \ReflectionClass($user);
        $attribute = $reflection->getAttributes(Update::class)[0]->newInstance();

        $this->manager
            ->method('getClassMetadata')
            ->with($user::class)
            ->willReturn($this->classMetadata);

        $this->classMetadata
            ->method('getName')
            ->willReturn($user::class);

        $this->classMetadata
            ->method('getReflectionProperty')
            ->with('name')
            ->willReturn(new \ReflectionProperty($user, 'name'));

        $this->dispatcher->expects($this->once())
            ->method('addUpdate')
            ->with($attribute, $user, [], []);

        $this->listener->preUpdate($event);
    }

    public function testPreUpdateIgnoreCollection()
    {
        $user      = new UserClassUpdateIgnoreCollection();
        $changeSet = [];
        $event     = new PreUpdateEventArgs($user, $this->manager, $changeSet);

        $reflection = new \ReflectionClass($user);
        $attribute = $reflection->getAttributes(Update::class)[0]->newInstance();

        $mapping = new OneToManyAssociationMapping('friends', User::class, User::class);
        $mapping->mappedBy = 'friend';

        $pc = new PersistentCollection(
            $this->manager,
            $this->classMetadata,
            new ArrayCollection([new User(), new User()]),
        );
        $pc->setOwner(new User(), $mapping);
        $pc->takeSnapshot();
        $pc->add(new User);

        $uow = $this
            ->getMockBuilder(UnitOfWork::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getScheduledCollectionUpdates'])
            ->getMock();

        $this->manager->method('getUnitOfWork')->willReturn($uow);
        $uow->method('getScheduledCollectionUpdates')->willReturn([$pc]);

        $this->manager
            ->method('getClassMetadata')
            ->with($user::class)
            ->willReturn($this->classMetadata);

        $this->classMetadata
            ->method('getName')
            ->willReturn($user::class);

        $this->classMetadata
            ->method('getReflectionProperty')
            ->with('friends')
            ->willReturn(new \ReflectionProperty($user, 'friends'));

        $this->dispatcher->expects($this->once())
            ->method('addUpdate')
            ->with($attribute, $user, [], []);

        $this->listener->preUpdate($event);
    }

    public function testPreUpdateCollection()
    {
        $user      = new UserClassUpdateCollection();
        $changeSet = [];
        $event     = new PreUpdateEventArgs($user, $this->manager, $changeSet);

        $reflection = new \ReflectionClass($user);
        $attribute = $reflection->getAttributes(Update::class)[0]->newInstance();

        $mapping = new OneToManyAssociationMapping('friends', User::class, User::class);
        $mapping->mappedBy = 'friend';

        $user1 = new User();
        $user2 = new User();
        $user3 = new User();

        $pc = new PersistentCollection(
            $this->manager,
            $this->classMetadata,
            new ArrayCollection([$user1, $user2]),
        );
        $pc->setOwner($user, $mapping);
        $pc->takeSnapshot();
        $pc->removeElement($user1);
        $pc->removeElement($user2);
        $pc->add($user3);

        $uow = $this
            ->getMockBuilder(UnitOfWork::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getScheduledCollectionUpdates'])
            ->getMock();

        $this->manager->method('getUnitOfWork')->willReturn($uow);
        $uow->method('getScheduledCollectionUpdates')->willReturn([$pc]);

        $this->assertEquals([$user1, $user2], $pc->getDeleteDiff());
        $this->assertEquals([$user3], $pc->getInsertDiff());

        $this->dispatcher->expects($this->once())
            ->method('addUpdate')
            ->with($attribute, $user, [], ['friends' => ['deleted' => [$user1, $user2], 'inserted' => [$user3]]]);

        $this->manager
            ->method('getClassMetadata')
            ->with($user::class)
            ->willReturn($this->classMetadata);

        $this->classMetadata
            ->method('getName')
            ->willReturn($user::class);

        $this->classMetadata
            ->method('getReflectionProperty')
            ->with('friends')
            ->willReturn(new \ReflectionProperty($user, 'friends'));

        $this->listener->preUpdate($event);
    }

    public function testPreUpdateCollectionOtherEntity()
    {
        $user      = new UserClassUpdateCollection();
        $user2     = new UserNoAnnotation();
        $changeSet = [];
        $event     = new PreUpdateEventArgs($user, $this->manager, $changeSet);

        $reflection = new \ReflectionClass($user);
        $attribute = $reflection->getAttributes(Update::class)[0]->newInstance();

        $mapping = new OneToManyAssociationMapping('friends', User::class, User::class);
        $mapping->mappedBy = 'friend';

        $pc = new PersistentCollection($this->manager, $this->classMetadata, new ArrayCollection());
        $pc->setOwner($user2, $mapping);

        $uow = $this
            ->getMockBuilder(UnitOfWork::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getScheduledCollectionUpdates'])
            ->getMock();

        $this->manager
            ->method('getClassMetadata')
            ->with($user::class)
            ->willReturn($this->classMetadata);

        $this->classMetadata
            ->method('getName')
            ->willReturn($user::class);

        $this->manager->method('getUnitOfWork')->willReturn($uow);
        $uow->method('getScheduledCollectionUpdates')->willReturn([$pc]);

        $this->dispatcher->expects($this->once())
            ->method('addUpdate')
            ->with($attribute, $user, [], []);

        $this->listener->preUpdate($event);
    }

    public function testPreUpdateCollectionDoesNotExist()
    {
        $this->expectException(\ReflectionException::class);

        $user      = new UserClassUpdateCollection();
        $changeSet = [];
        $event     = new PreUpdateEventArgs($user, $this->manager, $changeSet);

        $reflection = new \ReflectionClass($user);
        $attribute = $reflection->getAttributes(Update::class)[0]->newInstance();

        $mapping = new OneToManyAssociationMapping('foo', User::class, User::class);
        $mapping->mappedBy = 'friend';

        $pc = new PersistentCollection($this->manager, $this->classMetadata, new ArrayCollection());
        $pc->setOwner($user, $mapping);

        $uow = $this
            ->getMockBuilder(UnitOfWork::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getScheduledCollectionUpdates'])
            ->getMock();

        $this->manager->method('getUnitOfWork')->willReturn($uow);
        $uow->method('getScheduledCollectionUpdates')->willReturn([$pc]);

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
            ->method('addUpdate')
            ->with($attribute, $user, [], null);

        $this->listener->preUpdate($event);
    }
}
