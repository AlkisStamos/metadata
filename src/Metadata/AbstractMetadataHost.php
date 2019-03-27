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
 * Parent class for hosts that may have custom metadata (properties,methods,classes)
 */
abstract class AbstractMetadataHost
{
    /**
     * Collection of custom metadata from other sources (yml, annotation etc)
     *
     * @var AbstractMetadata[][]
     */
    public $metadata = [];
    /**
     * Collection of custom attributes (key/value)
     *
     * @var array
     */
    public $attrs = [];

    /**
     * Adds custom metadata to the class
     *
     * @param AbstractMetadata $metadata
     */
    public function addMetadata(AbstractMetadata $metadata): void
    {
        $this->metadata[get_class($metadata)][] = $metadata;
    }

    /**
     * Adds a custom attribute to the host
     *
     * @param $key
     * @param $value
     */
    public function addAttr($key, $value)
    {
        $this->attrs[$key] = $value;
    }
}