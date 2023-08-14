<?php

namespace W3C\LifecycleEventsBundle\Tests\Attribute\Fixtures;

use W3C\LifecycleEventsBundle\Attribute as On;

/**
 * @author Jean-Guilhem Rouel <jean-gui@w3.org>
 */
#[On\Change]
class UserErrorChange
{
    public $name;
}
