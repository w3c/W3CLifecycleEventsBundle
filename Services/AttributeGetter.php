<?php

namespace W3C\LifecycleEventsBundle\Services;

use Doctrine\ORM\Mapping\ClassMetadata;
use ReflectionException;

/**
 * Convenient class to get lifecycle attributes more easily
 *
 * @author Jean-Guilhem Rouel <jean-gui@w3.org>
 */
class AttributeGetter
{
    /**
     * Get a class-level annotation
     *
     * @param string $class           Class to get attribute of
     * @param string $attributeClass Class of the attribute to get
     *
     * @return object|null object of same class as $attributeClass or null if no attribute is found
     * @throws ReflectionException
     */
    public function getAnnotation(string $class, string $attributeClass): ?object
    {
        $reflection = new \ReflectionClass($class);
        $attributes = $reflection->getAttributes($attributeClass);

        if (\count($attributes) === 0) {
            return null;
        }

        return $attributes[0]->newInstance();
    }

    /**
     * Get a field-level attribute
     *
     * @param ClassMetadata $classMetadata   Metadata of the class to get attribute of
     * @param string        $field           Name of the field to get attribute of
     * @param string        $attributeClass Class of the attribute to get
     *
     * @return object|null object of same class as $attributeClass or null if no attribute is found
     * @throws ReflectionException if the field does not exist
     */
    public function getPropertyAnnotation(ClassMetadata $classMetadata, string $field, string $attributeClass): ?object
    {

        $reflProperty = $classMetadata->getReflectionProperty($field);

        if ($reflProperty) {
            $attributes = $reflProperty->getAttributes($attributeClass);

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
