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
 * Representation of a class's method metadata
 */
class MethodMetadata extends AbstractMetadataHost
{
    /**
     * The name of the method inside the class
     *
     * @var string
     */
    public string $name;
    /**
     * Assoc array (name=>value) of the method's arguments metadata
     *
     * @var ArgumentMetadata[]
     */
    public array $arguments = [];
    /**
     * The return type of the method
     *
     * @var TypeMetadata|null
     */
    public ?TypeMetadata $returnType = null;
    /**
     * Access type of the method ('private'|'public'|'protected')
     *
     * @var string
     */
    public string $access;

    /**
     * TypeMetadata constructor.
     * Generates the metadata instance with only its name
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
     * Adds argument metadata to the method
     *
     * @param ArgumentMetadata $argument
     */
    public function addArgumentMetadata(ArgumentMetadata $argument): void
    {
        $this->arguments[$argument->name] = $argument;
    }

    /**
     * Returns the argument metadata for the argument name or null if not found
     *
     * @param string $name
     * @param null $default
     * @return ArgumentMetadata|null
     */
    public function getArgumentMetadata(string $name, $default = null): ?ArgumentMetadata
    {
        return array_key_exists($name, $this->arguments) ? $this->arguments[$name] : $default;
    }
}