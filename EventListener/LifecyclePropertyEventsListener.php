<?php

namespace W3C\LifecycleEventsBundle\EventListener;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\PersistentCollection;
use W3C\LifecycleEventsBundle\Annotation\Change;
use W3C\LifecycleEventsBundle\Services\AnnotationGetter;
use W3C\LifecycleEventsBundle\Services\LifecycleEventsDispatcher;

/**
 * Listen to Doctrine preUpdate to feed a LifecycleEventsDispatcher
 *
 * @author Jean-Guilhem Rouel <jean-gui@w3.org>
 */
class LifecyclePropertyEventsListener
{
    /**
     * Events dispatcher
     *
     * @var LifecycleEventsDispatcher
     */
    private $dispatcher;

    /**
     * @var AnnotationGetter
     */
    private $annotationGetter;

    /**
     * Constructs a new instance
     *
     * @param LifecycleEventsDispatcher $dispatcher the dispatcher to feed
     * @param AnnotationGetter $annotationGetter
     */
    public function __construct(LifecycleEventsDispatcher $dispatcher, AnnotationGetter $annotationGetter)
    {
        $this->dispatcher       = $dispatcher;
        $this->annotationGetter = $annotationGetter;
    }

    public function preUpdate(PreUpdateEventArgs $args)
    {
        $this->addPropertyChanges($args);
        $this->addCollectionChanges($args);
    }

    /**
     * @param PreUpdateEventArgs $args
     */
    private function addPropertyChanges(PreUpdateEventArgs $args)
    {
        $entity        = $args->getEntity();
        $realClass     = ClassUtils::getRealClass(get_class($entity));
        $classMetadata = $args->getEntityManager()->getClassMetadata($realClass);

        foreach ($args->getEntityChangeSet() as $property => $change) {
            /** @var Change $annotation */
            $annotation = $this->annotationGetter->getPropertyAnnotation($classMetadata, $property, Change::class);

            if ($annotation) {
                $this->dispatcher->addPropertyChange(
                    $annotation,
                    $args->getEntity(),
                    $property,
                    $change[0],
                    $change[1]
                );
            }
        }
    }

    /**
     * @param PreUpdateEventArgs $args
     *
     * @throws \ReflectionException
     */
    private function addCollectionChanges(PreUpdateEventArgs $args)
    {
        $entity        = $args->getEntity();
        $realClass     = ClassUtils::getRealClass(get_class($entity));
        $classMetadata = $args->getEntityManager()->getClassMetadata($realClass);

        /** @var PersistentCollection $update */
        foreach ($args->getEntityManager()->getUnitOfWork()->getScheduledCollectionUpdates() as $update) {
            if ($update->getOwner() !== $entity) {
                continue;
            }

            $property   = $update->getMapping()['fieldName'];
            /** @var Change $annotation */
            $annotation = $this->annotationGetter->getPropertyAnnotation($classMetadata, $property, Change::class);

            // Make sure $u belongs to the entity we are working on
            if (!isset($annotation)) {
                continue;
            }

            $this->dispatcher->addCollectionChange(
                $annotation,
                $args->getEntity(),
                $property,
                $update->getDeleteDiff(),
                $update->getInsertDiff()
            );
        }
    }
}
