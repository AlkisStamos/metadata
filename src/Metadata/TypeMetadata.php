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
 * Represention of a property's or argument's type metadata
 */
class TypeMetadata
{
    /**
     * The types clean name (eg string|Namespace\Class etc)
     *
     * @var string
     */
    public $name;
    /**
     * If the type refers to collection of elements instead of one
     *
     * @var bool
     */
    public $isArray = false;
    /**
     * If nullable is supported except the type name only
     *
     * @var bool
     */
    public $isNullable = false;
    /**
     * If is a php flat type (string, double etc) or array object
     *
     * @var bool
     */
    public $isFlat = false;
    /**
     * Extra metadata property "format" to be set on objects like date time
     *
     * @var null|string
     */
    public $format = null;

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
     * Returns the fallback mixed type
     *
     * @return TypeMetadata
     */
    public static function mixedType()
    {
        $self = new self('mixed');
        $self->isFlat = true;
        return $self;
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