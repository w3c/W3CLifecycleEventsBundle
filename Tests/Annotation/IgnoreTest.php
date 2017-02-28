<?php

namespace LifecycleEventsBundle\Tests\Annotation;

use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\Reader;
use PHPUnit\Framework\TestCase;
use W3C\LifecycleEventsBundle\Annotation\IgnoreClassUpdates;
use W3C\LifecycleEventsBundle\Annotation\Update;
use W3C\LifecycleEventsBundle\Tests\Annotation\Fixtures\User;
use W3C\LifecycleEventsBundle\Tests\Annotation\Fixtures\UserErrorIgnore;

/**
 * @author Jean-Guilhem Rouel <jean-gui@w3.org>
 */
class IgnoreTest extends TestCase
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
        /** @var Update $annotation */
        $annotation = $this->reader->getPropertyAnnotation(
            new \ReflectionProperty(User::class, 'name'),
            IgnoreClassUpdates::class
        );

        $this->assertNotNull($annotation);
    }

    public function testError()
    {
        $this->expectException(AnnotationException::class);

        $this->reader->getClassAnnotation(
            new \ReflectionClass(UserErrorIgnore::class),
            IgnoreClassUpdates::class
        );
    }
}
