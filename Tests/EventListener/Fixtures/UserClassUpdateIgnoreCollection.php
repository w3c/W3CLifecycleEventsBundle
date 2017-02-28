<?php

namespace W3C\LifecycleEventsBundle\Tests\EventListener\Fixtures;

use W3C\LifecycleEventsBundle\Annotation as On;

/**
 * @On\Update()
 */
class UserClassUpdateIgnoreCollection
{
    public $name;

    public $email;

    /**
     * @On\IgnoreClassUpdates()
     */
    public $friends;
}
