<?php

namespace LifecycleEventsBundle\Tests\Annotation;

use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\Reader;
use PHPUnit\Framework\TestCase;
use W3C\LifecycleEventsBundle\Annotation\Delete;
use W3C\LifecycleEventsBundle\Tests\Annotation\Fixtures\User;
use W3C\LifecycleEventsBundle\Tests\Annotation\Fixtures\UserClass;
use W3C\LifecycleEventsBundle\Tests\Annotation\Fixtures\UserErrorDelete;
use W3C\LifecycleEventsBundle\Tests\Annotation\Fixtures\UserEvent;
use W3C\LifecycleEventsBundle\Tests\Annotation\Fixtures\UserEvent2;

/**
 * @author Jean-Guilhem Rouel <jean-gui@w3.org>
 */
class DeleteTest extends TestCase
{
    /**
     * @var Reader
     */
    private $reader;

    public function setUp() : void
    {
        $loader = require __DIR__ . '/../../vendor/autoload.php';
        AnnotationRegistry::registerLoader(array($loader, 'loadClass'));

        $this->reader = new AnnotationReader();
    }

    public function testNoParams()
    {
        $delete = new Delete();

        /** @var Delete $annotation */
        $annotation = $this->reader->getClassAnnotation(
            new \ReflectionClass(User::class),
            Delete::class
        );

        $this->assertEquals($annotation->class, $delete->class);
        $this->assertEquals($annotation->event, $delete->event);
    }

    public function testClass()
    {
        /** @var Delete $annotation */
        $annotation = $this->reader->getClassAnnotation(
            new \ReflectionClass(UserClass::class),
            Delete::class
        );

        $this->assertEquals($annotation->class, 'FooBar');
    }

    public function testEvent()
    {
        /** @var Delete $annotation */
        $annotation = $this->reader->getClassAnnotation(
            new \ReflectionClass(UserEvent::class),
            Delete::class
        );

        $this->assertEquals($annotation->event, 'foo.bar');

        $annotation = $this->reader->getClassAnnotation(
            new \ReflectionClass(UserEvent2::class),
            Delete::class
        );

        $this->assertEquals($annotation->event, 'foo.bar');
    }

    public function testError()
    {
        $this->expectException(AnnotationException::class);

        $this->reader->getPropertyAnnotation(
            new \ReflectionProperty(UserErrorDelete::class, 'name'),
            Delete::class
        );
    }
}
