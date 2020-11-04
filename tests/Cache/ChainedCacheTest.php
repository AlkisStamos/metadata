<?php
/*
 * (c) Alkis Stamos <stamosalkis@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alks\Metadata\Tests\Cache;

use Alks\Metadata\Cache\ChainedCache;
use Alks\Metadata\Cache\InMemoryCache;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;
use stdClass;

class ChainedCacheTest extends TestCase
{
    public function testGetWithDefaultCache()
    {
        $cache = new ChainedCache();
        $value = new stdClass();
        $value->prop = 'test';
        $cache->set('key', $value);
        $this->assertSame($value, $cache->get('key'));
    }

    public function testGetWithCustomInMemoryCache()
    {
        $inMemoryCache = $this->createMock(InMemoryCache::class);
        $value = new stdClass();
        $value->prop = 'test';
        $inMemoryCache->expects($this->once())
            ->method('has')
            ->with('key')
            ->willReturn(true);
        $inMemoryCache->expects($this->once())
            ->method('get')
            ->with('key')
            ->willReturn($value);
        $cache = new ChainedCache($inMemoryCache);
        $cache->set('key', $value);
        $this->assertSame($value, $cache->get('key'));
    }

    public function testGetWithCustomCache()
    {
        $value = new stdClass();
        $value->prop = 'test';
        $customCache = $this->createMock(CacheInterface::class);
        $customCache->expects($this->once())
            ->method('has')
            ->with('key')
            ->willReturn(true);
        $customCache->expects($this->once())
            ->method('get')
            ->with('key')
            ->willReturn($value);
        $memoryCache = $this->createMock(InMemoryCache::class);
        $memoryCache->expects($this->once())
            ->method('has')
            ->willReturn(false);
        $memoryCache->expects($this->once())
            ->method('set')
            ->with('key', $value);
        $cache = new ChainedCache($memoryCache);
        $cache->register($customCache);
        $this->assertSame($value, $cache->get('key'));
    }

    public function testGetWithNonExistingKey()
    {
        $customCache = $this->createMock(CacheInterface::class);
        $customCache->expects($this->once())
            ->method('has')
            ->with('key')
            ->willReturn(false);
        $memoryCache = $this->createMock(InMemoryCache::class);
        $memoryCache->expects($this->once())
            ->method('has')
            ->willReturn(false);
        $cache = new ChainedCache($memoryCache);
        $cache->register($customCache);
        $default = new stdClass();
        $default->test = 'test';
        $this->assertSame($default, $cache->get('key', $default));
    }

    public function testSetWithCustomCache()
    {
        $value = new stdClass();
        $value->prop = 'test';
        $customCache = $this->createMock(CacheInterface::class);
        $customCache->expects($this->once())
            ->method('set')
            ->with('key', $value)
            ->willReturn(true);
        $cache = new ChainedCache();
        $cache->register($customCache);
        $this->assertTrue($cache->set('key', $value));
        $this->assertSame($value, $cache->get('key'));
    }

    public function testInvalidSet()
    {
        $value = 'value';
        $customCache = $this->createMock(CacheInterface::class);
        $customCache->expects($this->once())
            ->method('set')
            ->with('key', $value)
            ->willReturn(false);
        $cache = new ChainedCache();
        $cache->register($customCache);
        $this->assertFalse($cache->set('key', $value));
    }

    public function testDeleteWithoutCustomCache()
    {
        $value = new stdClass();
        $value->prop = 'test';
        $cache = new ChainedCache();
        $cache->set('key', $value);
        $this->assertTrue($cache->has('key'));
        $this->assertSame($value, $cache->get('key'));
        $this->assertTrue($cache->delete('key'));
        $this->assertFalse($cache->has('key'));
    }

    public function testDeleteWithCustomCache()
    {
        $value = new stdClass();
        $value->prop = 'test';
        $customCache = $this->createMock(CacheInterface::class);
        $customCache->expects($this->once())
            ->method('delete')
            ->willReturn(true);
        $cache = new ChainedCache();
        $cache->register($customCache);
        $this->assertTrue($cache->delete('key'));
    }

    public function testInvalidDelete()
    {
        $customCache = $this->createMock(CacheInterface::class);
        $customCache->expects($this->once())
            ->method('delete')
            ->willReturn(false);
        $cache = new ChainedCache();
        $cache->register($customCache);
        $this->assertFalse($cache->delete('key'));
    }

    public function testClearWithoutCustomCache()
    {
        $values = [1, 2, 3];
        $cache = new ChainedCache();
        foreach ($values as $key => $value) {
            $cache->set($key . 'key', $value);
            $this->assertTrue($cache->has($key . 'key'));
        }
        $this->assertTrue($cache->clear());
        foreach ($values as $key => $value) {
            $this->assertFalse($cache->has($key . 'key'));
        }
    }

    public function testClearWithCustomCache()
    {
        $values = [1, 2, 3];
        $cache = new ChainedCache();
        foreach ($values as $key => $value) {
            $customCache = $this->createMock(CacheInterface::class);
            $customCache->expects($this->exactly(count($values)))
                ->method('has')
                ->willReturn(false);
            $customCache->expects($this->exactly(count($values)))
                ->method('set')
                ->willReturn(true);
            $customCache->expects($this->once())
                ->method('clear')
                ->willReturn(true);
            $cache->register($customCache);
        }
        foreach ($values as $key => $value) {
            $this->assertTrue($cache->set($key . 'key', $value));
            $this->assertTrue($cache->has($key . 'key'));
        }
        $this->assertTrue($cache->clear());
        foreach ($values as $key => $value) {
            $this->assertFalse($cache->has($key . 'key'));
        }
    }

    public function testInvalidClear()
    {
        $customCache = $this->createMock(CacheInterface::class);
        $customCache->expects($this->once())
            ->method('clear')
            ->willReturn(false);
        $memoryCache = $this->createMock(InMemoryCache::class);
        $memoryCache->expects($this->once())
            ->method('clear')
            ->willReturn(true);
        $cache = new ChainedCache($memoryCache);
        $cache->register($customCache);
        $this->assertFalse($cache->clear());
    }

    public function testGetMultiple()
    {
        $values = [1, 2, 3];
        $cache = new ChainedCache();
        $keys = [];
        $cacheValues = [];
        foreach ($values as $value) {
            $cacheValue = new stdClass();
            $cacheValue->value = $value;
            $cacheValues[] = $cacheValue;
            $keys[] = $value . 'key';
            $cache->set($value . 'key', $cacheValue);
        }
        foreach ($cache->getMultiple($keys) as $currentIter => $value) {
            $this->assertSame($cacheValues[$currentIter], $value);
        }
    }

    public function testSetMultiple()
    {
        $values = ['one' => 1, 'two' => 2, 'three' => 3];
        $inMemoryCache = $this->createMock(InMemoryCache::class);
        $inMemoryCache->expects($this->exactly(count($values)))
            ->method('set')
            ->willReturn(true);
        $cache = new ChainedCache($inMemoryCache);
        $this->assertTrue($cache->setMultiple($values));
    }

    public function testInvalidSetMultiple()
    {
        $values = ['one' => 1, 'two' => 2, 'three' => 3];
        $inMemoryCache = $this->createMock(InMemoryCache::class);
        $inMemoryCache->expects($this->exactly(count($values)))
            ->method('set')
            ->willReturn(false);
        $cache = new ChainedCache($inMemoryCache);
        $this->assertFalse($cache->setMultiple($values));
    }

    public function testDeleteMultiple()
    {
        $values = ['one' => 1, 'two' => 2, 'three' => 3];
        $inMemoryCache = $this->createMock(InMemoryCache::class);
        $inMemoryCache->expects($this->exactly(count($values)))
            ->method('delete')
            ->willReturn(true);
        $cache = new ChainedCache($inMemoryCache);
        $this->assertTrue($cache->deleteMultiple($values));
    }

    public function testInvalidDeleteMultiple()
    {
        $values = ['one' => 1, 'two' => 2, 'three' => 3];
        $inMemoryCache = $this->createMock(InMemoryCache::class);
        $inMemoryCache->expects($this->exactly(count($values)))
            ->method('delete')
            ->willReturn(false);
        $cache = new ChainedCache($inMemoryCache);
        $this->assertFalse($cache->deleteMultiple($values));
    }
}