<?php

namespace W3C\LifecycleEventsBundle\Tests\Annotation\Fixtures;

use W3C\LifecycleEventsBundle\Annotation as On;

/**
 * @On\Create(class="FooBar")
 * @On\Delete(class="FooBar")
 * @On\Update(class="FooBar")
 *
 * @author Jean-Guilhem Rouel <jean-gui@w3.org>
 */
class UserClass
{
    /**
     * @On\Change(class="FooBar")
     */
    public $name;
}