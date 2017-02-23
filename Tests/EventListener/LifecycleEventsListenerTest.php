<?php
/*
 * Copyright 2017 Jean-Guilhem Rouel
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace W3C\LifecycleEventsBundle\Tests\EventListener;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\Reader;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\UnitOfWork;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use W3C\LifecycleEventsBundle\Annotation\Update;
use W3C\LifecycleEventsBundle\EventListener\LifecycleEventsListener;
use W3C\LifecycleEventsBundle\Services\LifecycleEventsDispatcher;
use W3C\LifecycleEventsBundle\Tests\Annotation\Fixtures\User;
use W3C\LifecycleEventsBundle\Tests\EventListener\Fixtures\UserClassUpdateCollection;
use W3C\LifecycleEventsBundle\Tests\EventListener\Fixtures\UserClassUpdateIgnoreCollection;
use W3C\LifecycleEventsBundle\Tests\EventListener\Fixtures\UserClassUpdateIgnoreNoCollection;
use W3C\LifecycleEventsBundle\Tests\EventListener\Fixtures\UserClassUpdateNoCollection;
use W3C\LifecycleEventsBundle\Tests\EventListener\Fixtures\UserNoAnnotation;

class LifecycleEventsListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var LifecycleEventsListener
     */
    private $listener;

    /**
     * @var Reader
     */
    private $reader;

    /**
     * @var LifecycleEventsDispatcher|MockObject
     */
    private $dispatcher;

    /**
     * @var EntityManagerInterface|MockObject
     */
    private $manager;

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

        $this->listener = new LifecycleEventsListener($this->dispatcher, $this->reader);
    }

    public function testPostPersist()
    {
        $user = new User();
        $event = new LifecycleEventArgs($user, $this->manager);

        $this->dispatcher->expects($this->once())
            ->method('addCreation');

        $this->listener->postPersist($event);
    }

    public function testPostPersistNoCreationAnnotation()
    {
        $user  = new UserNoAnnotation();
        $event = new LifecycleEventArgs($user, $this->manager);

        $this->dispatcher->expects($this->never())
            ->method('addCreation');

        $this->listener->postPersist($event);
    }

    public function testPostRemove()
    {
        $user  = new User();
        $event = new LifecycleEventArgs($user, $this->manager);

        $this->dispatcher->expects($this->once())
            ->method('addDeletion');

        $this->listener->postRemove($event);
    }

    public function testPostRemoveNoCreationAnnotation()
    {
        $user  = new UserNoAnnotation();
        $event = new LifecycleEventArgs($user, $this->manager);

        $this->dispatcher->expects($this->never())
            ->method('addDeletion');

        $this->listener->postRemove($event);
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

        $this->dispatcher->expects($this->once())
            ->method('addUpdate')
            ->with($annotation, $user, ['name' => ['old' => null, 'new' => 'foo']], null);

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

        $this->dispatcher->expects($this->once())
            ->method('addUpdate')
            ->with($annotation, $user, [], null);

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

        $this->dispatcher->expects($this->once())
            ->method('addUpdate')
            ->with($annotation, $user, [], null);

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
            ->with($annotation, $user, [], null);

        $this->listener->preUpdate($event);
    }
}
