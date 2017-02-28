<?php

namespace LifecycleEventsBundle\Tests\Annotation;

use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\Reader;
use PHPUnit\Framework\TestCase;
use W3C\LifecycleEventsBundle\Annotation\Change;
use W3C\LifecycleEventsBundle\Tests\Annotation\Fixtures\User;
use W3C\LifecycleEventsBundle\Tests\Annotation\Fixtures\UserClass;
use W3C\LifecycleEventsBundle\Tests\Annotation\Fixtures\UserErrorChange;
use W3C\LifecycleEventsBundle\Tests\Annotation\Fixtures\UserEvent;
use W3C\LifecycleEventsBundle\Tests\Annotation\Fixtures\UserEvent2;

/**
 * @author Jean-Guilhem Rouel <jean-gui@w3.org>
 */
class ChangeTest extends TestCase
{
    /**
     * @var Reader
     */
    private $reader;

    public function setUp()
    {
        $loader = require __DIR__ . '/../../vendor/autoload.php';
        AnnotationRegistry::registerLoader(array($loader, 'loadClass'));

        $this->reader = new AnnotationReader();
    }

    public function testNoParams()
    {
        $change = new Change();

        /** @var Change $annotation */
        $annotation = $this->reader->getPropertyAnnotation(
            new \ReflectionProperty(User::class, 'name'),
            Change::class
        );

        $this->assertEquals($annotation->class, $change->class);
        $this->assertEquals($annotation->event, $change->event);
    }

    public function testClass()
    {
        /** @var Change $annotation */
        $annotation = $this->reader->getPropertyAnnotation(
            new \ReflectionProperty(UserClass::class, 'name'),
            Change::class
        );

        $this->assertEquals($annotation->class, 'FooBar');
    }

    public function testEvent()
    {
        /** @var Change $annotation */
        $annotation = $this->reader->getPropertyAnnotation(
            new \ReflectionProperty(UserEvent::class, 'name'),
            Change::class
        );

        $this->assertEquals($annotation->event, 'foo.bar');

        $annotation = $this->reader->getPropertyAnnotation(
            new \ReflectionProperty(UserEvent2::class, 'name'),
            Change::class
        );

        $this->assertEquals($annotation->event, 'foo.bar');
    }

    public function testError()
    {
        $this->expectException(AnnotationException::class);

        $this->reader->getClassAnnotation(
            new \ReflectionClass(UserErrorChange::class),
            Change::class
        );
    }
}
