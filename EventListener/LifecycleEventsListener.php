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
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\PersistentCollection;
use W3C\LifecycleEventsBundle\Annotation\Create;
use W3C\LifecycleEventsBundle\Annotation\Delete;
use W3C\LifecycleEventsBundle\Annotation\IgnoreClassUpdates;
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
        $class      = ClassUtils::getRealClass(get_class($args->getEntity()));
        $annotation = $this->reader->getClassAnnotation(
            new \ReflectionClass($class),
            Create::class
        );
        if ($annotation) {
            $this->dispatcher->addCreation([$annotation, $args]);
        }
    }

    /**
     * Called upon receiving postRemove events
     *
     * @param LifecycleEventArgs $args event to feed the dispatcher with
     */
    public function postRemove(LifecycleEventArgs $args)
    {
        $class      = ClassUtils::getRealClass(get_class($args->getEntity()));
        $annotation = $this->reader->getClassAnnotation(
            new \ReflectionClass($class),
            Delete::class
        );
        if ($annotation) {
            $this->dispatcher->addDeletion([$annotation, $args]);
        }
    }

    /**
     * Called upon receiving preUpdate events
     *
     * @param PreUpdateEventArgs $args event to feed the dispatcher with
     */
    public function preUpdate(PreUpdateEventArgs $args)
    {
        $entity = $args->getEntity();
        $class  = ClassUtils::getRealClass(get_class($entity));

        /** @var Update $annotation */
        $annotation               = $this->reader->getClassAnnotation(
            new \ReflectionClass($class),
            Update::class
        );
        if ($annotation && $annotation->monitor_collections) {
            // Build list of collection changes with @IgnoreclassUpdates properties removed
            $collectionsChanges = null;
            /** @var PersistentCollection $u */
            foreach ($args->getEntityManager()->getUnitOfWork()->getScheduledCollectionUpdates() as $u) {
                // Make sure $u belongs to the entity we are working on
                if ($u->getOwner() !== $entity) {
                    continue;
                }

                $property         = $u->getMapping()['fieldName'];
                $ignoreAnnotation = $this->reader->getPropertyAnnotation(
                    new \ReflectionProperty($class, $property),
                    IgnoreClassUpdates::class
                );

                if (!$ignoreAnnotation) {
                    $collectionsChanges[$property] = [
                        'deleted'  => $u->getDeleteDiff(),
                        'inserted' => $u->getInsertDiff()
                    ];
                }
            }

            // Build list of changes with @IgnoreclassUpdates properties removed
            $changes = [];
            foreach ($args->getEntityChangeSet() as $property => $change) {
                $ignoreAnnotation = $this->reader->getPropertyAnnotation(
                    new \ReflectionProperty($class, $property),
                    IgnoreClassUpdates::class
                );

                if (!$ignoreAnnotation) {
                    $changes[$property] = $change;
                }
            }

            $this->dispatcher->addUpdate([
                $annotation,
                $entity,
                $changes,
                $collectionsChanges
            ]);
        }
    }
}

?>