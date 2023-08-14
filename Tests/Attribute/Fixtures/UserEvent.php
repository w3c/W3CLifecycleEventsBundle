<?php

namespace W3C\LifecycleEventsBundle\Tests\Attribute\Fixtures;

use W3C\LifecycleEventsBundle\Attribute as On;

/**
 * @author Jean-Guilhem Rouel <jean-gui@w3.org>
 */
#[On\Create(event: "foo.bar")]
#[On\Delete(event: "foo.bar")]
#[On\Update(event: "foo.bar")]
class UserEvent
{
    #[On\Change(event: "foo.bar")]
    public $name;
}
