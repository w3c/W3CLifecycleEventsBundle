<?php

namespace W3C\LifecycleEventsBundle\Services;

use Doctrine\Common\Annotations\Reader;
use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * Convenient class to get lifecycle annotations more easily
 *
 * @author Jean-Guilhem Rouel <jean-gui@w3.org>
 */
class AnnotationGetter
{
    /**
     * @var Reader
     */
    private $reader;

    public function __construct(Reader $reader)
    {
        $this->reader = $reader;
    }

    /**
     * Get a class-level annotation
     *
     * @param string $class Class to get annotation of
     * @param string $annotationClass Class of the annotation to get
     *
     * @return object|null object of same class as $annotationClass or null if no annotation is found
     */
    public function getAnnotation($class, $annotationClass)
    {
        $annotation = $this->reader->getClassAnnotation(
            new \ReflectionClass($class),
            $annotationClass
        );
        return $annotation;
    }

    /**
     * Get a field-level annotation
     *
     * @param ClassMetadata $classMetadata Metadata of the class to get annotation of
     * @param string $field Name of the field to get annotation of
     * @param string $annotationClass Class of the annotation to get
     *
     * @return object|null object of same class as $annotationClass or null if no annotation is found
     * @throws \ReflectionException if the field does not exist
     */
    public function getPropertyAnnotation(ClassMetadata $classMetadata, $field, $annotationClass)
    {
        $reflProperty = $classMetadata->getReflectionProperty($field);

        if ($reflProperty) {
            return $this->reader->getPropertyAnnotation($reflProperty, $annotationClass);
        }

        throw new \ReflectionException(
            $classMetadata->getName() . '.' . $field . ' not found. Could this be a private field of a parent class?'
        );
    }
}
