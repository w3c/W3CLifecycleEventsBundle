<?php

namespace W3C\LifecycleEventsBundle\Tests\Annotation\Fixtures;

use W3C\LifecycleEventsBundle\Annotation as On;

/**
 * @On\Create()
 * @On\Delete()
 * @On\Update()
 *
 * @author Jean-Guilhem Rouel <jean-gui@w3.org>
 */
class User
{
    /**
     * @On\Change()
     * @On\IgnoreClassUpdates()
     */
    public $name;

    public $friends;
}
