<?php

namespace W3C\LifecycleEventsBundle\Tests\Services;

use Doctrine\ORM\Mapping\ClassMetadata;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use W3C\LifecycleEventsBundle\Attribute\Change;
use W3C\LifecycleEventsBundle\Attribute\Create;
use W3C\LifecycleEventsBundle\Attribute\Update;
use W3C\LifecycleEventsBundle\Services\AttributeGetter;
use W3C\LifecycleEventsBundle\Tests\EventListener\Fixtures\UserChange;

/**
 * @author Jean-Guilhem Rouel <jean-gui@w3.org>
 */
class AttributeGetterTest extends TestCase
{
    /**
     * @var AttributeGetter
     */
    private $attributeGetter;

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

        $this->attributeGetter = new AttributeGetter();
    }

    public function testGetAnnotation()
    {
        $attribute = $this->attributeGetter->getAnnotation(
            'W3C\LifecycleEventsBundle\Tests\Attribute\Fixtures\Person',
            Update::class
        );

        $this->assertEquals(Update::class, get_class($attribute));

        $attribute = $this->attributeGetter->getAnnotation(
            'W3C\LifecycleEventsBundle\Tests\Attribute\Fixtures\Person',
            Create::class
        );

        $this->assertNull($attribute);
    }

    public function testGetPropertyAnnotationOk()
    {
        $user = new UserChange();

        $this->classMetadata
            ->method('getReflectionProperty')
            ->with('name')
            ->willReturn(new \ReflectionProperty($user, 'name'));

        $attribute = $this->attributeGetter->getPropertyAnnotation($this->classMetadata, 'name', Change::class);

        $this->assertEquals(Change::class, get_class($attribute));
    }

    public function testGetPropertyAnnotationNoAnnotation()
    {
        $user = new UserChange();

        $this->classMetadata
            ->method('getReflectionProperty')
            ->with('email')
            ->willReturn(new \ReflectionProperty($user, 'email'));

        $attribute = $this->attributeGetter->getPropertyAnnotation($this->classMetadata, 'email', Change::class);

        $this->assertNull($attribute);
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

        $this->attributeGetter->getPropertyAnnotation($this->classMetadata, 'foo', Change::class);
    }
}
