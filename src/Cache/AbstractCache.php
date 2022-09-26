<?php
/*
 * (c) Alkis Stamos <stamosalkis@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alks\Metadata\Cache;

use Psr\SimpleCache\CacheInterface;

/**
 * @package Metadata
 * @author Alkis Stamos <stamosalkis@gmail.com>
 * @license MIT
 * @copyright Alkis Stamos
 *
 * Provides default behaviour to cache related classes
 */
abstract class AbstractCache implements CacheInterface
{
    /**
     * Validates a cache key
     *
     * @param $key
     * @throws InvalidArgumentException
     */
    protected function validateKey($key): void
    {
        if (!is_string($key)) {
            throw new InvalidArgumentException(sprintf('Cache key must be string, "%s" given', gettype($key)));
        }
    }
}