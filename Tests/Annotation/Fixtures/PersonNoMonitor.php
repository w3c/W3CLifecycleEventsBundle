<?php

namespace W3C\LifecycleEventsBundle\Tests\Annotation\Fixtures;

use W3C\LifecycleEventsBundle\Annotation as On;

/**
 * @On\Update()
 *
 * @author Jean-Guilhem Rouel <jean-gui@w3.org>
 */
class PersonNoMonitor
{
    public $name;

    /**
     * @ ORM\ManyToMany(targetEntity="Person", inversedBy="friendOf")
     */
    public $friends;

    /**
     * @ ORM\ManyToMany(targetEntity="Person", mappedBy="friends")
     */
    public $friendOf;

    /**
     * @ ORM\ManyToOne(targetEntity="Person", inversedBy="sons")
     * @On\Change()
     */
    public $father;

    /**
     * @ ORM\OneToMany(targetEntity="Person", mappedBy="father")
     */
    public $sons;

    /**
     * @ ORM\OneToOne(targetEntity="Person", inversedBy="mentoring")
     */
    public $mentor;

    /**
     * @ ORM\OneToOne(targetEntity="Person", mappedBy="mentor")
     */
    public $mentoring;
}
