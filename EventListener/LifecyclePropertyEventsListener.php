<?php

namespace W3C\LifecycleEventsBundle\EventListener;

use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\PersistentCollection;
use W3C\LifecycleEventsBundle\Annotation\Change;
use W3C\LifecycleEventsBundle\Services\LifecycleEventsDispatcher;

/**
 * Listen to Doctrine preUpdate to feed a LifecycleEventsDispatcher
 *
 * @author Jean-Guilhem Rouel <jean-gui@w3.org>
 */
class LifecyclePropertyEventsListener
{
    /**
     * Events dispatcher.
     *
     * @var LifecycleEventsDispatcher
     */
    private $dispatcher;

    /**
     * @var Reader
     */
    private $reader;

    /**
     * Constructs a new instance.
     *
     * @param LifecycleEventsDispatcher $dispatcher the dispatcher to fed
     * @param Reader                    $reader
     */
    public function __construct(LifecycleEventsDispatcher $dispatcher, Reader $reader)
    {
        $this->dispatcher = $dispatcher;
        $this->reader = $reader;
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
        $entity = $args->getEntity();
        $realClass = ClassUtils::getRealClass(get_class($entity));
        $classMetadata = $args->getEntityManager()->getClassMetadata($realClass);

        foreach ($args->getEntityChangeSet() as $property => $change) {
            $annotation = $this->getChangeAnnotation($classMetadata, $property);

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
        $entity = $args->getEntity();
        $realClass = ClassUtils::getRealClass(get_class($entity));
        $classMetadata = $args->getEntityManager()->getClassMetadata($realClass);

        /** @var PersistentCollection $update */
        foreach ($args->getEntityManager()->getUnitOfWork()->getScheduledCollectionUpdates() as $update) {
            if ($update->getOwner() !== $entity) {
                continue;
            }

            $property = $update->getMapping()['fieldName'];
            $annotation = $this->getChangeAnnotation($classMetadata, $property);

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

    /**
     * @param ClassMetadata $classMetadata
     * @param string        $property
     *
     * @throws \ReflectionException
     *
     * @return Change
     */
    private function getChangeAnnotation(ClassMetadata $classMetadata, $property)
    {
        try {
            /** @var Change $annotation */
            $annotation = $this->reader->getPropertyAnnotation(
                $classMetadata->getReflectionProperty($property),
                Change::class
            );

            return $annotation;
        } catch (\ReflectionException $e) {
            throw new \ReflectionException(
                $e->getMessage().'. Could this be a private field of a parent class?',
                $e->getCode(),
                $e
            );
        }
    }
}
