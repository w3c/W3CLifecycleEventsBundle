<?php

namespace W3C\LifecycleEventsBundle\Tests\EventListener\Fixtures;

use W3C\LifecycleEventsBundle\Attribute as On;

#[On\Update(monitor_collections: false)]
class UserClassUpdateNoCollection
{
    public $name;

    public $friends;
}
