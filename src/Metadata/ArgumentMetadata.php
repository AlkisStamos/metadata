<?php
/*
 * (c) Alkis Stamos <stamosalkis@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AlkisStamos\Metadata\Metadata;
/**
 * @package Metadata
 * @author Alkis Stamos <stamosalkis@gmail.com>
 * @license MIT
 * @copyright Alkis Stamos
 *
 * Representation of an method's argument metadata
 */
class ArgumentMetadata
{
    /**
     * The name of the argument
     *
     * @var string
     */
    public $name;
    /**
     * The type of the argument (if defined)
     *
     * @var TypeMetadata|null
     */
    public $type = null;

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
}