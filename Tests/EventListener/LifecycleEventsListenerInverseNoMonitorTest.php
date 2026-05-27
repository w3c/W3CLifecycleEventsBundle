<?php

namespace W3C\LifecycleEventsBundle\Tests\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ManyToManyInverseSideMapping;
use Doctrine\ORM\Mapping\ManyToManyOwningSideMapping;
use Doctrine\ORM\Mapping\ManyToOneAssociationMapping;
use Doctrine\ORM\Mapping\OneToManyAssociationMapping;
use Doctrine\ORM\Mapping\OneToOneInverseSideMapping;
use Doctrine\ORM\Mapping\OneToOneOwningSideMapping;
use Doctrine\ORM\Mapping\PropertyAccessors\PropertyAccessorFactory;
use Doctrine\ORM\Mapping\PropertyAccessors\RawValuePropertyAccessor;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\UnitOfWork;
use PHPUnit\Framework\TestCase;
use W3C\LifecycleEventsBundle\Attribute\Update;
use W3C\LifecycleEventsBundle\EventListener\LifecycleEventsListener;
use W3C\LifecycleEventsBundle\Services\AttributeGetter;
use W3C\LifecycleEventsBundle\Services\LifecycleEventsDispatcher;
use W3C\LifecycleEventsBundle\Tests\Attribute\Fixtures\PersonNoMonitor;

/**
 * @author Jean-Guilhem Rouel <jean-gui@w3.org>
 */
class LifecycleEventsListenerInverseNoMonitorTest extends TestCase
{
    private LifecycleEventsListener $listener;
    private LifecycleEventsDispatcher $dispatcher;
    private EntityManagerInterface $manager;
    private ClassMetadata $classMetadata;
    private array $mappings;
    private PersonNoMonitor $person;
    private PersonNoMonitor $mentor;
    private PersonNoMonitor $father;
    private PersonNoMonitor $friend1;
    private PersonNoMonitor $friend2;

