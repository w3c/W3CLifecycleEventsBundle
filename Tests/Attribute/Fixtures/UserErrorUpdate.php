<?php

namespace W3C\LifecycleEventsBundle\Tests\Attribute\Fixtures;

use W3C\LifecycleEventsBundle\Annotation as On;

/**
 * @author Jean-Guilhem Rouel <jean-gui@w3.org>
 */
class UserErrorUpdate
{
    #[On\Update]
    public $name;
}
