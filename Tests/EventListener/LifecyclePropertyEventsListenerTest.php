<?php

namespace W3C\LifecycleEventsBundle\Tests\EventListener;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\UnitOfWork;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use W3C\LifecycleEventsBundle\Annotation\Change;
use W3C\LifecycleEventsBundle\Annotation\Update;
use W3C\LifecycleEventsBundle\EventListener\LifecycleEventsListener;
use W3C\LifecycleEventsBundle\EventListener\LifecyclePropertyEventsListener;
use W3C\LifecycleEventsBundle\Services\AnnotationGetter;
use W3C\LifecycleEventsBundle\Services\LifecycleEventsDispatcher;
use W3C\LifecycleEventsBundle\Tests\Annotation\Fixtures\User;
use W3C\LifecycleEventsBundle\Tests\EventListener\Fixtures\OtherEntity;
use W3C\LifecycleEventsBundle\Tests\EventListener\Fixtures\UserChange;
use W3C\LifecycleEventsBundle\Tests\EventListener\Fixtures\UserClassUpdateCollection;
use W3C\LifecycleEventsBundle\Tests\EventListener\Fixtures\UserNoAnnotation;

/**
 * @author Jean-Guilhem Rouel <jean-gui@w3.org>
 */
class LifecyclePropertyEventsListenerTest extends \PHPUnit_Framework_TestCase
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
     * @var UnitOfWork|MockObject
     */
    private $uow;

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

        $this->uow = $this
            ->getMockBuilder(UnitOfWork::class)
            ->disableOriginalConstructor()
            ->setMethods(['getScheduledCollectionUpdates', 'getOwner', 'getMapping', 'getDeleteDiff', 'getInsertDiff'])
            ->getMock();

        $this->listener = new LifecyclePropertyEventsListener($this->dispatcher, new AnnotationGetter($this->reader));
    }

    public function testPreUpdateProperty()
    {
        $user = new UserChange();
        $changeSet = ['name' => ['foo', 'bar']];
        $event = new PreUpdateEventArgs($user, $this->manager, $changeSet);

        /** @var Update $annotation */
        $annotation = $this->reader->getPropertyAnnotation(
            new \ReflectionProperty(get_class($user), 'name'),
            Change::class
        );

        $this->manager->method('getUnitOfWork')->willReturn($this->uow);
        $this->uow->method('getScheduledCollectionUpdates')->willReturn([]);

        $this->manager
            ->method('getClassMetadata')
            ->with(ClassUtils::getRealClass(get_class($user)))
            ->willReturn($this->classMetadata);

        $this->classMetadata
            ->method('getReflectionProperty')
            ->with('name')
            ->willReturn(new \ReflectionProperty($user, 'name'));

        $this->dispatcher->expects($this->once())
            ->method('addPropertyChange')
            ->with($annotation, $user, 'name', 'foo', 'bar');

        $this->listener->preUpdate($event);
    }

    public function testPreUpdateCollection()
    {
        $user      = new UserChange();
        $changeSet = [];
        $event     = new PreUpdateEventArgs($user, $this->manager, $changeSet);

        /** @var Update $annotation */
        $annotation = $this->reader->getPropertyAnnotation(
            new \ReflectionProperty(get_class($user), 'name'),
            Change::class
        );

        $this->manager->method('getUnitOfWork')->willReturn($this->uow);
        $this->uow->method('getScheduledCollectionUpdates')->willReturn([$this->uow]);
        $this->uow->method('getOwner')->willReturn($user);
        $this->uow->method('getMapping')->willReturn(['fieldName' => 'friends']);
        $deleted = [new User(), new User()];
        $this->uow->method('getDeleteDiff')->willReturn($deleted);
        $inserted = [new User()];
        $this->uow->method('getInsertDiff')->willReturn($inserted);

        $this->manager
            ->method('getClassMetadata')
            ->with(ClassUtils::getRealClass(get_class($user)))
            ->willReturn($this->classMetadata);

        $this->classMetadata
            ->method('getReflectionProperty')
            ->with('friends')
            ->willReturn(new \ReflectionProperty($user, 'friends'));

        $this->dispatcher->expects($this->once())
            ->method('addCollectionChange')
            ->with($annotation, $user, 'friends', $deleted, $inserted);

        $this->listener->preUpdate($event);
    }

    public function testPreUpdateCollectionFieldDoesNotExist()
    {
        $this->expectException(\ReflectionException::class);

        $user      = new UserChange();
        $changeSet = [];
        $event     = new PreUpdateEventArgs($user, $this->manager, $changeSet);

        $this->manager->method('getUnitOfWork')->willReturn($this->uow);
        $this->uow->method('getScheduledCollectionUpdates')->willReturn([$this->uow]);
        $this->uow->method('getOwner')->willReturn($user);
        $this->uow->method('getMapping')->willReturn(['fieldName' => 'foo']);
        $deleted = [new User(), new User()];
        $this->uow->method('getDeleteDiff')->willReturn($deleted);
        $inserted = [new User()];
        $this->uow->method('getInsertDiff')->willReturn($inserted);

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
            ->method('addCollectionChange');

        $this->listener->preUpdate($event);
    }

    public function testPreUpdateCollectionFieldNotMonitored()
    {
        $user      = new UserClassUpdateCollection();
        $changeSet = [];
        $event     = new PreUpdateEventArgs($user, $this->manager, $changeSet);

        $this->manager->method('getUnitOfWork')->willReturn($this->uow);
        $this->uow->method('getScheduledCollectionUpdates')->willReturn([$this->uow]);
        $this->uow->method('getOwner')->willReturn($user);
        $this->uow->method('getMapping')->willReturn(['fieldName' => 'friends']);
        $deleted = [new User(), new User()];
        $this->uow->method('getDeleteDiff')->willReturn($deleted);
        $inserted = [new User()];
        $this->uow->method('getInsertDiff')->willReturn($inserted);

        $this->manager
            ->method('getClassMetadata')
            ->with(ClassUtils::getRealClass(get_class($user)))
            ->willReturn($this->classMetadata);

        $this->classMetadata
            ->method('getReflectionProperty')
            ->with('friends')
            ->willReturn(new \ReflectionProperty($user, 'friends'));

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

        $this->manager->method('getUnitOfWork')->willReturn($this->uow);
        $this->uow->method('getScheduledCollectionUpdates')->willReturn([$this->uow]);
        $this->uow->method('getOwner')->willReturn($user2);
        $this->uow->method('getMapping')->willReturn(['fieldName' => 'friends']);
        $deleted = [new User(), new User()];
        $this->uow->method('getDeleteDiff')->willReturn($deleted);
        $inserted = [new User()];
        $this->uow->method('getInsertDiff')->willReturn($inserted);

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

        $this->manager->method('getUnitOfWork')->willReturn($this->uow);
        $this->uow->method('getScheduledCollectionUpdates')->willReturn([$this->uow]);
        $this->uow->method('getOwner')->willReturn($user2);
        $this->uow->method('getMapping')->willReturn(['fieldName' => 'foo']);
        $deleted = [new User(), new User()];
        $this->uow->method('getDeleteDiff')->willReturn($deleted);
        $inserted = [new User()];
        $this->uow->method('getInsertDiff')->willReturn($inserted);

        $this->dispatcher->expects($this->never())
            ->method('addCollectionChange');

        $this->listener->preUpdate($event);
    }
}
