<?php

namespace LifecycleEventsBundle\Tests\Attribute;

use PHPUnit\Framework\TestCase;
use W3C\LifecycleEventsBundle\Attribute\Create;
use W3C\LifecycleEventsBundle\Tests\Attribute\Fixtures\User;
use W3C\LifecycleEventsBundle\Tests\Attribute\Fixtures\UserClass;
use W3C\LifecycleEventsBundle\Tests\Attribute\Fixtures\UserErrorCreate;
use W3C\LifecycleEventsBundle\Tests\Attribute\Fixtures\UserEvent;
use W3C\LifecycleEventsBundle\Tests\Attribute\Fixtures\UserEvent2;

/**
 * @author Jean-Guilhem Rouel <jean-gui@w3.org>
 */
class CreateTest extends TestCase
{
    public function testNoParams()
    {
        $create = new Create();

        $reflection = new \ReflectionClass(User::class);
        $attribute = $reflection->getAttributes(Create::class)[0]->newInstance();

        $this->assertEquals($attribute->class, $create->class);
        $this->assertEquals($attribute->event, $create->event);
    }

    public function testClass()
    {
        $reflection = new \ReflectionClass(UserClass::class);
        $attribute = $reflection->getAttributes(Create::class)[0]->newInstance();

        $this->assertEquals($attribute->class, 'FooBar');
    }

    public function testEvent()
    {
        $reflection = new \ReflectionClass(UserEvent::class);
        $attribute = $reflection->getAttributes(Create::class)[0]->newInstance();

        $this->assertEquals($attribute->event, 'foo.bar');


        $reflection = new \ReflectionClass(UserEvent2::class);
        $attribute = $reflection->getAttributes(Create::class)[0]->newInstance();

        $this->assertEquals($attribute->event, 'foo.bar');
    }

    public function testError()
    {
        $this->expectException(\Error::class);

        $reflection = new \ReflectionProperty(UserErrorCreate::class, 'name');
        $reflection->getAttributes(Create::class)[0]->newInstance();
    }
}
