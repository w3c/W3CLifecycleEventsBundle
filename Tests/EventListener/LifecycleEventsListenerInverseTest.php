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
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\UnitOfWork;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use W3C\LifecycleEventsBundle\Attribute\Change;
use W3C\LifecycleEventsBundle\Attribute\Update;
use W3C\LifecycleEventsBundle\EventListener\LifecycleEventsListener;
use W3C\LifecycleEventsBundle\Services\AttributeGetter;
use W3C\LifecycleEventsBundle\Services\LifecycleEventsDispatcher;
use W3C\LifecycleEventsBundle\Tests\Attribute\Fixtures\Person;
use W3C\LifecycleEventsBundle\Tests\Attribute\Fixtures\PersonNoMonitor;

/**
 * @author Jean-Guilhem Rouel <jean-gui@w3.org>
 */
class LifecycleEventsListenerInverseTest extends TestCase
{
    private LifecycleEventsListener $listener;
    private LifecycleEventsDispatcher $dispatcher;
    private EntityManagerInterface $manager;
    private ClassMetadata $classMetadata;
    private array $mappings;
    private Person $person;
    private Person $mentor;
    private Person $father;
    private Person $friend1;
    private Person $friend2;

    public function setUp() : void
    {
        parent::setUp();

        $this->person = new Person();
        $this->mentor = new Person();
        $this->father = new Person();
        $this->friend1 = new Person();
        $this->friend2 = new Person();

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

        $father = new ManyToOneAssociationMapping(
            'father',
            Person::class,
            Person::class,
        );
        $father->inversedBy = 'sons';

        $sons = new OneToManyAssociationMapping(
            'sons',
            Person::class,
            Person::class,
        );
        $sons->mappedBy = 'father';

        $friends = new ManyToManyOwningSideMapping(
            'friends',
            Person::class,
            Person::class,
        );
        $friends->inversedBy = 'friendOf';

        $friendOf = new ManyToManyInverseSideMapping(
            'friendOf',
            Person::class,
            Person::class,
        );
        $friendOf->mappedBy = 'friends';

        $mentor = new OneToOneOwningSideMapping(
            'mentor',
            Person::class,
            Person::class,
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
            ->method('getReflectionProperty')
            ->willReturnCallback(function () {
                $field = func_get_arg(0);
                return new \ReflectionProperty(Person::class, $field);
        });

        foreach (array_keys($this->mappings) as $field) {
            $this->classMetadata->reflFields[$field] = $this
                ->getMockBuilder(\ReflectionProperty::class)
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

        $this->classMetadata
            ->method('getName')
            ->willReturn($this->person::class);

        $this->classMetadata->reflFields['mentor']
            ->method('getValue')
            ->willReturn($this->mentor);

        $this->dispatcher->expects($this->exactly(1))
            ->method('addUpdate')
            ->with(
                $this->callback(function ($arg) { return $arg instanceof Update; }),
                $this->callback(function ($arg) { return $arg === $this->mentor; }),
                $this->callback(function ($arg) {
                    return
                        array_keys($arg) === ['mentoring'] &&
                        $arg['mentoring']['old'] === null &&
                        $arg['mentoring']['new'] === $this->person;
                }),
                $this->equalTo([]));

        $this->dispatcher->expects($this->exactly(1))
            ->method('addPropertyChange')
            ->with(
                $this->callback(function ($arg) { return $arg instanceof Change; }),
                $this->callback(function ($arg) { return $arg === $this->mentor; }),
                $this->equalTo('mentoring'),
                $this->equalTo(null),
                $this->callback(function ($arg) { return $arg === $this->person; })
            );

        $this->listener->postPersist($event);
    }

    public function testOneToOnePreRemove()
    {
        $event = new PreRemoveEventArgs($this->person, $this->manager);

        $this->manager
            ->method('getClassMetadata')
            ->with($this->person::class)
            ->willReturn($this->classMetadata);

        $this->classMetadata
            ->method('getName')
            ->willReturn($this->person::class);

        $this->classMetadata->reflFields['mentor']
            ->method('getValue')
            ->willReturn($this->mentor);

        $this->dispatcher->expects($this->exactly(1))
            ->method('addUpdate')
            ->with(
                $this->callback(function ($arg) { return $arg instanceof Update; }),
                $this->callback(function ($arg) { return $arg === $this->mentor; }),
                $this->callback(function ($arg) {
                    return
                        array_keys($arg) === ['mentoring'] &&
                        $arg['mentoring']['old'] === $this->person &&
                        $arg['mentoring']['new'] === null;
                }),
                $this->equalTo([]));

        $this->dispatcher->expects($this->exactly(1))
            ->method('addPropertyChange')
            ->with(
                $this->callback(function ($arg) { return $arg instanceof Change; }),
                $this->callback(function ($arg) { return $arg === $this->mentor; }),
                $this->equalTo('mentoring'),
                $this->callback(function ($arg) { return $arg === $this->person; }),
                $this->equalTo(null)
            );

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

        $this->classMetadata
            ->method('getName')
            ->willReturn($this->person::class);

        $this->classMetadata->reflFields['mentor']
            ->method('getValue')
            ->willReturn($this->mentor);

        $matcher = $this->exactly(4);
        $this->dispatcher->expects($matcher)
            ->method('addUpdate')
            ->willReturnCallback(function ($update, $entity, $changes, $context) use ($matcher) {
                $this->assertInstanceOf(Update::class, $update);
                $this->assertSame([], $context);

                if ($matcher->numberOfInvocations() === 1) {
                    $this->assertSame($this->mentor, $entity);
                    $this->assertSame(['mentoring'], array_keys($changes));
                    $this->assertNull($changes['mentoring']['old']);
                    $this->assertSame($this->person, $changes['mentoring']['new']);
                } elseif ($matcher->numberOfInvocations() === 2) {
                    $this->assertSame($this->person, $entity);
                    $this->assertSame(['mentor'], array_keys($changes));
                    $this->assertNull($changes['mentor']['old']);
                    $this->assertSame($this->mentor, $changes['mentor']['new']);
                } elseif ($matcher->numberOfInvocations() === 3) {
                    $this->assertSame($this->mentor, $entity);
                    $this->assertSame(['mentoring'], array_keys($changes));
                    $this->assertSame($this->person, $changes['mentoring']['old']);
                    $this->assertNull($changes['mentoring']['new']);
                } elseif ($matcher->numberOfInvocations() === 4) {
                    $this->assertSame($this->person, $entity);
                    $this->assertSame(['mentor'], array_keys($changes));
                    $this->assertSame($this->mentor, $changes['mentor']['old']);
                    $this->assertNull($changes['mentor']['new']);
                } else {
                    $this->fail('addUpdate called more times than expected.');
                }
            });

        $matcher = $this->exactly(2);
        $this->dispatcher->expects($matcher)
            ->method('addPropertyChange')
            ->willReturnCallback(function ($change, $entity, $field, $old, $new) use ($matcher) {
                $this->assertInstanceOf(Change::class, $change);
                $this->assertSame($this->mentor, $entity);
                $this->assertSame('mentoring', $field);

                if ($matcher->numberOfInvocations() === 1) {
                    $this->assertNull($old);
                    $this->assertSame($this->person, $new);
                } elseif ($matcher->numberOfInvocations() === 2) {
                    $this->assertSame($this->person, $old);
                    $this->assertNull($new);
                } else {
                    $this->fail('addPropertyChange called more times than expected.');
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

        $this->classMetadata
            ->method('getName')
            ->willReturn($this->person::class);

        $this->classMetadata->reflFields['father']
            ->method('getValue')
            ->willReturn($this->father);

        $this->dispatcher->expects($this->exactly(1))
            ->method('addUpdate')
            ->with(
                $this->callback(function ($arg) { return $arg instanceof Update; }),
                $this->callback(function ($arg) { return $arg === $this->father; }),
                $this->equalTo([]),
                $this->callback(function ($arg) {
                    return
                        array_keys($arg) === ['sons'] &&
                        $arg['sons']['deleted'] === [] &&
                        $arg['sons']['inserted'] === [$this->person];
                }));

        $this->dispatcher->expects($this->exactly(1))
            ->method('addCollectionChange')
            ->with(
                $this->callback(function ($arg) { return $arg instanceof Change; }),
                $this->callback(function ($arg) { return $arg === $this->father; }),
                $this->equalTo('sons'),
                $this->equalTo([]),
                $this->callback(function ($arg) { return $arg === [$this->person]; }));

        $this->listener->postPersist($event);
    }

    public function testOneToManyPreRemove()
    {
        $event = new PreRemoveEventArgs($this->person, $this->manager);

        $this->manager
            ->method('getClassMetadata')
            ->with($this->person::class)
            ->willReturn($this->classMetadata);

        $this->classMetadata
            ->method('getName')
            ->willReturn($this->person::class);

        $this->classMetadata->reflFields['father']
            ->method('getValue')
            ->willReturn($this->father);

        $this->dispatcher->expects($this->exactly(1))
            ->method('addUpdate')
            ->with(
                $this->callback(function ($arg) { return $arg instanceof Update; }),
                $this->callback(function ($arg) { return $arg === $this->father; }),
                $this->equalTo([]),
                $this->callback(function ($arg) {
                    return
                        array_keys($arg) === ['sons'] &&
                        $arg['sons']['deleted'] === [$this->person] &&
                        $arg['sons']['inserted'] === [];
                }));

        $this->dispatcher->expects($this->exactly(1))
            ->method('addCollectionChange')
            ->with(
                $this->callback(function ($arg) { return $arg instanceof Change; }),
                $this->callback(function ($arg) { return $arg === $this->father; }),
                $this->equalTo('sons'),
                $this->callback(function ($arg) { return $arg === [$this->person]; }),
                $this->equalTo([]));

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

        $matcher = $this->exactly(4);
        $this->dispatcher->expects($matcher)
            ->method('addUpdate')
            ->willReturnCallback(function ($update, $entity, $changes, $collections) use ($matcher) {
                $this->assertInstanceOf(Update::class, $update);

                switch ($matcher->numberOfInvocations()) {
                    case 1:
                        $this->assertSame($this->father, $entity);
                        $this->assertSame([], $changes);

                        $this->assertSame(['sons'], array_keys($collections));
                        $this->assertSame([], $collections['sons']['deleted']);
                        $this->assertSame([$this->person], $collections['sons']['inserted']);
                        break;

                    case 2:
                        $this->assertSame($this->person, $entity);
                        $this->assertSame([], $collections);

                        $this->assertSame(['father'], array_keys($changes));
                        $this->assertNull($changes['father']['old']);
                        $this->assertSame($this->father, $changes['father']['new']);
                        break;

                    case 3:
                        $this->assertSame($this->father, $entity);
                        $this->assertSame([], $changes);

                        $this->assertSame(['sons'], array_keys($collections));
                        $this->assertSame([$this->person], $collections['sons']['deleted']);
                        $this->assertSame([], $collections['sons']['inserted']);
                        break;

                    case 4:
                        $this->assertSame($this->person, $entity);
                        $this->assertSame([], $collections);

                        $this->assertSame(['father'], array_keys($changes));
                        $this->assertSame($this->father, $changes['father']['old']);
                        $this->assertNull($changes['father']['new']);
                        break;

                    default:
                        $this->fail('addUpdate called more times than expected.');
                }
            });

        $matcher = $this->exactly(2);
        $this->dispatcher->expects($matcher)
            ->method('addCollectionChange')
            ->willReturnCallback(function ($change, $entity, $field, $old, $new) use ($matcher) {
                $this->assertInstanceOf(Change::class, $change);
                $this->assertSame($this->father, $entity);
                $this->assertSame('sons', $field);

                match ($matcher->numberOfInvocations()) {
                    1 => [
                        $this->assertSame([], $old),
                        $this->assertSame([$this->person], $new),
                    ],
                    2 => [
                        $this->assertSame([$this->person], $old),
                        $this->assertSame([], $new),
                    ],
                    default => $this->fail('addCollectionChange called more times than expected.'),
                };
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

        $this->classMetadata
            ->method('getName')
            ->willReturn($this->person::class);

        $this->classMetadata->reflFields['friends']
            ->method('getValue')
            ->willReturn(new ArrayCollection([$this->friend1, $this->friend2]));

        $matcher = $this->exactly(2);

        $this->dispatcher->expects($matcher)
            ->method('addUpdate')
            ->willReturnCallback(function ($update, $entity, $changes, $collections) use ($matcher) {
                $this->assertInstanceOf(Update::class, $update);
                $this->assertSame([], $changes);

                $this->assertSame(['friendOf'], array_keys($collections));
                $this->assertSame([], $collections['friendOf']['deleted']);
                $this->assertSame([$this->person], $collections['friendOf']['inserted']);

                switch ($matcher->numberOfInvocations()) {
                    case 1:
                        $this->assertSame($this->friend1, $entity);
                        break;

                    case 2:
                        $this->assertSame($this->friend2, $entity);
                        break;

                    default:
                        $this->fail('addUpdate called more times than expected.');
                }
            });

        $matcher = $this->exactly(2);

        $this->dispatcher->expects($matcher)
            ->method('addCollectionChange')
            ->willReturnCallback(function ($change, $entity, $field, $old, $new) use ($matcher) {
                $this->assertInstanceOf(Change::class, $change);
                $this->assertSame('friendOf', $field);
                $this->assertSame([], $old);
                $this->assertSame([$this->person], $new);

                switch ($matcher->numberOfInvocations()) {
                    case 1:
                        $this->assertSame($this->friend1, $entity);
                        break;

                    case 2:
                        $this->assertSame($this->friend2, $entity);
                        break;

                    default:
                        $this->fail('addCollectionChange called more times than expected.');
                }
            });

        $this->listener->postPersist($event);
    }

    public function testManyToManyPreRemove()
    {
        $event = new PreRemoveEventArgs($this->person, $this->manager);

        $this->manager
            ->method('getClassMetadata')
            ->with($this->person::class)
            ->willReturn($this->classMetadata);

        $this->classMetadata
            ->method('getName')
            ->willReturn($this->person::class);

        $this->classMetadata->reflFields['friends']
            ->method('getValue')
            ->willReturn(new ArrayCollection([$this->friend1, $this->friend2]));

        $updateMatcher = $this->exactly(2);
        $this->dispatcher->expects($updateMatcher)
            ->method('addUpdate')
            ->willReturnCallback(function ($update, $entity, $changes, $collections) use ($updateMatcher) {
                $this->assertInstanceOf(Update::class, $update);
                $this->assertSame([], $changes);

                $this->assertSame(['friendOf'], array_keys($collections));
                $this->assertSame([$this->person], $collections['friendOf']['deleted']);
                $this->assertSame([], $collections['friendOf']['inserted']);

                switch ($updateMatcher->numberOfInvocations()) {
                    case 1:
                        $this->assertSame($this->friend1, $entity);
                        break;

                    case 2:
                        $this->assertSame($this->friend2, $entity);
                        break;

                    default:
                        $this->fail('addUpdate called more times than expected.');
                }
            });

        $collectionMatcher = $this->exactly(2);
        $this->dispatcher->expects($collectionMatcher)
            ->method('addCollectionChange')
            ->willReturnCallback(function ($change, $entity, $field, $old, $new) use ($collectionMatcher) {
                $this->assertInstanceOf(Change::class, $change);
                $this->assertSame('friendOf', $field);
                $this->assertSame([$this->person], $old);
                $this->assertSame([], $new);

                switch ($collectionMatcher->numberOfInvocations()) {
                    case 1:
                        $this->assertSame($this->friend1, $entity);
                        break;

                    case 2:
                        $this->assertSame($this->friend2, $entity);
                        break;

                    default:
                        $this->fail('addCollectionChange called more times than expected.');
                }
            });

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
        $updateMatcher = $this->exactly(3);

        $this->dispatcher->expects($updateMatcher)
            ->method('addUpdate')
            ->willReturnCallback(function ($update, $entity, $changes, $collections) use ($updateMatcher) {
                $this->assertInstanceOf(Update::class, $update);
                $this->assertSame([], $changes);

                switch ($updateMatcher->numberOfInvocations()) {
                    case 1:
                        $this->assertSame($this->friend1, $entity);
                        $this->assertSame(['friendOf'], array_keys($collections));
                        $this->assertSame([], $collections['friendOf']['deleted']);
                        $this->assertSame([$this->person], $collections['friendOf']['inserted']);
                        break;

                    case 2:
                        $this->assertSame($this->friend2, $entity);
                        $this->assertSame(['friendOf'], array_keys($collections));
                        $this->assertSame([], $collections['friendOf']['deleted']);
                        $this->assertSame([$this->person], $collections['friendOf']['inserted']);
                        break;

                    case 3:
                        $this->assertSame($this->person, $entity);
                        $this->assertSame(['friends'], array_keys($collections));
                        $this->assertSame([], $collections['friends']['deleted']);
                        $this->assertSame([$this->friend1, $this->friend2], $collections['friends']['inserted']);
                        break;

                    default:
                        $this->fail('addUpdate called more times than expected.');
                }
            });

        $collectionMatcher = $this->exactly(2);

        $this->dispatcher->expects($collectionMatcher)
            ->method('addCollectionChange')
            ->willReturnCallback(function ($change, $entity, $field, $old, $new) use ($collectionMatcher) {
                $this->assertInstanceOf(Change::class, $change);
                $this->assertSame('friendOf', $field);
                $this->assertSame([], $old);
                $this->assertSame([$this->person], $new);

                switch ($collectionMatcher->numberOfInvocations()) {
                    case 1:
                        $this->assertSame($this->friend1, $entity);
                        break;

                    case 2:
                        $this->assertSame($this->friend2, $entity);
                        break;

                    default:
                        $this->fail('addCollectionChange called more times than expected.');
                }
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
        $pc->add($this->friend2);

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

        $this->classMetadata
            ->method('getName')
            ->willReturn($this->person::class);

        $updateMatcher = $this->exactly(3);
        $this->dispatcher->expects($updateMatcher)
            ->method('addUpdate')
            ->willReturnCallback(function ($update, $entity, $changes, $collections) use ($updateMatcher) {
                $this->assertInstanceOf(Update::class, $update);
                $this->assertSame([], $changes);

                switch ($updateMatcher->numberOfInvocations()) {
                    case 1:
                        $this->assertSame($this->friend1, $entity);
                        $this->assertSame(['friendOf'], array_keys($collections));
                        $this->assertSame([$this->person], $collections['friendOf']['deleted']);
                        $this->assertSame([], $collections['friendOf']['inserted']);
                        break;

                    case 2:
                        $this->assertSame($this->friend2, $entity);
                        $this->assertSame(['friendOf'], array_keys($collections));
                        $this->assertSame([], $collections['friendOf']['deleted']);
                        $this->assertSame([$this->person], $collections['friendOf']['inserted']);
                        break;

                    case 3:
                        $this->assertSame($this->person, $entity);
                        $this->assertSame(['friends'], array_keys($collections));
                        $this->assertSame([$this->friend1], $collections['friends']['deleted']);
                        $this->assertSame([$this->friend2], $collections['friends']['inserted']);
                        break;

                    default:
                        $this->fail('addUpdate called more times than expected.');
                }
            });

        $collectionMatcher = $this->exactly(2);
        $this->dispatcher->expects($collectionMatcher)
            ->method('addCollectionChange')
            ->willReturnCallback(function ($change, $entity, $field, $old, $new) use ($collectionMatcher) {
                $this->assertInstanceOf(Change::class, $change);
                $this->assertSame('friendOf', $field);

                switch ($collectionMatcher->numberOfInvocations()) {
                    case 1:
                        $this->assertSame($this->friend1, $entity);
                        $this->assertSame([$this->person], $old);
                        $this->assertSame([], $new);
                        break;

                    case 2:
                        $this->assertSame($this->friend2, $entity);
                        $this->assertSame([], $old);
                        $this->assertSame([$this->person], $new);
                        break;

                    default:
                        $this->fail('addCollectionChange called more times than expected.');
                }
            });

        $changeSet = [];
        $event     = new PreUpdateEventArgs($this->person, $this->manager, $changeSet);

        $this->listener->preUpdate($event);
    }
}
