<?php

namespace W3C\LifecycleEventsBundle\Tests\Annotation\Fixtures;

use W3C\LifecycleEventsBundle\Annotation as On;

/**
 * @On\Create(event="foo.bar")
 * @On\Delete(event="foo.bar")
 * @On\Update(event="foo.bar")
 *
 * @author Jean-Guilhem Rouel <jean-gui@w3.org>
 */
class UserEvent
{
    /**
     * @On\Change(event="foo.bar")
     */
    public $name;
}