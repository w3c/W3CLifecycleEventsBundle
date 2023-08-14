<?php

namespace W3C\LifecycleEventsBundle\Tests\Attribute\Fixtures;

use W3C\LifecycleEventsBundle\Attribute as On;

/**
 * @author Jean-Guilhem Rouel <jean-gui@w3.org>
 */
#[On\Create("foo.bar")]
#[On\Delete("foo.bar")]
#[On\Update("foo.bar")]
class UserEvent2
{
    #[On\Change("foo.bar")]
    public $name;
}
