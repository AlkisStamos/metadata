<?php
/*
 * (c) Alkis Stamos <stamosalkis@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alks\Metadata\Driver;

use Alks\Metadata\Metadata\ClassMetadata;
use Alks\Metadata\Metadata\MethodMetadata;
use Alks\Metadata\Metadata\PropertyMetadata;

/**
 * @package Metadata
 * @author Alkis Stamos <stamosalkis@gmail.com>
 * @license MIT
 * @copyright Alkis Stamos
 *
 * Parses and stores metadata for classes
 */
interface MetadataDriverInterface
{
    /**
     * Generates the class metadata from a reflection class
     *
     * @param \ReflectionClass $class
     * @return ClassMetadata|null
     */
    public function getClassMetadata(\ReflectionClass $class): ?ClassMetadata;

    /**
     * Generates the property metadata from a reflection property
     *
     * @param \ReflectionProperty $property
     * @return PropertyMetadata|null
     */
    public function getPropertyMetadata(\ReflectionProperty $property): ?PropertyMetadata;

    /**
     * Generates the method metadata from a reflection method
     *
     * @param \ReflectionMethod $method
     * @return MethodMetadata|null
     */
    public function getMethodMetadata(\ReflectionMethod $method): ?MethodMetadata;
}