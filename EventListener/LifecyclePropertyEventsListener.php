<?php

namespace W3C\LifecycleEventsBundle\EventListener;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\PersistentCollection;
use ReflectionException;
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
    private LifecycleEventsDispatcher $dispatcher;
    private AnnotationGetter $annotationGetter;

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

    /**
     * @param PreUpdateEventArgs $args
     *
     * @return void
     * @throws ReflectionException
     */
    public function preUpdate(PreUpdateEventArgs $args)
    {
        $this->addPropertyChanges($args);
        $this->addCollectionChanges($args);
    }

    /**
     * @param PreUpdateEventArgs $args
     *
     * @throws ReflectionException
     */
    private function addPropertyChanges(PreUpdateEventArgs $args)
    {
        $entity        = $args->getObject();
        $realClass     = ClassUtils::getRealClass(get_class($entity));
        $classMetadata = $args->getObjectManager()->getClassMetadata($realClass);

        foreach ($args->getEntityChangeSet() as $property => $change) {
            /** @var Change $annotation */
            $annotation = $this->annotationGetter->getPropertyAnnotation($classMetadata, $property, Change::class);

            if ($annotation) {
                $this->dispatcher->addPropertyChange(
                    $annotation,
                    $args->getObject(),
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
     * @throws ReflectionException
     */
    private function addCollectionChanges(PreUpdateEventArgs $args): void
    {
        $entity        = $args->getObject();
        $realClass     = ClassUtils::getRealClass(get_class($entity));
        $classMetadata = $args->getObjectManager()->getClassMetadata($realClass);

        /** @var PersistentCollection $update */
        foreach ($args->getObjectManager()->getUnitOfWork()->getScheduledCollectionUpdates() as $update) {
            if ($update->getOwner() !== $entity) {
                continue;
            }

            $property   = $update->getMapping()['fieldName'];
            /** @var Change $annotation */
            $annotation = $this->annotationGetter->getPropertyAnnotation($classMetadata, $property, Change::class);

            // Make sure $u belongs to the entity we are working on
            if (!$annotation) {
                continue;
            }

            $this->dispatcher->addCollectionChange(
                $annotation,
                $args->getObject(),
                $property,
                $update->getDeleteDiff(),
                $update->getInsertDiff()
            );
        }
    }
}
