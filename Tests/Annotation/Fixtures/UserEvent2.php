<?php

namespace W3C\LifecycleEventsBundle\Tests\Annotation\Fixtures;

use W3C\LifecycleEventsBundle\Annotation as On;

/**
 * @On\Create("foo.bar")
 * @On\Delete("foo.bar")
 * @On\Update("foo.bar")
 *
 * @author Jean-Guilhem Rouel <jean-gui@w3.org>
 */
class UserEvent2
{
    /**
     * @On\Change("foo.bar")
     */
    public $name;
}