<?php

namespace W3C\LifecycleEventsBundle\Tests\Attribute\Fixtures;

use W3C\LifecycleEventsBundle\Attribute as On;

/**
 * @author Jean-Guilhem Rouel <jean-gui@w3.org>
 */
#[On\Create(class: "FooBar")]
#[On\Delete(class: "FooBar")]
#[On\Update(class: "FooBar")]
class UserClass
{
    #[On\Change(class: "FooBar")]
    public $name;
}
