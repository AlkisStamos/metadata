<?php
/*
 * (c) Alkis Stamos <stamosalkis@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alks\Metadata\Driver;

use Alks\Metadata\Annotation\Annotation;
use Alks\Metadata\Annotation\Property;
use Alks\Metadata\Metadata\ClassMetadata;
use Alks\Metadata\Metadata\MethodMetadata;
use Alks\Metadata\Metadata\PropertyMetadata;
use Alks\Metadata\Metadata\TypeMetadata;
use Doctrine\Common\Annotations\Reader;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

/**
 * @package Metadata
 * @author Alkis Stamos <stamosalkis@gmail.com>
 * @license MIT
 * @copyright Alkis Stamos
 *
 * Driver to parse custom annotations in addition to the doc block and reflection properties
 */
class AnnotationMetadataDriver extends DocCommentMetadataDriver
{
    /**
     * Default annotation reader
     *
     * @var Reader
     */
    private Reader $annotationReader;
    /**
     * Local cache of class metadata
     *
     * @var ClassMetadata[]
     */
    private array $cache = [];

    /**
     * AnnotationMetadataDriver constructor
     * Generates an AnnotationMetadataDriver instance with the selected annotation reader
     * @param Reader $annotationReader
     */
    public function __construct(Reader $annotationReader)
    {
        $this->annotationReader = $annotationReader;
    }

    /**
     * Overrides the main class metadata generation to add extra annotations if any
     *
     * @param ReflectionClass $class
     * @return ClassMetadata|null
     */
    public function getClassMetadata(ReflectionClass $class): ?ClassMetadata
    {
        if (isset($this->cache[$class->getName()])) {
            return $this->cache[$class->getName()];
        }
        $classMetadata = parent::getClassMetadata($class);
        $annotations = $this->annotationReader->getClassAnnotations($class);
        foreach ($annotations as $annotation) {
            if ($annotation instanceof Annotation && $classMetadata) {
                $classMetadata->addMetadata($annotation);
            }
        }
        $this->cache[$class->getName()] = $classMetadata;
        return $classMetadata;
    }

    /**
     * Generates the method metadata from a reflection method and overrides properties from any annotations defined.
     *
     * @param ReflectionMethod $method
     * @return MethodMetadata|null
     */
    public function getMethodMetadata(ReflectionMethod $method): ?MethodMetadata
    {
        $methodMetadata = parent::getMethodMetadata($method);
        $annotations = $this->annotationReader->getMethodAnnotations($method);
        foreach ($annotations as $annotation) {
            if ($annotation instanceof Annotation && $methodMetadata) {
                $methodMetadata->addMetadata($annotation);
            }
        }
        return $methodMetadata;
    }

    /**
     * Reads all properties metadata and overrides certain parameters if any custom Property annotations are defined
     *
     * @param ReflectionProperty $property
     * @return PropertyMetadata|null
     */
    public function getPropertyMetadata(ReflectionProperty $property): ?PropertyMetadata
    {
        $propertyMetadata = parent::getPropertyMetadata($property);
        $annotations = $this->annotationReader->getPropertyAnnotations($property);
        foreach ($annotations as $annotation) {
            if ($annotation instanceof Annotation && $propertyMetadata) {
                $propertyMetadata->addMetadata($annotation);
                if ($annotation instanceof Property) {
                    $propertyMetadata->setter = $annotation->getSetter();
                    $propertyMetadata->getter = $annotation->getGetter();
                    $type = $this->typeFromPropertyAnnotation($annotation, $property->getDeclaringClass());
                    if ($type !== null) {
                        $propertyMetadata->type = $type;
                    }
                    foreach ($annotation->getAttrs() as $key => $value) {
                        $propertyMetadata->addAttr($key, $value);
                    }
                }
            }
        }
        if ($propertyMetadata->type === null) {
            $propertyMetadata->type = TypeMetadata::mixedType();
        }
        return $propertyMetadata;
    }

    /**
     * Generates a new instance of type by the property annotation. In the case of empty or not known type
     * (flat or class) the method will return null
     *
     * @param Property $property
     * @param ReflectionClass|null $reflectionClass
     * @return TypeMetadata|null
     */
    protected function typeFromPropertyAnnotation(Property $property, ?ReflectionClass $reflectionClass): ?TypeMetadata
    {
        $typeName = $property->getType();
        if ($typeName === null) {
            return null;
        }
        $typeName = $this->fixStaticClassReference($typeName);
        $isArray = false;
        if ($this->isCollectionType($typeName, $typeName)) {
            $isArray = true;
        }
        $isFlat = $this->isSimpleType($typeName);
        if (!$isFlat && !class_exists($typeName)) {
            $typeName = $this->getTypeNameFromUseStatements($typeName, $reflectionClass);
            if ($typeName === null) {
                return null;
            }
        }
        $typeMetadata = new TypeMetadata($typeName);
        $typeMetadata->format = $property->getFormat();
        $typeMetadata->isArray = $isArray;
        $typeMetadata->isFlat = $this->isSimpleType($typeName);
        $typeMetadata->isNullable = $property->isNullable();
        return $typeMetadata;
    }

    /**
     * Allows the usage of ::class in annotations
     *
     * @param string $typeName
     * @return string
     */
    public function fixStaticClassReference(string $typeName): string
    {
        return strpos($typeName, '::class') !== false ? trim(str_replace('::class', '', $typeName)) : $typeName;
    }
}