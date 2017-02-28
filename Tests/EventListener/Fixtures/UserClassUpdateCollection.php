<?php

namespace W3C\LifecycleEventsBundle\Tests\EventListener\Fixtures;

use W3C\LifecycleEventsBundle\Annotation as On;

/**
 * @On\Update()
 */
class UserClassUpdateCollection
{
    public $name;

    public $email;

    public $friends;
}
