<?php
/*
 * (c) Alkis Stamos <stamosalkis@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alks\Metadata\Metadata;
/**
 * @package Metadata
 * @author Alkis Stamos <stamosalkis@gmail.com>
 * @license MIT
 * @copyright Alkis Stamos
 *
 * Representation of a class's metadata
 */
class ClassMetadata extends AbstractMetadataHost
{
    /**
     * The class name
     *
     * @var string
     */
    public $name;
    /**
     * File properties of the host file of the class
     *
     * @var array
     */
    public $fileProperties;
    /**
     * Collection of property metadata as propertyName=>value
     *
     * @var PropertyMetadata[]
     */
    public $properties = [];
    /**
     * Collection of method metadata as methodName=>value
     *
     * @var MethodMetadata[]
     */
    public $methods = [];

    /**
     * ArgumentMetadata constructor.
     * Generates an ArgumentMetadata instance by the argument name
     * @param string $name
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * Stringify the type with only its name (for direct comparison "$type" === string)
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->name;
    }

    /**
     * Adds a property to the file properties
     *
     * @param string $property
     * @param mixed $value
     */
    public function addFileProperty($property, $value): void
    {
        $this->fileProperties[$property] = $value;
    }

    /**
     * Adds property metadata to the class
     *
     * @param PropertyMetadata $metadata
     */
    public function addPropertyMetadata(PropertyMetadata $metadata): void
    {
        $this->properties[$metadata->name] = $metadata;
    }

    /**
     * Adds method metadata to the class
     *
     * @param MethodMetadata $metadata
     */
    public function addMethodMetadata(MethodMetadata $metadata): void
    {
        $this->methods[$metadata->name] = $metadata;
    }
}