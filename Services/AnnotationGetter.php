<?php

namespace W3C\LifecycleEventsBundle\Services;

use Doctrine\Common\Annotations\Reader;
use Doctrine\ORM\Mapping\ClassMetadata;
use ReflectionClass;
use ReflectionException;

/**
 * Convenient class to get lifecycle annotations more easily
 *
 * @author Jean-Guilhem Rouel <jean-gui@w3.org>
 */
class AnnotationGetter
{
    private Reader $reader;

    public function __construct(Reader $reader)
    {
        $this->reader = $reader;
    }

    /**
     * Get a class-level annotation
     *
     * @param string $class           Class to get annotation of
     * @param string $annotationClass Class of the annotation to get
     *
     * @return object|null object of same class as $annotationClass or null if no annotation is found
     * @throws ReflectionException
     */
    public function getAnnotation(string $class, string $annotationClass): ?object
    {
        return $this->reader->getClassAnnotation(
            new ReflectionClass($class),
            $annotationClass
        );
    }

    /**
     * Get a field-level annotation
     *
     * @param ClassMetadata $classMetadata   Metadata of the class to get annotation of
     * @param string        $field           Name of the field to get annotation of
     * @param string        $annotationClass Class of the annotation to get
     *
     * @return object|null object of same class as $annotationClass or null if no annotation is found
     * @throws ReflectionException if the field does not exist
     */
    public function getPropertyAnnotation(ClassMetadata $classMetadata, string $field, string $annotationClass): ?object
    {
        $reflProperty = $classMetadata->getReflectionProperty($field);

        if ($reflProperty) {
            return $this->reader->getPropertyAnnotation($reflProperty, $annotationClass);
        }

        throw new ReflectionException(
            $classMetadata->getName() . '.' . $field . ' not found. Could this be a private field of a parent class?'
        );
    }
}
