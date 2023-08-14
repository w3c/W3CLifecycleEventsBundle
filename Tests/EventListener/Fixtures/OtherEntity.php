<?php

namespace W3C\LifecycleEventsBundle\Tests\EventListener\Fixtures;

use W3C\LifecycleEventsBundle\Annotation as On;

/**
 * @author Jean-Guilhem Rouel <jean-gui@w3.org>
 */
class OtherEntity
{
    #[On\Change]
    public $foo;
}
