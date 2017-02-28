<?php

namespace W3C\LifecycleEventsBundle\Tests\EventListener\Fixtures;

use W3C\LifecycleEventsBundle\Annotation as On;

/**
 * @author Jean-Guilhem Rouel <jean-gui@w3.org>
 */
class UserChange
{
    /**
     * @On\Change()
     */
    public $name;

    public $email;

    /**
     * @On\Change()
     */
    public $friends;
}