<?php

namespace W3C\LifecycleEventsBundle\Tests\Annotation\Fixtures;

use W3C\LifecycleEventsBundle\Annotation as On;

/**
 * @On\IgnoreClassUpdates()
 *
 * @author Jean-Guilhem Rouel <jean-gui@w3.org>
 */
class UserErrorIgnore
{
    public $name;
}
