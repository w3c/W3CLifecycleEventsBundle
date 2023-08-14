<?php

namespace LifecycleEventsBundle\Tests\Attribute;

use PHPUnit\Framework\TestCase;
use W3C\LifecycleEventsBundle\Annotation\IgnoreClassUpdates;
use W3C\LifecycleEventsBundle\Tests\Attribute\Fixtures\User;
use W3C\LifecycleEventsBundle\Tests\Attribute\Fixtures\UserErrorIgnore;

/**
 * @author Jean-Guilhem Rouel <jean-gui@w3.org>
 */
class IgnoreTest extends TestCase
{
    public function testNoParams()
    {
        $reflection = new \ReflectionProperty(User::class, 'name');
        $attribute = $reflection->getAttributes(IgnoreClassUpdates::class)[0]->newInstance();

        $this->assertNotNull($attribute);
    }

    public function testError()
    {
        $this->expectException(\Error::class);

        $reflection = new \ReflectionClass(UserErrorIgnore::class);
        $reflection->getAttributes(IgnoreClassUpdates::class)[0]->newInstance();
    }
}
