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
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\UnitOfWork;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use W3C\LifecycleEventsBundle\Annotation\Change;
use W3C\LifecycleEventsBundle\Annotation\Update;
use W3C\LifecycleEventsBundle\EventListener\LifecycleEventsListener;
use W3C\LifecycleEventsBundle\EventListener\LifecyclePropertyEventsListener;
use W3C\LifecycleEventsBundle\Services\LifecycleEventsDispatcher;
use W3C\LifecycleEventsBundle\Tests\Annotation\Fixtures\User;
use W3C\LifecycleEventsBundle\Tests\EventListener\Fixtures\UserChange;
use W3C\LifecycleEventsBundle\Tests\EventListener\Fixtures\UserNoAnnotation;

class LifecyclePropertyEventsListenerTest extends \PHPUnit_Framework_TestCase
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

    /**
     * @var UnitOfWork|MockObject
     */
    private $uow;

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

        $this->uow = $this
            ->getMockBuilder(UnitOfWork::class)
            ->disableOriginalConstructor()
            ->setMethods(['getScheduledCollectionUpdates', 'getOwner', 'getMapping', 'getDeleteDiff', 'getInsertDiff'])
            ->getMock();

        $this->listener = new LifecyclePropertyEventsListener($this->dispatcher, $this->reader);
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

        $this->dispatcher->expects($this->once())
            ->method('addCollectionChange')
            ->with($annotation, $user, 'friends', $deleted, $inserted);

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
}
