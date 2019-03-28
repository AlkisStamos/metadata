<?php
/*
 * (c) Alkis Stamos <stamosalkis@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AlkisStamos\Metadata\Tests\Cache;
use AlkisStamos\Metadata\Cache\InMemoryCache;
use AlkisStamos\Metadata\Cache\InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * @package Metadata
 * @author Alkis Stamos <stamosalkis@gmail.com>
 * @license MIT
 * @copyright Alkis Stamos
 *
 * Tests the AlkisStamos\Metadata\Cache\InMemoryCache
 * Valid and invalid terms refer to non existing/existing keys
 * Invalid-type term refers to invalid key
 */
class InMemoryCacheTest extends TestCase
{
    public function testGetWithInvalidKey()
    {
        $cache = new InMemoryCache();
        $default = false;
        $this->assertSame($default,$cache->get('key',$default));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testWithInvalidKeyType()
    {
        $cache = new InMemoryCache();
        $cache->get(null);
    }

    public function testGetWithValidKey()
    {
        $cache = new InMemoryCache();
        $value = new \stdClass();
        $cache->set('key',$value);
        $default = false;
        $this->assertSame($value,$cache->get('key',$default));
    }

    public function testDeleteWithInvalidKey()
    {
        $cache = new InMemoryCache();
        $this->assertTrue($cache->delete('invalid_key'));
    }

    public function testDeleteWithValidKey()
    {
        $cache = new InMemoryCache();
        $value = new \stdClass();
        $cache->set('key',$value);
        $default = false;
        $this->assertSame($value,$cache->get('key',$default));
        $this->assertTrue($cache->delete('key'));
        $this->assertSame($default,$cache->get('key',$default));
    }

    public function testClear()
    {
        $cache = new InMemoryCache();
        $value = new \stdClass();
        $cache->set('key',$value);
        $default = false;
        $this->assertSame($value,$cache->get('key',$default));
        $this->assertTrue($cache->clear());
        $this->assertSame($default,$cache->get('key',$default));
    }

    public function testGetMultipleInvalidKeys()
    {
        $cache = new InMemoryCache();
        $default = false;
        foreach($cache->getMultiple(['one','two'],$default) as $value)
        {
            $this->assertSame($default,$value);
        }
    }

    public function testGetMultipleWithValidKeys()
    {
        $cache = new InMemoryCache();
        $valueSet = new \stdClass();
        $cache->set('key',$valueSet);
        $default = false;
        foreach($cache->getMultiple(['invalid','key'],$default) as $key=>$value)
        {
            if($key === 1)
            {
                $this->assertSame($valueSet,$value);
            }
            else
            {
                $this->assertSame($default,$value);
            }
        }
    }

    public function testSetMultiple()
    {
        $values = ['a'=>1,'b'=>'2','c'=>3,'d'=>false,'e'=>'four','f'=>null];
        $cache = new InMemoryCache();
        $this->assertTrue($cache->setMultiple($values));
        $range = range('a','f');
        foreach($cache->getMultiple(range('a','f')) as $key=>$value)
        {
            $this->assertSame($values[$range[$key]],$value);
        }
    }

    public function testDeleteMultiple()
    {
        $values = ['a'=>1,'b'=>'2','c'=>3,'d'=>false,'e'=>'four','f'=>null];
        $cache = new InMemoryCache();
        $this->assertTrue($cache->setMultiple($values));
        $range = range('a','f');
        foreach($cache->getMultiple(range('a','f')) as $key=>$value)
        {
            $this->assertSame($values[$range[$key]],$value);
        }
        $this->assertTrue($cache->deleteMultiple(range('a','e')));
        $default = new \stdClass();
        foreach($cache->getMultiple(range('a','f'),$default) as $key=>$value)
        {
            if($key === 5)
            {
                $this->assertSame($values[$range[$key]],$value);
            }
            else
            {
                $this->assertSame($default,$value);
            }
        }
    }
}