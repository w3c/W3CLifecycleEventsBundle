<?php

namespace W3C\LifecycleEventsBundle\Tests\EventListener\Fixtures;

use W3C\LifecycleEventsBundle\Attribute as On;

#[On\Update]
class UserClassUpdateCollection
{
    public $name;

    public $email;

    public $friends;
}
