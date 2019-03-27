<?php
/*
 * (c) Alkis Stamos <stamosalkis@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AlkisStamos\Metadata\Tests\Cache;
use AlkisStamos\Metadata\Cache\ChainedCache;
use AlkisStamos\Metadata\Cache\InMemoryCache;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;

class ChainedCacheTest extends TestCase
{
    public function testGetWithDefaultCache()
    {
        $cache = new ChainedCache();
        $value = new \stdClass();
        $value->prop = 'test';
        $cache->set('key',$value);
        $this->assertSame($value,$cache->get('key'));
    }

    public function testGetWithCustomInMemoryCache()
    {
        $inMemoryCache = $this->createMock(InMemoryCache::class);
        $value = new \stdClass();
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
        $cache->set('key',$value);
        $this->assertSame($value,$cache->get('key'));
    }

    public function testGetWithCustomCache()
    {
        $value = new \stdClass();
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
            ->with('key',$value);
        $cache = new ChainedCache($memoryCache);
        $cache->register($customCache);
        $this->assertSame($value,$cache->get('key'));
    }
}