<?php

namespace W3C\LifecycleEventsBundle\Tests\Services;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\ClassMetadata;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use W3C\LifecycleEventsBundle\Annotation\Change;
use W3C\LifecycleEventsBundle\Annotation\Create;
use W3C\LifecycleEventsBundle\Annotation\Update;
use W3C\LifecycleEventsBundle\Services\AnnotationGetter;
use W3C\LifecycleEventsBundle\Tests\EventListener\Fixtures\UserChange;

/**
 * @author Jean-Guilhem Rouel <jean-gui@w3.org>
 */
class AnnotationGetterTest extends TestCase
{
    /**
     * @var AnnotationGetter
     */
    private $annotationGetter;

    /**
     * @var ClassMetadata|MockObject
     */
    private $classMetadata;

    public function setUp() : void
    {
        parent::setUp();

        $this->classMetadata = $this
            ->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();

        $reader = new AnnotationReader();
        $this->annotationGetter = new AnnotationGetter($reader);
    }

    public function testGetAnnotation()
    {
        $annotation = $this->annotationGetter->getAnnotation(
            'W3C\LifecycleEventsBundle\Tests\Attribute\Fixtures\Person',
            Update::class
        );

        $this->assertEquals(Update::class, get_class($annotation));

        $annotation = $this->annotationGetter->getAnnotation(
            'W3C\LifecycleEventsBundle\Tests\Attribute\Fixtures\Person',
            Create::class
        );

        $this->assertNull($annotation);
    }

    public function testGetPropertyAnnotationOk()
    {
        $user = new UserChange();

        $this->classMetadata
            ->method('getReflectionProperty')
            ->with('name')
            ->willReturn(new \ReflectionProperty($user, 'name'));

        $annotation = $this->annotationGetter->getPropertyAnnotation($this->classMetadata, 'name', Change::class);

        $this->assertEquals(Change::class, get_class($annotation));
    }

    public function testGetPropertyAnnotationNoAnnotation()
    {
        $user = new UserChange();

        $this->classMetadata
            ->method('getReflectionProperty')
            ->with('email')
            ->willReturn(new \ReflectionProperty($user, 'email'));

        $annotation = $this->annotationGetter->getPropertyAnnotation($this->classMetadata, 'email', Change::class);

        $this->assertNull($annotation);
    }

    public function testGetPropertyAnnotationNoField()
    {
        $this->expectException(\ReflectionException::class);

        $this->classMetadata
            ->method('getReflectionProperty')
            ->with('foo')
            ->willReturn(null);
        $this->classMetadata
            ->method('getName')
            ->willReturn(UserChange::class);

        $this->annotationGetter->getPropertyAnnotation($this->classMetadata, 'foo', Change::class);
    }
}
