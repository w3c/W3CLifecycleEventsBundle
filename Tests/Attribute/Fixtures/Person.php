<?php

namespace W3C\LifecycleEventsBundle\Tests\Attribute\Fixtures;

use W3C\LifecycleEventsBundle\Annotation as On;

/**
 * @author Jean-Guilhem Rouel <jean-gui@w3.org>
 */
#[On\Update(monitor_owning: true)]
class Person
{
    public $name;

    /**
     * @ ORM\ManyToMany(targetEntity="Person", inversedBy="friendOf")
     */
    public $friends;

    /**
     * @ ORM\ManyToMany(targetEntity="Person", mappedBy="friends")
     */
    #[On\Change(monitor_owning: true)]
    public $friendOf;

    /**
     * @ ORM\ManyToOne(targetEntity="Person", inversedBy="sons")
     */
    public $father;

    /**
     * @ ORM\OneToMany(targetEntity="Person", mappedBy="father")
     */
    #[On\Change(monitor_owning: true)]
    public $sons;

    /**
     * @ ORM\OneToOne(targetEntity="Person", inversedBy="mentoring")
     */
    public $mentor;

    /**
     * @ ORM\OneToOne(targetEntity="Person", mappedBy="mentor")
     */
    #[On\Change(monitor_owning: true)]
    public $mentoring;
}
