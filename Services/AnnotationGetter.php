<?php

namespace W3C\LifecycleEventsBundle\Services;

use Doctrine\ORM\Mapping\ClassMetadata;
use ReflectionException;

/**
 * Convenient class to get lifecycle annotations more easily
 *
 * @author Jean-Guilhem Rouel <jean-gui@w3.org>
 */
class AnnotationGetter
{
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
        $reflection = new \ReflectionClass($class);
        $attributes = $reflection->getAttributes($annotationClass);

        if (\count($attributes) === 0) {
            return null;
        }

        return $attributes[0]->newInstance();
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
            $attributes = $reflProperty->getAttributes($annotationClass);

            if (\count($attributes) === 0) {
                return null;
            }

            return $attributes[0]->newInstance();
        }

        throw new ReflectionException(
            $classMetadata->getName() . '.' . $field . ' not found. Could this be a private field of a parent class?'
        );
    }
}
