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

namespace LifecycleEventsBundle\Tests\Annotation;

use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\Reader;
use PHPUnit\Framework\TestCase;
use W3C\LifecycleEventsBundle\Annotation\Create;
use W3C\LifecycleEventsBundle\Tests\Annotation\Fixtures\User;
use W3C\LifecycleEventsBundle\Tests\Annotation\Fixtures\UserClass;
use W3C\LifecycleEventsBundle\Tests\Annotation\Fixtures\UserErrorCreate;
use W3C\LifecycleEventsBundle\Tests\Annotation\Fixtures\UserEvent;
use W3C\LifecycleEventsBundle\Tests\Annotation\Fixtures\UserEvent2;


class CreateTest extends TestCase
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
        $create = new Create();

        /** @var Create $annotation */
        $annotation = $this->reader->getClassAnnotation(
            new \ReflectionClass(User::class),
            Create::class
        );

        $this->assertEquals($annotation->class, $create->class);
        $this->assertEquals($annotation->event, $create->event);
    }

    public function testClass()
    {
        /** @var Create $annotation */
        $annotation = $this->reader->getClassAnnotation(
            new \ReflectionClass(UserClass::class),
            Create::class
        );

        $this->assertEquals($annotation->class, 'FooBar');
    }

    public function testEvent()
    {
        /** @var Create $annotation */
        $annotation = $this->reader->getClassAnnotation(
            new \ReflectionClass(UserEvent::class),
            Create::class
        );

        $this->assertEquals($annotation->event, 'foo.bar');

        $annotation = $this->reader->getClassAnnotation(
            new \ReflectionClass(UserEvent2::class),
            Create::class
        );

        $this->assertEquals($annotation->event, 'foo.bar');
    }

    public function testError()
    {
        $this->expectException(AnnotationException::class);

        $this->reader->getPropertyAnnotation(
            new \ReflectionProperty(UserErrorCreate::class, 'name'),
            Create::class
        );
    }
}
