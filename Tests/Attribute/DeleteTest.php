<?php

namespace LifecycleEventsBundle\Tests\Attribute;

use PHPUnit\Framework\TestCase;
use W3C\LifecycleEventsBundle\Annotation\Delete;
use W3C\LifecycleEventsBundle\Tests\Attribute\Fixtures\User;
use W3C\LifecycleEventsBundle\Tests\Attribute\Fixtures\UserClass;
use W3C\LifecycleEventsBundle\Tests\Attribute\Fixtures\UserErrorDelete;
use W3C\LifecycleEventsBundle\Tests\Attribute\Fixtures\UserEvent;
use W3C\LifecycleEventsBundle\Tests\Attribute\Fixtures\UserEvent2;

/**
 * @author Jean-Guilhem Rouel <jean-gui@w3.org>
 */
class DeleteTest extends TestCase
{
    public function testNoParams()
    {
        $delete = new Delete();

        $reflection = new \ReflectionClass(User::class);
        $attribute = $reflection->getAttributes(Delete::class)[0]->newInstance();

        $this->assertEquals($attribute->class, $delete->class);
        $this->assertEquals($attribute->event, $delete->event);
    }

    public function testClass()
    {
        $reflection = new \ReflectionClass(UserClass::class);
        $attribute = $reflection->getAttributes(Delete::class)[0]->newInstance();

        $this->assertEquals($attribute->class, 'FooBar');
    }

    public function testEvent()
    {
        $reflection = new \ReflectionClass(UserEvent::class);
        $attribute = $reflection->getAttributes(Delete::class)[0]->newInstance();

        $this->assertEquals($attribute->event, 'foo.bar');


        $reflection = new \ReflectionClass(UserEvent2::class);
        $attribute = $reflection->getAttributes(Delete::class)[0]->newInstance();

        $this->assertEquals($attribute->event, 'foo.bar');
    }

    public function testError()
    {
        $this->expectException(\Error::class);

        $reflection = new \ReflectionProperty(UserErrorDelete::class, 'name');
        $reflection->getAttributes(Delete::class)[0]->newInstance();
    }
}
