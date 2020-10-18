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
 * Class to chain multiple PSR compliant cache adapters. By default the chained cache will be using its own instance of
 * an in-memory-cache adapter (which would be the only one in sync)
 *
 */
class ChainedCache extends AbstractCache
{
    /**
     * Local array cache
     *
     * @var InMemoryCache
     */
    protected $inMemoryCache;
    /**
     * Registered PSR compliant cache adapters
     *
     * @var CacheInterface[]
     */
    protected $availablePools = [];

    /**
     * ChainedCache constructor.
     * If no memory cache is passed
     * @param InMemoryCache|null $inMemoryCache
     */
    public function __construct(InMemoryCache $inMemoryCache = null)
    {
        $this->inMemoryCache = $inMemoryCache ?? new InMemoryCache();
    }

    /**
     * Wipes clean the entire cache's keys.
     *
     * @return bool True on success and false on failure.
     */
    public function clear()
    {
        $ans = $this->inMemoryCache->clear();
        foreach ($this->availablePools as $pool) {
            if (!$pool->clear()) {
                $ans = false;
            }
        }
        return $ans;
    }

    /**
     * Obtains multiple cache items by their unique keys.
     *
     * @param iterable $keys A list of keys that can obtained in a single operation.
     * @param mixed $default Default value to return for keys that do not exist.
     *
     * @return iterable A list of key => value pairs. Cache keys that do not exist or are stale will have $default as value.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if $keys is neither an array nor a Traversable,
     *   or if any of the $keys are not a legal value.
     */
    public function getMultiple($keys, $default = null)
    {
        foreach ($keys as $key) {
            yield $this->get($key, $default);
        }
    }

    /**
     * Fetches a value from the cache.
     *
     * @param string $key The unique key of this item in the cache.
     * @param mixed $default Default value to return if the key does not exist.
     *
     * @return mixed The value of the item from the cache, or $default in case of cache miss.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if the $key string is not a legal value.
     */
    public function get($key, $default = null)
    {
        if (!$this->inMemoryCache->has($key)) {
            foreach ($this->availablePools as $pool) {
                if ($pool->has($key)) {
                    $item = $pool->get($key);
                    $this->inMemoryCache->set($key, $item);
                    return $item;
                }
            }
            return $default;
        }
        return $this->inMemoryCache->get($key, $default);
    }

    /**
     * Persists a set of key => value pairs in the cache, with an optional TTL.
     *
     * @param iterable $values A list of key => value pairs for a multiple-set operation.
     * @param null|int|\DateInterval $ttl Optional. The TTL value of this item. If no value is sent and
     *                                       the driver supports TTL then the library may set a default value
     *                                       for it or let the driver take care of that.
     *
     * @return bool True on success and false on failure.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if $values is neither an array nor a Traversable,
     *   or if any of the $values are not a legal value.
     */
    public function setMultiple($values, $ttl = null)
    {
        $ans = true;
        foreach ($values as $key => $value) {
            if (!$this->set($key, $value, $ttl)) {
                $ans = false;
            }
        }
        return $ans;
    }

    /**
     * Persists data in the cache, uniquely referenced by a key with an optional expiration TTL time.
     *
     * @param string $key The key of the item to store.
     * @param mixed $value The value of the item to store, must be serializable.
     * @param null|int|\DateInterval $ttl Optional. The TTL value of this item. If no value is sent and
     *                                      the driver supports TTL then the library may set a default value
     *                                      for it or let the driver take care of that.
     *
     * @return bool True on success and false on failure.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if the $key string is not a legal value.
     */
    public function set($key, $value, $ttl = null)
    {
        $ans = $this->inMemoryCache->set($key, $value, $ttl);
        foreach ($this->availablePools as $pool) {
            if (!$pool->set($key, $value, $ttl)) {
                $ans = false;
            }
        }
        return $ans;
    }

    /**
     * Deletes multiple cache items in a single operation.
     *
     * @param iterable $keys A list of string-based keys to be deleted.
     *
     * @return bool True if the items were successfully removed. False if there was an error.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if $keys is neither an array nor a Traversable,
     *   or if any of the $keys are not a legal value.
     */
    public function deleteMultiple($keys)
    {
        $ans = true;
        foreach ($keys as $key) {
            if (!$this->delete($key)) {
                $ans = false;
            }
        }
        return $ans;
    }

    /**
     * Delete an item from the cache by its unique key.
     *
     * @param string $key The unique cache key of the item to delete.
     *
     * @return bool True if the item was successfully removed. False if there was an error.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if the $key string is not a legal value.
     */
    public function delete($key)
    {
        $ans = $this->inMemoryCache->delete($key);
        foreach ($this->availablePools as $pool) {
            if (!$pool->delete($key)) {
                $ans = false;
            }
        }
        return $ans;
    }

    /**
     * Determines whether an item is present in the cache.
     *
     * NOTE: It is recommended that has() is only to be used for cache warming type purposes
     * and not to be used within your live applications operations for get/set, as this method
     * is subject to a race condition where your has() will return true and immediately after,
     * another script can remove it making the state of your app out of date.
     *
     * @param string $key The cache item key.
     *
     * @return bool
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if the $key string is not a legal value.
     */
    public function has($key)
    {
        if ($this->inMemoryCache->has($key)) {
            return true;
        }
        foreach ($this->availablePools as $pool) {
            if ($pool->has($key)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Registers a cache pool in the available pools
     *
     * @param CacheInterface $cache
     * @return $this
     */
    public function register(CacheInterface $cache)
    {
        $this->availablePools[] = $cache;
        return $this;
    }
}