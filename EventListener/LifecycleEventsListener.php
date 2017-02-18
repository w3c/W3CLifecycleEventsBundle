<?php
/**
 * LifecycleEventsListener.php
 *
 * @author Jean-Guilhem Rouel <jean-gui@w3.org>
 *
 * @copyright Copyright <C2><A9> 2014 W3C <C2><AE> (MIT, ERCIM, Keio) {@link http://www.w3.org/Consortium/Legal/2002/ipr-notice-20021231 Usage policies apply}.
 */
namespace W3C\LifecycleEventsBundle\EventListener;

use Doctrine\Common\Annotations\Reader;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\PersistentCollection;
use W3C\LifecycleEventsBundle\Annotation\Create;
use W3C\LifecycleEventsBundle\Annotation\Delete;
use W3C\LifecycleEventsBundle\Annotation\Update;
use W3C\LifecycleEventsBundle\Services\LifecycleEventsDispatcher;

/**
 * Listen to Doctrine postPersist, postRemove and preUpdate to feed a LifecycleEventsDispatcher
 */
class LifecycleEventsListener
{
    /**
     * Events dispatcher
     *
     * @var LifecycleEventsDispatcher
     */
    private $dispatcher;

    /**
     * @var Reader
     */
    private $reader;

    /**
     * Constructs a new instance
     *
     * @param LifecycleEventsDispatcher $dispatcher the dispatcher to fed
     * @param Reader $reader
     */
    public function __construct(LifecycleEventsDispatcher $dispatcher, Reader $reader)
    {
        $this->dispatcher = $dispatcher;
        $this->reader     = $reader;
    }

    /**
     * Called upon receiving postPersist events
     *
     * @param LifecycleEventArgs $args event to feed the dispatcher with
     */
    public function postPersist(LifecycleEventArgs $args)
    {
        $annotation = $this->reader->getClassAnnotation(
            new \ReflectionClass(get_class($args->getEntity())),
            Create::class
        );
        if ($annotation) {
            $this->dispatcher->getCreations()->add([$annotation, $args]);
        }
    }

    /**
     * Called upon receiving postRemove events
     *
     * @param LifecycleEventArgs $args event to feed the dispatcher with
     */
    public function postRemove(LifecycleEventArgs $args)
    {
        $annotation = $this->reader->getClassAnnotation(
            new \ReflectionClass(get_class($args->getEntity())),
            Delete::class
        );
        if ($annotation) {
            $this->dispatcher->getDeletions()->add([$annotation, $args]);
        }
    }

    /**
     * Called upon receiving preUpdate events
     *
     * @param PreUpdateEventArgs $args event to feed the dispatcher with
     */
    public function preUpdate(PreUpdateEventArgs $args)
    {
        $annotation = $this->reader->getClassAnnotation(
            new \ReflectionClass(get_class($args->getEntity())),
            Update::class
        );
        if ($annotation) {
            $collectionsChanges = null;
            /** @var PersistentCollection $u */
            foreach ($args->getEntityManager()->getUnitOfWork()->getScheduledCollectionUpdates() as $u) {
                $collectionsChanges[$u->getMapping()['fieldName']] = [
                    'deleted'  => $u->getDeleteDiff(),
                    'inserted' => $u->getInsertDiff()
                ];
            }
            $this->dispatcher->getUpdates()->add([
                $annotation,
                $args->getEntity(),
                $args->getEntityChangeSet(),
                $collectionsChanges
            ]);
        }
    }

    /**
     * Called upon receiving postFlush events
     * Dispatches all gathered events
     *
     * @param PostFlushEventArgs $args post flush event
     */
    public function postFlush(PostFlushEventArgs $args)
    {
        if ($this->dispatcher->getAutoDispatch()) {
            $this->dispatcher->preAutoDispatch();
            $this->dispatcher->dispatchEvents();
        }
    }

}

?>