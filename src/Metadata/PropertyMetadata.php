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
 * Representation of a class's property metadata
 */
class PropertyMetadata extends AbstractMetadataHost
{
    /**
     * The name of the property
     *
     * @var string
     */
    public string $name;
    /**
     * The type metadata of the property
     *
     * @var TypeMetadata|null
     */
    public ?TypeMetadata $type = null;
    /**
     * Access type of the property ('private'|'public'|'protected')
     *
     * @var string
     */
    public string $access;
    /**
     * Getter name of the property. By default, is null and the getter will be selected by naming strategies, if is set
     * the naming strategies would be overridden
     *
     * @var string|null
     */
    public ?string $setter = null;
    /**
     * Setter name of the property. By default, is null and the getter will be selected by naming strategies, if is set
     * the naming strategies would be overridden
     *
     * @var string|null
     */
    public ?string $getter = null;

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
}