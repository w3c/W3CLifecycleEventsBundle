<?php
/**
 * PreDispatch.php
 *
 * @author Jean-Guilhem Rouel <jean-gui@w3.org>
 *
 * @copyright Copyright <C2><A9> 2011 W3C <C2><AE> (MIT, ERCIM, Keio) {@link http://www.w3.org/Consortium/Legal/2002/ipr-notice-20021231 Usage policies apply}.
 */
namespace W3C\LifecycleEventsBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use W3C\LifecycleEventsBundle\Services\LifecycleEventsDispatcher;

/**
 * LifecycleEvent is used when an entity is created or deleted
 */
class PreDispatchEvent extends Event
{

    /**
     * @var LifecycleEventsDispatcher
     */
    protected $dispatcher;

    /**
     * @param $dispatcher
     */
    public function __construct(LifecycleEventsDispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * @return mixed
     */
    public function getDispatcher()
    {
        return $this->getDispatcher();
    }
}