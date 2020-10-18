<?php
/*
 * (c) Alkis Stamos <stamosalkis@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alks\Metadata;

use Alks\Metadata\Cache\ChainedCache;
use Alks\Metadata\Driver\AnnotationMetadataMetadataDriver;
use Alks\Metadata\Driver\MetadataDriverInterface;
use Alks\Metadata\Driver\ReflectionMetadataDriver;
use Alks\Metadata\Metadata\ClassMetadata;
use Alks\Metadata\Metadata\MethodMetadata;
use Alks\Metadata\Metadata\PropertyMetadata;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\FileCacheReader;
use Psr\SimpleCache\CacheInterface;

/**
 * @package Metadata
 * @author Alkis Stamos <stamosalkis@gmail.com>
 * @license MIT
 * @copyright Alkis Stamos
 *
 * Service to wrap multiple metadata drivers and providing caching options
 */
class MetadataDriver implements MetadataDriverInterface
{
    /**
     * Collection of registered drivers to be used in chain
     *
     * @var MetadataDriverInterface[]
     */
    protected $drivers = [];
    /**
     * Collection of already registered driver names to disable duplicates
     *
     * @var string[]
     */
    protected $registeredDrivers = [];
    /**
     * The metadata cache
     *
     * @var CacheInterface
     */
    protected $cache;
    /**
     * Local flag to help with lazy initialization
     *
     * @var bool
     */
    private $isInitialized = false;

    /**
     * MetadataDriver constructor.
     * By default if no cache is passed in the constructor the driver will enable the built-in chained cache
     * @param MetadataDriverInterface[] $drivers
     * @param CacheInterface $cache
     */
    public function __construct(array $drivers = [], CacheInterface $cache = null)
    {
        foreach ($drivers as $driver) {
            $this->register($driver);
        }
        $this->cache = $cache === null ? new ChainedCache() : $cache;
    }

    /**
     * Registers a metadata driver. Drivers will run in sequence in the order they have been registered
     *
     * @param MetadataDriverInterface $metadataDriver
     * @return $this
     */
    public function register(MetadataDriverInterface $metadataDriver)
    {
        $key = get_class($metadataDriver);
        if (!isset($this->registeredDrivers[$key])) {
            $this->drivers[] = $metadataDriver;
            $this->registeredDrivers[$key] = true;
        }
        return $this;
    }

    /**
     * Generates the class metadata from a reflection class
     *
     * @param \ReflectionClass $class
     * @return ClassMetadata|null
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \RuntimeException
     */
    public function getClassMetadata(\ReflectionClass $class): ?ClassMetadata
    {
        $cachedKey = 'class.' . $this->cachedClassName($class->getName());
        $cached = $this->pullFromCache($cachedKey);
        if ($cached !== null) {
            return $cached;
        }
        foreach ($this->getDrivers() as $metadataDriver) {
            $classMetadata = $metadataDriver->getClassMetadata($class);
            if ($classMetadata !== null) {
                $this->cache($cachedKey, $classMetadata);
                return $classMetadata;
            }
        }
        throw new \RuntimeException('Cannot generate class metadata for ' . $class->getName() . ' with the registered drivers');
    }

    /**
     * Generates a "safe to cache" key from a class name
     *
     * @param string $className
     * @return string
     */
    private function cachedClassName(string $className): string
    {
        return str_replace('\\', '_', $className);
    }

    /**
     * Searches in all registered caches to pull an item
     *
     * @param $key
     * @return mixed|null
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    protected function pullFromCache($key)
    {
        return $this->cache->has($key) ? $this->cache->get($key) : null;
    }

    /**
     * Returns the registered drivers or initializes the component with the default ReflectionMetadataDriver
     *
     * @return MetadataDriverInterface[]
     */
    public function getDrivers()
    {
        if (!$this->isInitialized) {
            $this->isInitialized = true;
            if (count($this->drivers) === 0) {
                $this->register(
                    new ReflectionMetadataDriver()
                );
            }
        }
        return $this->drivers;
    }

    /**
     * Caches an item in all regitered cache pools
     *
     * @param $key
     * @param $item
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    protected function cache($key, $item)
    {
        $this->cache->set($key, $item);
    }

    /**
     * Generates the property metadata from a reflection property
     *
     * @param \ReflectionProperty $property
     * @return PropertyMetadata|null
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \RuntimeException
     */
    public function getPropertyMetadata(\ReflectionProperty $property): ?PropertyMetadata
    {
        $cachedKey = 'property.' . $this->cachedClassName($property->getDeclaringClass()->getName()) . '.' . $property->getName();
        $cached = $this->pullFromCache($cachedKey);
        if ($cached !== null) {
            return $cached;
        }
        foreach ($this->getDrivers() as $metadataDriver) {
            $propertyMetadata = $metadataDriver->getPropertyMetadata($property);
            if ($propertyMetadata !== null) {
                $this->cache($cachedKey, $propertyMetadata);
                return $propertyMetadata;
            }
        }
        throw new \RuntimeException('Cannot generate metadata for ' . $property->getDeclaringClass()->getName() . '::' . $property->getName() . ' with the registered drivers');
    }

    /**
     * Generates the method metadata from a reflection method
     *
     * @param \ReflectionMethod $method
     * @return MethodMetadata|null
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \RuntimeException
     */
    public function getMethodMetadata(\ReflectionMethod $method): ?MethodMetadata
    {
        $cachedKey = 'method.' . $this->cachedClassName($method->getDeclaringClass()->getName()) . '.' . $method->getName();
        $cached = $this->pullFromCache($cachedKey);
        if ($cached !== null) {
            return $cached;
        }
        foreach ($this->getDrivers() as $metadataDriver) {
            $methodMetadata = $metadataDriver->getMethodMetadata($method);
            if ($methodMetadata !== null) {
                $this->cache($cachedKey, $methodMetadata);
                return $methodMetadata;
            }
        }
        throw new \RuntimeException('Cannot generate metadata for ' . $method->getDeclaringClass()->getName() . '::' . $method->getName() . ' with the registered drivers');
    }

    /**
     * Registers or overrides the cache pool of the driver
     *
     * @param CacheInterface $cache
     */
    public function registerCache(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Enables the annotation metadata driver with default configuration. If the cacheDir is defined the directory would
     * be used for annotation cache
     *
     * @param null $cacheDir
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @todo FileCacheReader is deprecated remove before upgrading the doctrine annotations
     */
    public function enableAnnotations($cacheDir = null)
    {
        if ($cacheDir === null) {
            $this->register(new AnnotationMetadataMetadataDriver(
                new AnnotationReader()
            ));
            return;
        }
        $this->register(new AnnotationMetadataMetadataDriver(
            new FileCacheReader(
                new AnnotationReader(), $cacheDir
            )
        ));
    }
}