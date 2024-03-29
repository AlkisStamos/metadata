<?php
/*
 * (c) Alkis Stamos <stamosalkis@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alks\Metadata\Driver;

use Alks\Metadata\Metadata\ClassMetadata;
use Alks\Metadata\Metadata\PropertyMetadata;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

/**
 * @package Metadata
 * @author Alkis Stamos <stamosalkis@gmail.com>
 * @license MIT
 * @copyright Alkis Stamos
 *
 * Provides basic behaviour for metadata drivers
 */
abstract class AbstractMetadataDriver implements MetadataDriverInterface
{
    /**
     * Generates the class metadata from a reflection class
     *
     * @param ReflectionClass $class
     * @return ClassMetadata|null
     */
    public function getClassMetadata(ReflectionClass $class): ?ClassMetadata
    {
        $classMetadata = new ClassMetadata($class->getName());
        $classMetadata->addFileProperty('path', $class->getFileName());
        $methods = $class->getMethods();
        foreach ($methods as $method) {
            $classMetadata->addMethodMetadata($this->getMethodMetadata($method));
        }
        $properties = $class->getProperties();
        foreach ($properties as $property) {
            $propertyMetadata = $this->getPropertyMetadata($property);
            if ($propertyMetadata) {
                if ($propertyMetadata->getter === null) {
                    $propertyMetadata->getter = $this->getPropertyMethodName($classMetadata, $propertyMetadata);
                }
                if ($propertyMetadata->setter === null) {
                    $propertyMetadata->setter = $this->getPropertyMethodName($classMetadata, $propertyMetadata, 'set');
                }
            }
            $classMetadata->addPropertyMetadata($propertyMetadata);
        }
        return $classMetadata;
    }

    /**
     * Returns the setter or getter for the property
     *
     * @param ClassMetadata $classMetadata
     * @param PropertyMetadata $propertyMetadata
     * @param string $prefix
     * @return null|string
     */
    protected function getPropertyMethodName(ClassMetadata $classMetadata, PropertyMetadata $propertyMetadata, $prefix = 'get'): ?string
    {
        $methodName = $prefix . ucfirst($propertyMetadata->name);
        return isset($classMetadata->methods[$methodName]) ? $methodName : null;
    }

    /**
     * Resolves the access of a reflection instance
     *
     * @param $reflection
     * @return string
     */
    protected function resolveAccess($reflection): string
    {
        if ($reflection instanceof ReflectionProperty || $reflection instanceof ReflectionMethod) {
            if ($reflection->isPrivate()) {
                return 'private';
            }
            if ($reflection->isProtected()) {
                return 'protected';
            }
        }
        return 'public';
    }

    /**
     * Checks if the given type is a "simple type", refering to PHPs built in types
     *
     * @param string $type type name from gettype()
     * @return bool
     */
    protected function isSimpleType(string $type): bool
    {
        return $this->isFlatType($type)
            || $type === 'array' || $type === 'object';
    }

    /**
     * Checks if the given type is a type that is not nested (simple type except array and object)
     *
     * @param string $type type name from gettype()
     * @return bool
     */
    protected function isFlatType(string $type): bool
    {
        return $type === 'NULL'
            || $type === 'string'
            || $type === 'boolean' || $type === 'bool'
            || $type === 'integer' || $type === 'int'
            || $type === 'double' || $type === 'float';
    }

    /**
     * Returns true if type is an array of elements. If so the reference $realType will have the value of the real type
     * name (eg string[] => true, 'string')
     *
     * @param $type
     * @param $realType
     * @return bool
     */
    protected function isCollectionType($type, &$realType): bool
    {
        if (substr($type, -2) === '[]') {
            $realType = substr($type, 0, -2);
            return true;
        }
        $realType = $type;
        return false;
    }
}