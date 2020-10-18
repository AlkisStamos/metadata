<?php
/*
 * (c) Alkis Stamos <stamosalkis@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alks\Metadata\Annotation;
/**
 * @package Metadata
 * @author Alkis Stamos <stamosalkis@gmail.com>
 * @license MIT
 * @copyright Alkis Stamos
 *
 * Doctrine annotation class to provide hardcoded property info to class members
 *
 * @Annotation
 */
class Property extends Annotation
{
    /**
     * The type of the property ("string","bool" etc)
     *
     * @var string|null
     */
    protected $type = null;
    /**
     * If the property can be null
     *
     * @var bool
     */
    protected $nullable = false;
    /**
     * The setter name of the property
     *
     * @var null|string
     */
    protected $setter = null;
    /**
     * The getter name of the property
     *
     * @var null|string
     */
    protected $getter = null;
    /**
     * Format of the property's type (eg DateTime->"Ymd H:i:s")
     *
     * @var null|string
     */
    protected $format = null;
    /**
     * Custom key/value array to store extra attributes if needed
     *
     * @var array
     */
    protected $attrs = [];

    /**
     * Constructs the property with the type name as default value
     *
     * Property constructor.
     * @param array $data
     */
    public function __construct(array $data)
    {
        if (isset($data['value'])) {
            $this->type = $data['value'];
            unset($data['value']);
        }
        parent::__construct($data);
    }

    /**
     * @return null|string
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @param null|string $type
     */
    public function setType(?string $type): void
    {
        $this->type = $type;
    }

    /**
     * @return bool
     */
    public function isNullable(): bool
    {
        return $this->nullable;
    }

    /**
     * @param bool $nullable
     */
    public function setNullable(bool $nullable): void
    {
        $this->nullable = $nullable;
    }

    /**
     * @return null|string
     */
    public function getSetter(): ?string
    {
        return $this->setter;
    }

    /**
     * @param null|string $setter
     */
    public function setSetter(?string $setter): void
    {
        $this->setter = $setter;
    }

    /**
     * @return null|string
     */
    public function getGetter(): ?string
    {
        return $this->getter;
    }

    /**
     * @param null|string $getter
     */
    public function setGetter(?string $getter): void
    {
        $this->getter = $getter;
    }

    /**
     * @return null|string
     */
    public function getFormat(): ?string
    {
        return $this->format;
    }

    /**
     * @param null|string $format
     */
    public function setFormat(?string $format): void
    {
        $this->format = $format;
    }

    /**
     * @return array
     */
    public function getAttrs(): array
    {
        return $this->attrs;
    }

    /**
     * @param array $attrs
     */
    public function setAttrs(array $attrs): void
    {
        $this->attrs = $attrs;
    }
}