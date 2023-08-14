<?php

namespace W3C\LifecycleEventsBundle\Tests\EventListener\Fixtures;

use W3C\LifecycleEventsBundle\Annotation as On;

#[On\Update(monitor_collections: false)]
class UserClassUpdateIgnoreNoCollection
{
    #[On\IgnoreClassUpdates]
    public $name;

    public $email;

    public $friends;
}
