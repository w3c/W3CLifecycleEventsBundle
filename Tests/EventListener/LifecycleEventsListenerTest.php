<?php

namespace W3C\LifecycleEventsBundle\Tests\EventListener;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\UnitOfWork;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use W3C\LifecycleEventsBundle\Annotation\Update;
use W3C\LifecycleEventsBundle\EventListener\LifecycleEventsListener;
use W3C\LifecycleEventsBundle\Services\AnnotationGetter;
use W3C\LifecycleEventsBundle\Services\LifecycleEventsDispatcher;
use W3C\LifecycleEventsBundle\Tests\Annotation\Fixtures\User;
use W3C\LifecycleEventsBundle\Tests\EventListener\Fixtures\UserClassUpdateCollection;
use W3C\LifecycleEventsBundle\Tests\EventListener\Fixtures\UserClassUpdateIgnoreCollection;
use W3C\LifecycleEventsBundle\Tests\EventListener\Fixtures\UserClassUpdateIgnoreNoCollection;
use W3C\LifecycleEventsBundle\Tests\EventListener\Fixtures\UserClassUpdateNoCollection;
use W3C\LifecycleEventsBundle\Tests\EventListener\Fixtures\UserNoAnnotation;

/**
 * @author Jean-Guilhem Rouel <jean-gui@w3.org>
 */
class LifecycleEventsListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var LifecycleEventsListener
     */
    private $listener;

    /**
     * @var LifecycleEventsDispatcher|MockObject
     */
    private $dispatcher;

    /**
     * @var Reader
     */
    private $reader;

    /**
     * @var EntityManagerInterface|MockObject
     */
    private $manager;

    /**
     * @var ClassMetadata|MockObject
     */
    private $classMetadata;

    public function setUp()
    {
        parent::setUp();

        $loader = require __DIR__ . '/../../vendor/autoload.php';
        AnnotationRegistry::registerLoader(array($loader, 'loadClass'));

        $this->reader = new AnnotationReader();

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

        $this->listener = new LifecycleEventsListener($this->dispatcher, new AnnotationGetter($this->reader));
    }

    public function testPostPersist()
    {
        $user = new User();
        $event = new LifecycleEventArgs($user, $this->manager);

        $this->dispatcher->expects($this->once())
            ->method('addCreation');

        $this->manager
            ->method('getClassMetadata')
            ->with(ClassUtils::getRealClass(get_class($user)))
            ->willReturn($this->classMetadata);

        $this->classMetadata
            ->method('getAssociationMappings')
            ->willReturn(['friends' => []]);

        $this->listener->postPersist($event);
    }

    public function testPostPersistNoCreationAnnotation()
    {
        $user  = new UserNoAnnotation();
        $event = new LifecycleEventArgs($user, $this->manager);

        $this->dispatcher->expects($this->never())
            ->method('addCreation');

        $this->manager
            ->method('getClassMetadata')
            ->with(ClassUtils::getRealClass(get_class($user)))
            ->willReturn($this->classMetadata);

        $this->classMetadata
            ->method('getAssociationMappings')
            ->willReturn(['friends' => []]);

        $this->listener->postPersist($event);
    }

    public function testPreRemove()
    {
        $user  = new User();
        $event = new LifecycleEventArgs($user, $this->manager);

        $this->dispatcher->expects($this->once())
            ->method('addDeletion');

        $this->manager
            ->method('getClassMetadata')
            ->with(ClassUtils::getRealClass(get_class($user)))
            ->willReturn($this->classMetadata);

        $this->classMetadata
            ->method('getAssociationMappings')
            ->willReturn(['friends' => []]);

        $this->listener->preRemove($event);
    }

    public function testPreRemoveNoAnnotation()
    {
        $user  = new UserNoAnnotation();
        $event = new LifecycleEventArgs($user, $this->manager);

        $this->dispatcher->expects($this->never())
            ->method('addDeletion');

        $this->manager
            ->method('getClassMetadata')
            ->with(ClassUtils::getRealClass(get_class($user)))
            ->willReturn($this->classMetadata);

        $this->classMetadata
            ->method('getAssociationMappings')
            ->willReturn([]);

        $this->listener->preRemove($event);
    }

    public function testPreSoftDelete()
    {
        $user  = new User();
        $event = new LifecycleEventArgs($user, $this->manager);

        $this->dispatcher->expects($this->once())
            ->method('addDeletion');

        $this->manager
            ->method('getClassMetadata')
            ->with(ClassUtils::getRealClass(get_class($user)))
            ->willReturn($this->classMetadata);

        $this->classMetadata
            ->method('getAssociationMappings')
            ->willReturn(['friends' => []]);

        $this->listener->preSoftDelete($event);
    }

    public function testPreSoftDeleteNoAnnotation()
    {
        $user  = new UserNoAnnotation();
        $event = new LifecycleEventArgs($user, $this->manager);

        $this->dispatcher->expects($this->never())
            ->method('addDeletion');

        $this->manager
            ->method('getClassMetadata')
            ->with(ClassUtils::getRealClass(get_class($user)))
            ->willReturn($this->classMetadata);

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

        /** @var Update $annotation */
        $annotation = $this->reader->getClassAnnotation(
            new \ReflectionClass($user),
            Update::class
        );

        $this->manager
            ->method('getClassMetadata')
            ->with(ClassUtils::getRealClass(get_class($user)))
            ->willReturn($this->classMetadata);

        $this->classMetadata
            ->method('getReflectionProperty')
            ->with('name')
            ->willReturn(new \ReflectionProperty($user, 'name'));

        $this->dispatcher->expects($this->once())
            ->method('addUpdate')
            ->with($annotation, $user, ['name' => ['old' => null, 'new' => 'foo']], []);

        $this->listener->preUpdate($event);
    }

    public function testPreUpdateIgnoreNoCollection()
    {
        $user      = new UserClassUpdateIgnoreNoCollection();
        $changeSet = ['name' => [null, 'foo']];
        $event     = new PreUpdateEventArgs($user, $this->manager, $changeSet);

        /** @var Update $annotation */
        $annotation = $this->reader->getClassAnnotation(
            new \ReflectionClass($user),
            Update::class
        );

        $this->manager
            ->method('getClassMetadata')
            ->with(ClassUtils::getRealClass(get_class($user)))
            ->willReturn($this->classMetadata);

        $this->classMetadata
            ->method('getReflectionProperty')
            ->with('name')
            ->willReturn(new \ReflectionProperty($user, 'name'));

        $this->dispatcher->expects($this->once())
            ->method('addUpdate')
            ->with($annotation, $user, [], []);

        $this->listener->preUpdate($event);
    }

    public function testPreUpdateIgnoreCollection()
    {
        $user      = new UserClassUpdateIgnoreCollection();
        $changeSet = [];
        $event     = new PreUpdateEventArgs($user, $this->manager, $changeSet);

        /** @var Update $annotation */
        $annotation = $this->reader->getClassAnnotation(
            new \ReflectionClass($user),
            Update::class
        );

        $uow = $this
            ->getMockBuilder(UnitOfWork::class)
            ->disableOriginalConstructor()
            ->setMethods(['getScheduledCollectionUpdates', 'getOwner', 'getMapping', 'getDeleteDiff', 'getInsertDiff'])
            ->getMock();

        $this->manager->method('getUnitOfWork')->willReturn($uow);
        $uow->method('getScheduledCollectionUpdates')->willReturn([$uow]);
        $uow->method('getOwner')->willReturn($user);
        $uow->method('getMapping')->willReturn(['fieldName' => 'friends']);
        $deleted = [new User(), new User()];
        $uow->method('getDeleteDiff')->willReturn($deleted);
        $inserted = [new User()];
        $uow->method('getInsertDiff')->willReturn($inserted);

        $this->manager
            ->method('getClassMetadata')
            ->with(ClassUtils::getRealClass(get_class($user)))
            ->willReturn($this->classMetadata);

        $this->classMetadata
            ->method('getReflectionProperty')
            ->with('friends')
            ->willReturn(new \ReflectionProperty($user, 'friends'));

        $this->dispatcher->expects($this->once())
            ->method('addUpdate')
            ->with($annotation, $user, [], []);

        $this->listener->preUpdate($event);
    }

    public function testPreUpdateCollection()
    {
        $user      = new UserClassUpdateCollection();
        $changeSet = [];
        $event     = new PreUpdateEventArgs($user, $this->manager, $changeSet);

        /** @var Update $annotation */
        $annotation = $this->reader->getClassAnnotation(
            new \ReflectionClass($user),
            Update::class
        );

        $uow = $this
            ->getMockBuilder(UnitOfWork::class)
            ->disableOriginalConstructor()
            ->setMethods(['getScheduledCollectionUpdates', 'getOwner', 'getMapping', 'getDeleteDiff', 'getInsertDiff'])
            ->getMock();

        $this->manager->method('getUnitOfWork')->willReturn($uow);
        $uow->method('getScheduledCollectionUpdates')->willReturn([$uow]);
        $uow->method('getOwner')->willReturn($user);
        $uow->method('getMapping')->willReturn(['fieldName' => 'friends']);
        $deleted = [new User(), new User()];
        $uow->method('getDeleteDiff')->willReturn($deleted);
        $inserted = [new User()];
        $uow->method('getInsertDiff')->willReturn($inserted);

        $this->dispatcher->expects($this->once())
            ->method('addUpdate')
            ->with($annotation, $user, [], ['friends' => ['deleted' => $deleted, 'inserted' => $inserted]]);

        $this->manager
            ->method('getClassMetadata')
            ->with(ClassUtils::getRealClass(get_class($user)))
            ->willReturn($this->classMetadata);

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

        /** @var Update $annotation */
        $annotation = $this->reader->getClassAnnotation(
            new \ReflectionClass($user),
            Update::class
        );

        $uow = $this
            ->getMockBuilder(UnitOfWork::class)
            ->disableOriginalConstructor()
            ->setMethods(['getScheduledCollectionUpdates', 'getOwner', 'getMapping', 'getDeleteDiff', 'getInsertDiff'])
            ->getMock();

        $this->manager->method('getUnitOfWork')->willReturn($uow);
        $uow->method('getScheduledCollectionUpdates')->willReturn([$uow]);
        $uow->method('getOwner')->willReturn($user2);
        $uow->method('getMapping')->willReturn(['fieldName' => 'friends']);
        $deleted = [new User(), new User()];
        $uow->method('getDeleteDiff')->willReturn($deleted);
        $inserted = [new User()];
        $uow->method('getInsertDiff')->willReturn($inserted);

        $this->dispatcher->expects($this->once())
            ->method('addUpdate')
            ->with($annotation, $user, [], []);

        $this->listener->preUpdate($event);
    }

    public function testPreUpdateCollectionDoesNotExist()
    {
        $this->expectException(\ReflectionException::class);

        $user      = new UserClassUpdateCollection();
        $changeSet = [];
        $event     = new PreUpdateEventArgs($user, $this->manager, $changeSet);

        /** @var Update $annotation */
        $annotation = $this->reader->getClassAnnotation(
            new \ReflectionClass($user),
            Update::class
        );

        $uow = $this
            ->getMockBuilder(UnitOfWork::class)
            ->disableOriginalConstructor()
            ->setMethods(['getScheduledCollectionUpdates', 'getOwner', 'getMapping', 'getDeleteDiff', 'getInsertDiff'])
            ->getMock();

        $this->manager->method('getUnitOfWork')->willReturn($uow);
        $uow->method('getScheduledCollectionUpdates')->willReturn([$uow]);
        $uow->method('getOwner')->willReturn($user);
        $uow->method('getMapping')->willReturn(['fieldName' => 'foo']);
        $deleted = [new User(), new User()];
        $uow->method('getDeleteDiff')->willReturn($deleted);
        $inserted = [new User()];
        $uow->method('getInsertDiff')->willReturn($inserted);

        $this->manager
            ->method('getClassMetadata')
            ->with(ClassUtils::getRealClass(get_class($user)))
            ->willReturn($this->classMetadata);

        $this->classMetadata
            ->method('getName')
            ->willReturn(get_class($user));

        $this->classMetadata
            ->method('getReflectionProperty')
            ->with('foo')
            ->willReturn(null);

        $this->dispatcher->expects($this->never())
            ->method('addUpdate')
            ->with($annotation, $user, [], null);

        $this->listener->preUpdate($event);
    }
}
