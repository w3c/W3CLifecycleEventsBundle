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
use W3C\LifecycleEventsBundle\Annotation\IgnoreClassUpdates;
use W3C\LifecycleEventsBundle\Annotation\Update;
use W3C\LifecycleEventsBundle\Tests\Annotation\Fixtures\User;
use W3C\LifecycleEventsBundle\Tests\Annotation\Fixtures\UserErrorIgnore;


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
