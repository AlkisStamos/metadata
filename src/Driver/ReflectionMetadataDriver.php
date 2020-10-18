<?php
/*
 * (c) Alkis Stamos <stamosalkis@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alks\Metadata\Driver;

use Alks\Metadata\Metadata\ArgumentMetadata;
use Alks\Metadata\Metadata\MethodMetadata;
use Alks\Metadata\Metadata\PropertyMetadata;
use Alks\Metadata\Metadata\TypeMetadata;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionProperty;
use ReflectionType;

/**
 * @package Metadata
 * @author Alkis Stamos <stamosalkis@gmail.com>
 * @license MIT
 * @copyright Alkis Stamos
 *
 * Generates metadata using PHP's reflection API
 */
class ReflectionMetadataDriver extends AbstractMetadataDriver
{
    /**
     * Generates the property metadata from a reflection property
     *
     * @param ReflectionProperty $property
     * @return PropertyMetadata|null
     */
    public function getPropertyMetadata(ReflectionProperty $property): ?PropertyMetadata
    {
        $propertyMetadata = new PropertyMetadata($propertyName = $property->getName());
        $propertyMetadata->access = $this->resolveAccess($property);
        return $propertyMetadata;
    }

    /**
     * Generates the method metadata from a reflection method
     *
     * @param ReflectionMethod $method
     * @return MethodMetadata|null
     */
    public function getMethodMetadata(ReflectionMethod $method): ?MethodMetadata
    {
        $methodMetadata = new MethodMetadata($methodName = $method->getName());
        $methodMetadata->access = $this->resolveAccess($method);
        $methodMetadata->returnType = $this->getTypeMetadata($method->getReturnType());
        $arguments = $method->getParameters();
        foreach ($arguments as $argument) {
            $methodMetadata->addArgumentMetadata($this->getArgumentMetadata($argument));
        }
        return $methodMetadata;
    }

    /**
     * Generates a TypeMetadata instance from reflection. If the ReflectionType is null the method will return null.
     * Note that in the current PHP implementation there is no type hint for array of complex or flat items
     * (eg string[]) so using this method will result in false data.
     *
     * @param ReflectionType|null $reflectionType
     * @return TypeMetadata|null
     */
    protected function getTypeMetadata(ReflectionType $reflectionType = null): ?TypeMetadata
    {
        if ($reflectionType !== null) {
            $typeMetadata = new TypeMetadata($reflectionType->getName());
            $typeMetadata->isNullable = $reflectionType->allowsNull();
            $typeMetadata->isFlat = $this->isFlatType($typeMetadata->name);
            if ($typeMetadata->name === 'array') {
                $typeMetadata->isArray = true;
            }
            return $typeMetadata;
        }
        return null;
    }

    /**
     * Returns the argument metadata for a parameter
     *
     * @param ReflectionParameter $argument
     * @return ArgumentMetadata
     */
    protected function getArgumentMetadata(ReflectionParameter $argument): ArgumentMetadata
    {
        $argumentMetadata = new ArgumentMetadata($argument->getName());
        $argumentMetadata->type = $this->getTypeMetadata($argument->getType());
        return $argumentMetadata;
    }
}