    public function setUp() : void
    {
        parent::setUp();

        $this->person = new PersonNoMonitor();
        $this->mentor = new PersonNoMonitor();
        $this->father = new PersonNoMonitor();
        $this->friend1 = new PersonNoMonitor();
        $this->friend2 = new PersonNoMonitor();

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
        ;

        $father = new ManyToOneAssociationMapping(
            'father',
            PersonNoMonitor::class,
            PersonNoMonitor::class,
        );
        $father->inversedBy = 'sons';

        $sons = new OneToManyAssociationMapping(
            'sons',
            PersonNoMonitor::class,
            PersonNoMonitor::class,
        );
        $sons->mappedBy = 'father';

        $friends = new ManyToManyOwningSideMapping(
            'friends',
            PersonNoMonitor::class,
            PersonNoMonitor::class,
        );
        $friends->inversedBy = 'friendOf';

        $friendOf = new ManyToManyInverseSideMapping(
            'friendOf',
            PersonNoMonitor::class,
            PersonNoMonitor::class,
        );
        $friendOf->mappedBy = 'friends';

        $mentor = new OneToOneOwningSideMapping(
            'mentor',
            PersonNoMonitor::class,
            PersonNoMonitor::class,
        );
        $mentor->inversedBy = 'mentoring';

        $mentoring = new OneToOneInverseSideMapping(
            'mentoring',
            PersonNoMonitor::class,
            PersonNoMonitor::class,
        );
        $mentoring->mappedBy = 'mentor';

        $this->mappings = [
            'father' => $father,
            'sons' => $sons,
            'friends' => $friends,
            'friendOf' => $friendOf,
            'mentor'    => $mentor,
            'mentoring' => $mentoring,
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
            ->method('getPropertyAccessor')
            ->willReturnCallback(function () {
                $field = func_get_arg(0);
                return PropertyAccessorFactory::createPropertyAccessor(PersonNoMonitor::class, $field);
        });

        foreach (array_keys($this->mappings) as $field) {
            $this->classMetadata->propertyAccessors[$field] = $this->getMockBuilder(RawValuePropertyAccessor::class)
                ->disableOriginalConstructor()
                ->onlyMethods(['getValue'])
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

        $this->listener = new LifecycleEventsListener($this->dispatcher, new AttributeGetter());
    }

    public function testOneToOnePostPersist()
    {
        $event = new PostPersistEventArgs($this->person, $this->manager);

        $this->manager
            ->method('getClassMetadata')
            ->with($this->person::class)
            ->willReturn($this->classMetadata);

        $this->classMetadata->propertyAccessors['mentor']
            ->method('getValue')
            ->willReturn($this->mentor);

        $this->classMetadata
            ->method('getName')
            ->willReturn($this->person::class);

        $this->dispatcher->expects($this->never())
            ->method('addUpdate');

        $this->listener->postPersist($event);
    }

    public function testOneToOnePreRemove()
    {
        $event = new PreRemoveEventArgs($this->person, $this->manager);

        $this->manager
            ->method('getClassMetadata')
            ->with($this->person::class)
            ->willReturn($this->classMetadata);

        $this->classMetadata->propertyAccessors['mentor']
            ->method('getValue')
            ->willReturn($this->mentor);

        $this->classMetadata
            ->method('getName')
            ->willReturn($this->person::class);

        $this->dispatcher->expects($this->never())
            ->method('addUpdate');

        $this->listener->preRemove($event);
    }

    public function testOneToOnePreUpdate()
    {
        $uow = $this
            ->getMockBuilder(UnitOfWork::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getScheduledCollectionUpdates'])
            ->getMock();
        $this->manager->method('getUnitOfWork')->willReturn($uow);
        $uow->method('getScheduledCollectionUpdates')->willReturn([]);

        $this->manager
            ->method('getClassMetadata')
            ->with($this->person::class)
            ->willReturn($this->classMetadata);

        $this->classMetadata->propertyAccessors['mentor']
            ->method('getValue')
            ->willReturn($this->mentor);

        $this->classMetadata
            ->method('getName')
            ->willReturn($this->person::class);

        $matcher = $this->exactly(2);
        $this->dispatcher->expects($matcher)
            ->method('addUpdate')
            ->willReturnCallback(function ($update, $person, $changes, $context) use ($matcher) {
                $this->assertInstanceOf(Update::class, $update);
                $this->assertSame($this->person, $person);
                $this->assertSame([], $context);

                if ($matcher->numberOfInvocations() === 1) {
                    $this->assertSame(['mentor'], array_keys($changes));
                    $this->assertNull($changes['mentor']['old']);
                    $this->assertSame($this->mentor, $changes['mentor']['new']);
                } elseif ($matcher->numberOfInvocations() === 2) {
                    $this->assertSame(['mentor'], array_keys($changes));
                    $this->assertSame($this->mentor, $changes['mentor']['old']);
                    $this->assertNull($changes['mentor']['new']);
                } else {
                    $this->fail('addUpdate called more times than expected.');
                }
            });

        $changeSet = ['mentor' => [null, $this->mentor]];
        $event     = new PreUpdateEventArgs($this->person, $this->manager, $changeSet);

        $this->listener->preUpdate($event);

        $changeSet = ['mentor' => [$this->mentor, null]];
        $event     = new PreUpdateEventArgs($this->person, $this->manager, $changeSet);

        $this->listener->preUpdate($event);
    }


    public function testOneToManyPostPersist()
    {
        $event = new PostPersistEventArgs($this->person, $this->manager);

        $this->manager
            ->method('getClassMetadata')
            ->with($this->person::class)
            ->willReturn($this->classMetadata);

        $this->classMetadata->propertyAccessors['father']
            ->method('getValue')
            ->willReturn($this->father);

        $this->classMetadata
            ->method('getName')
            ->willReturn($this->person::class);

        $this->dispatcher->expects($this->never())
            ->method('addUpdate');

        $this->listener->postPersist($event);
    }

    public function testOneToManyPreRemove()
    {
        $event = new PreRemoveEventArgs($this->person, $this->manager);

        $this->manager
            ->method('getClassMetadata')
            ->with($this->person::class)
            ->willReturn($this->classMetadata);

        $this->classMetadata->propertyAccessors['father']
            ->method('getValue')
            ->willReturn($this->mentor);

        $this->classMetadata
            ->method('getName')
            ->willReturn($this->person::class);

        $this->dispatcher->expects($this->never())
            ->method('addUpdate');

        $this->listener->preRemove($event);
    }

    public function testOneToManyPreUpdate()
    {
        $uow = $this
            ->getMockBuilder(UnitOfWork::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getScheduledCollectionUpdates'])
            ->getMock();

        $this->manager->method('getUnitOfWork')->willReturn($uow);
        $uow->method('getScheduledCollectionUpdates')->willReturn([]);

        $this->manager
            ->method('getClassMetadata')
            ->with($this->person::class)
            ->willReturn($this->classMetadata);

        $this->classMetadata
            ->method('getName')
            ->willReturn($this->person::class);

        $matcher = $this->exactly(2);
        $this->dispatcher->expects($matcher)
            ->method('addUpdate')
            ->willReturnCallback(function ($update, $person, $changes, $context) use ($matcher) {
                $this->assertInstanceOf(Update::class, $update);
                $this->assertSame($this->person, $person);
                $this->assertSame([], $context);

                if ($matcher->numberOfInvocations() === 1) {
                    $this->assertSame(['father'], array_keys($changes));
                    $this->assertNull($changes['father']['old']);
                    $this->assertSame($this->father, $changes['father']['new']);
                } elseif ($matcher->numberOfInvocations() === 2) {
                    $this->assertSame(['father'], array_keys($changes));
                    $this->assertSame($this->father, $changes['father']['old']);
                    $this->assertNull($changes['father']['new']);
                } else {
                    $this->fail('addUpdate called more times than expected.');
                }
            });

        $changeSet = ['father' => [null, $this->father]];
        $event     = new PreUpdateEventArgs($this->person, $this->manager, $changeSet);

        $this->listener->preUpdate($event);

        $changeSet = ['father' => [$this->father, null]];
        $event     = new PreUpdateEventArgs($this->person, $this->manager, $changeSet);

        $this->listener->preUpdate($event);
    }

    public function testManyToManyPostPersist()
    {
        $event = new PostPersistEventArgs($this->person, $this->manager);

        $this->manager
            ->method('getClassMetadata')
            ->with($this->person::class)
            ->willReturn($this->classMetadata);

        $this->classMetadata->propertyAccessors['friends']
            ->method('getValue')
            ->willReturn(new ArrayCollection([$this->friend1, $this->friend2]));

        $this->classMetadata
            ->method('getName')
            ->willReturn($this->person::class);

        $this->dispatcher->expects($this->never())
            ->method('addUpdate');

        $this->listener->postPersist($event);
    }

    public function testManyToManyPreRemove()
    {
        $event = new PreRemoveEventArgs($this->person, $this->manager);

        $this->manager
            ->method('getClassMetadata')
            ->with($this->person::class)
            ->willReturn($this->classMetadata);

        $this->classMetadata->propertyAccessors['friends']
            ->method('getValue')
            ->willReturn(new ArrayCollection([$this->friend1, $this->friend2]));

        $this->classMetadata
            ->method('getName')
            ->willReturn($this->person::class);

        $this->dispatcher->expects($this->never())
            ->method('addUpdate');

        $this->listener->preRemove($event);
    }

    public function testManyToManyPreUpdate()
    {
        $pc = new PersistentCollection($this->manager, $this->classMetadata, new ArrayCollection());
        $pc->add($this->friend1);
        $pc->add($this->friend2);
        $pc->setOwner($this->person, $this->mappings['friends']);

        $uow = $this
            ->getMockBuilder(UnitOfWork::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getScheduledCollectionUpdates'])
            ->getMock();

        $this->manager->method('getUnitOfWork')->willReturn($uow);
        $uow->method('getScheduledCollectionUpdates')->willReturn([$pc]);

        $this->manager
            ->method('getClassMetadata')
            ->with($this->person::class)
            ->willReturn($this->classMetadata);

        $this->classMetadata
            ->method('getName')
            ->willReturn($this->person::class);

        $this->dispatcher->expects($this->once())
            ->method('addUpdate')
            ->willReturnCallback(function ($update, $person, $changes, $collections) {
                $this->assertInstanceOf(Update::class, $update);
                $this->assertSame($this->person, $person);
                $this->assertSame([], $changes);

                $this->assertSame(['friends'], array_keys($collections));
                $this->assertSame([], $collections['friends']['deleted']);
                $this->assertSame([$this->friend1, $this->friend2], $collections['friends']['inserted']);
            });

        $changeSet = [];
        $event     = new PreUpdateEventArgs($this->person, $this->manager, $changeSet);

        $this->listener->preUpdate($event);
    }

    public function testManyToManyRemovePreUpdate()
    {
        $pc = new PersistentCollection(
            $this->manager,
            $this->classMetadata,
            new ArrayCollection([$this->friend1])
        );
        $pc->setOwner($this->person, $this->mappings['friends']);
        $pc->takeSnapshot();
        $pc->removeElement($this->friend1);

        $uow = $this
            ->getMockBuilder(UnitOfWork::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getScheduledCollectionUpdates'])
            ->getMock();

        $this->manager->method('getUnitOfWork')->willReturn($uow);
        $uow->method('getScheduledCollectionUpdates')->willReturn([$pc]);

        $this->manager
            ->method('getClassMetadata')
            ->with($this->person::class)
            ->willReturn($this->classMetadata);

        $this->classMetadata
            ->method('getName')
            ->willReturn($this->person::class);

        $this->dispatcher->expects($this->once())
            ->method('addUpdate')
            ->willReturnCallback(function ($update, $person, $changes, $collections) {
                $this->assertInstanceOf(Update::class, $update);
                $this->assertSame($this->person, $person);
                $this->assertSame([], $changes);

                $this->assertSame(['friends'], array_keys($collections));
                $this->assertSame([$this->friend1], $collections['friends']['deleted']);
                $this->assertSame([], $collections['friends']['inserted']);
            });

        $changeSet = [];
        $event     = new PreUpdateEventArgs($this->person, $this->manager, $changeSet);

        $this->listener->preUpdate($event);
    }
}
