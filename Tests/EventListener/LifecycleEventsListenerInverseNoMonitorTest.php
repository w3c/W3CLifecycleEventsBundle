<?php

namespace W3C\LifecycleEventsBundle\Tests\EventListener;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\UnitOfWork;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use W3C\LifecycleEventsBundle\Annotation\Update;
use W3C\LifecycleEventsBundle\EventListener\LifecycleEventsListener;
use W3C\LifecycleEventsBundle\Services\AnnotationGetter;
use W3C\LifecycleEventsBundle\Services\LifecycleEventsDispatcher;
use W3C\LifecycleEventsBundle\Tests\Annotation\Fixtures\PersonNoMonitor;

/**
 * @author Jean-Guilhem Rouel <jean-gui@w3.org>
 */
class LifecycleEventsListenerInverseNoMonitorTest extends TestCase
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
     * @var EntityManagerInterface|MockObject
     */
    private $manager;

    /**
     * @var ClassMetadata|MockObject
     */
    private $classMetadata;

    /**
     * @var array
     */
    private $mappings;

    private $person;
    private $mentor;
    private $father;
    private $friend1;
    private $friend2;

    public function setUp() : void
    {
        parent::setUp();

        $this->person = new PersonNoMonitor();
        $this->mentor = new PersonNoMonitor();
        $this->father = new PersonNoMonitor();
        $this->friend1 = new PersonNoMonitor();
        $this->friend2 = new PersonNoMonitor();

        $loader = require __DIR__ . '/../../vendor/autoload.php';
        AnnotationRegistry::registerLoader(array($loader, 'loadClass'));

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

        $this->mappings = [
            'father'    => [
                'targetEntity' => PersonNoMonitor::class,
                'inversedBy'   => 'sons'
            ],
            'sons'      => [
                'targetEntity' => PersonNoMonitor::class,
                'mappedBy'     => 'father',
            ],
            'friends'   => [
                'targetEntity' => PersonNoMonitor::class,
                'inversedBy'   => 'friendOf',
            ],
            'friendOf'  => [
                'targetEntity' => PersonNoMonitor::class,
                'mappedBy'     => 'friends'
            ],
            'mentor'    => [
                'targetEntity' => PersonNoMonitor::class,
                'inversedBy'   => 'mentoring',
            ],
            'mentoring' => [
                'targetEntity' => PersonNoMonitor::class,
                'mappedBy'     => 'mentor',
            ],
        ];

        $this->classMetadata
            ->method('getAssociationMappings')
            ->willReturn($this->mappings);

        $this->classMetadata
            ->method('hasAssociation')
            ->willReturnCallback(function () {
                $field = func_get_arg(0);
                return in_array($field, array_keys($this->mappings));
        });

        $this->classMetadata
            ->method('getAssociationMapping')
            ->willReturnCallback(function () {
                $field = func_get_arg(0);
                return $this->mappings[$field];
        });

        $this->classMetadata
            ->method('getReflectionProperty')
            ->willReturnCallback(function () {
                $field = func_get_arg(0);
                return new \ReflectionProperty(PersonNoMonitor::class, $field);
        });

        foreach (array_keys($this->mappings) as $field) {
            $this->classMetadata->reflFields[$field] = $this
                ->getMockBuilder(\ReflectionProperty::class)
                ->disableOriginalConstructor()
                ->setMethods(['getValue'])
                ->getMock();
        }

        $this->classMetadata->expects($this->any())
            ->method('isAssociationInverseSide')
            ->willReturnCallback(function () {
                $field = func_get_arg(0);
                switch ($field) {
                    case 'mentoring':
                    case 'friendOf':
                    case 'sons':
                        return true;
                    default:
                        return false;
                }
            });
        $this->classMetadata
            ->method('isSingleValuedAssociation')
            ->willReturnCallback(function () {
                $field = func_get_arg(0);
                switch ($field) {
                    case 'mentor':
                    case 'mentoring':
                    case 'father':
                        return true;
                    default:
                        return false;
                }
            });
        $this->classMetadata
            ->method('isCollectionValuedAssociation')
            ->willReturnCallback(function () {
                $field = func_get_arg(0);
                switch ($field) {
                    case 'mentor':
                    case 'mentoring':
                    case 'father':
                        return false;
                    default:
                        return true;
                }
            });

        $this->listener = new LifecycleEventsListener($this->dispatcher, new AnnotationGetter(new AnnotationReader()));
    }

    public function testOneToOnePostPersist()
    {
        $event = new LifecycleEventArgs($this->person, $this->manager);

        $this->manager
            ->method('getClassMetadata')
            ->with(ClassUtils::getRealClass(get_class($this->person)))
            ->willReturn($this->classMetadata);

        $this->classMetadata->reflFields['mentor']
            ->method('getValue')
            ->willReturn($this->mentor);

        $this->dispatcher->expects($this->never())
            ->method('addUpdate');

        $this->listener->postPersist($event);
    }

    public function testOneToOnePreRemove()
    {
        $event = new LifecycleEventArgs($this->person, $this->manager);

        $this->manager
            ->method('getClassMetadata')
            ->with(ClassUtils::getRealClass(get_class($this->person)))
            ->willReturn($this->classMetadata);

        $this->classMetadata->reflFields['mentor']
            ->method('getValue')
            ->willReturn($this->mentor);

        $this->dispatcher->expects($this->never())
            ->method('addUpdate');

        $this->listener->preRemove($event);
    }

    public function testOneToOnePreUpdate()
    {
        $uow = $this
            ->getMockBuilder(UnitOfWork::class)
            ->disableOriginalConstructor()
            ->setMethods(['getScheduledCollectionUpdates', 'getOwner', 'getMapping', 'getDeleteDiff', 'getInsertDiff'])
            ->getMock();
        $this->manager->method('getUnitOfWork')->willReturn($uow);
        $uow->method('getScheduledCollectionUpdates')->willReturn([]);

        $this->manager
            ->method('getClassMetadata')
            ->with(ClassUtils::getRealClass(get_class($this->person)))
            ->willReturn($this->classMetadata);

        $this->classMetadata->reflFields['mentor']
            ->method('getValue')
            ->willReturn($this->mentor);

        $this->dispatcher->expects($this->exactly(2))
            ->method('addUpdate')
            ->withConsecutive(
                [
                    $this->callback(function ($arg) {
                        return $arg instanceof Update;
                    }),
                    $this->equalTo($this->person),
                    $this->callback(function ($arg) {
                        return
                            array_keys($arg) === ['mentor'] &&
                            $arg['mentor']['old'] === null &&
                            $arg['mentor']['new'] === $this->mentor;
                    }),
                    $this->equalTo([])
                ],
                [
                    $this->callback(function ($arg) {
                        return $arg instanceof Update;
                    }),
                    $this->equalTo($this->person),
                    $this->callback(function ($arg) {
                        return
                            array_keys($arg) === ['mentor'] &&
                            $arg['mentor']['old'] === $this->mentor &&
                            $arg['mentor']['new'] === null;
                    }),
                    $this->equalTo([])
                ]
            );

        $changeSet = ['mentor' => [null, $this->mentor]];
        $event     = new PreUpdateEventArgs($this->person, $this->manager, $changeSet);

        $this->listener->preUpdate($event);

        $changeSet = ['mentor' => [$this->mentor, null]];
        $event     = new PreUpdateEventArgs($this->person, $this->manager, $changeSet);

        $this->listener->preUpdate($event);
    }


    public function testOneToManyPostPersist()
    {
        $event = new LifecycleEventArgs($this->person, $this->manager);

        $this->manager
            ->method('getClassMetadata')
            ->with(ClassUtils::getRealClass(get_class($this->person)))
            ->willReturn($this->classMetadata);

        $this->classMetadata->reflFields['father']
            ->method('getValue')
            ->willReturn($this->father);

        $this->dispatcher->expects($this->never())
            ->method('addUpdate');

        $this->listener->postPersist($event);
    }

    public function testOneToManyPreRemove()
    {
        $event = new LifecycleEventArgs($this->person, $this->manager);

        $this->manager
            ->method('getClassMetadata')
            ->with(ClassUtils::getRealClass(get_class($this->person)))
            ->willReturn($this->classMetadata);

        $this->classMetadata->reflFields['father']
            ->method('getValue')
            ->willReturn($this->mentor);

        $this->dispatcher->expects($this->never())
            ->method('addUpdate');

        $this->listener->preRemove($event);
    }

    public function testOneToManyPreUpdate()
    {
        $uow = $this
            ->getMockBuilder(UnitOfWork::class)
            ->disableOriginalConstructor()
            ->setMethods(['getScheduledCollectionUpdates', 'getOwner', 'getMapping', 'getDeleteDiff', 'getInsertDiff'])
            ->getMock();
        $this->manager->method('getUnitOfWork')->willReturn($uow);
        $uow->method('getScheduledCollectionUpdates')->willReturn([]);

        $this->manager
            ->method('getClassMetadata')
            ->with(ClassUtils::getRealClass(get_class($this->person)))
            ->willReturn($this->classMetadata);

        $this->dispatcher->expects($this->exactly(2))
            ->method('addUpdate')
            ->withConsecutive(
                [
                    $this->callback(function ($arg) {
                        return $arg instanceof Update;
                    }),
                    $this->equalTo($this->person),
                    $this->callback(function ($arg) {
                        return
                            array_keys($arg) === ['father'] &&
                            $arg['father']['old'] === null &&
                            $arg['father']['new'] === $this->father;
                    }),
                    $this->equalTo([])
                ],
                [
                    $this->callback(function ($arg) {
                        return $arg instanceof Update;
                    }),
                    $this->equalTo($this->person),
                    $this->callback(function ($arg) {
                        return
                            array_keys($arg) === ['father'] &&
                            $arg['father']['old'] === $this->father &&
                            $arg['father']['new'] === null;
                    }),
                    $this->equalTo([])
                ]
            );

        $changeSet = ['father' => [null, $this->father]];
        $event     = new PreUpdateEventArgs($this->person, $this->manager, $changeSet);

        $this->listener->preUpdate($event);

        $changeSet = ['father' => [$this->father, null]];
        $event     = new PreUpdateEventArgs($this->person, $this->manager, $changeSet);

        $this->listener->preUpdate($event);
    }

    public function testManyToManyPostPersist()
    {
        $event = new LifecycleEventArgs($this->person, $this->manager);

        $this->manager
            ->method('getClassMetadata')
            ->with(ClassUtils::getRealClass(get_class($this->person)))
            ->willReturn($this->classMetadata);

        $this->classMetadata->reflFields['friends']
            ->method('getValue')
            ->willReturn(new ArrayCollection([$this->friend1, $this->friend2]));

        $this->dispatcher->expects($this->never())
            ->method('addUpdate');

        $this->listener->postPersist($event);
    }

    public function testManyToManyPreRemove()
    {
        $event = new LifecycleEventArgs($this->person, $this->manager);

        $this->manager
            ->method('getClassMetadata')
            ->with(ClassUtils::getRealClass(get_class($this->person)))
            ->willReturn($this->classMetadata);

        $this->classMetadata->reflFields['friends']
            ->method('getValue')
            ->willReturn(new ArrayCollection([$this->friend1, $this->friend2]));

        $this->dispatcher->expects($this->never())
            ->method('addUpdate');

        $this->listener->preRemove($event);
    }

    public function testManyToManyPreUpdate()
    {
        $uow = $this
            ->getMockBuilder(UnitOfWork::class)
            ->disableOriginalConstructor()
            ->setMethods(['getScheduledCollectionUpdates', 'getOwner', 'getMapping', 'getDeleteDiff', 'getInsertDiff'])
            ->getMock();
        $this->manager->method('getUnitOfWork')->willReturn($uow);
        $uow->method('getScheduledCollectionUpdates')->willReturn([$uow]);
        $uow->method('getOwner')->willReturn($this->person);
        $uow->method('getMapping')->willReturn(['fieldName' => 'friends']);
        $deleted = [];
        $uow->method('getDeleteDiff')->willReturn($deleted);
        $inserted = [$this->friend1, $this->friend2];
        $uow->method('getInsertDiff')->willReturn($inserted);

        $this->manager
            ->method('getClassMetadata')
            ->with(ClassUtils::getRealClass(get_class($this->person)))
            ->willReturn($this->classMetadata);

        $this->dispatcher->expects($this->exactly(1))
            ->method('addUpdate')
            ->withConsecutive(
                [
                    $this->callback(function ($arg) {
                        return $arg instanceof Update;
                    }),
                    $this->equalTo($this->person),
                    $this->equalTo([]),
                    $this->callback(function ($arg) {
                        return
                            array_keys($arg) === ['friends'] &&
                            $arg['friends']['deleted'] === [] &&
                            $arg['friends']['inserted'] === [$this->friend1, $this->friend2];
                    })
                ]
            );

        $changeSet = [];
        $event     = new PreUpdateEventArgs($this->person, $this->manager, $changeSet);

        $this->listener->preUpdate($event);
    }

    public function testManyToManyRemovePreUpdate()
    {
        $uow = $this
            ->getMockBuilder(UnitOfWork::class)
            ->disableOriginalConstructor()
            ->setMethods(['getScheduledCollectionUpdates', 'getOwner', 'getMapping', 'getDeleteDiff', 'getInsertDiff'])
            ->getMock();
        $this->manager->method('getUnitOfWork')->willReturn($uow);
        $uow->method('getScheduledCollectionUpdates')->willReturn([$uow]);
        $uow->method('getOwner')->willReturn($this->person);
        $uow->method('getMapping')->willReturn(['fieldName' => 'friends']);
        $deleted = [$this->friend1];
        $uow->method('getDeleteDiff')->willReturn($deleted);
        $inserted = [];
        $uow->method('getInsertDiff')->willReturn($inserted);

        $this->manager
            ->method('getClassMetadata')
            ->with(ClassUtils::getRealClass(get_class($this->person)))
            ->willReturn($this->classMetadata);

        $this->dispatcher->expects($this->exactly(1))
            ->method('addUpdate')
            ->withConsecutive(
                [
                    $this->callback(function ($arg) {
                        return $arg instanceof Update;
                    }),
                    $this->equalTo($this->person),
                    $this->equalTo([]),
                    $this->callback(function ($arg) {
                        return
                            array_keys($arg) === ['friends'] &&
                            $arg['friends']['deleted'] === [$this->friend1] &&
                            $arg['friends']['inserted'] === [];
                    })
                ]
            );

        $changeSet = [];
        $event     = new PreUpdateEventArgs($this->person, $this->manager, $changeSet);

        $this->listener->preUpdate($event);
    }
}
