<?php

namespace LifecycleEventsBundle\Tests\Attribute;

use PHPUnit\Framework\TestCase;
use W3C\LifecycleEventsBundle\Annotation\Change;
use W3C\LifecycleEventsBundle\Tests\Attribute\Fixtures\User;
use W3C\LifecycleEventsBundle\Tests\Attribute\Fixtures\UserClass;
use W3C\LifecycleEventsBundle\Tests\Attribute\Fixtures\UserErrorChange;
use W3C\LifecycleEventsBundle\Tests\Attribute\Fixtures\UserEvent;
use W3C\LifecycleEventsBundle\Tests\Attribute\Fixtures\UserEvent2;

/**
 * @author Jean-Guilhem Rouel <jean-gui@w3.org>
 */
class ChangeTest extends TestCase
{
    public function testNoParams()
    {
        $change = new Change();

        $reflection = new \ReflectionProperty(User::class, 'name');
        $attribute = $reflection->getAttributes()[0]->newInstance();

        $this->assertEquals($attribute->class, $change->class);
        $this->assertEquals($attribute->event, $change->event);
    }

    public function testClass()
    {
        $reflection = new \ReflectionProperty(UserClass::class, 'name');
        $attribute = $reflection->getAttributes()[0]->newInstance();

        $this->assertEquals($attribute->class, 'FooBar');
    }

    public function testEvent()
    {
        $reflection = new \ReflectionProperty(UserEvent::class, 'name');
        $attribute = $reflection->getAttributes()[0]->newInstance();

        $this->assertEquals($attribute->event, 'foo.bar');


        $reflection = new \ReflectionProperty(UserEvent2::class, 'name');
        $attribute = $reflection->getAttributes()[0]->newInstance();

        $this->assertEquals($attribute->event, 'foo.bar');
    }

    public function testError()
    {
        $this->expectException(\Error::class);

        $reflection = new \ReflectionClass(UserErrorChange::class);
        $attribute = $reflection->getAttributes()[0]->newInstance();

        var_dump($attribute);
    }
}
