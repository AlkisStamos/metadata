<?php
/*
 * (c) Alkis Stamos <stamosalkis@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AlkisStamos\Metadata\Tests;
use AlkisStamos\Metadata\Driver\AnnotationMetadataMetadataDriver;
use AlkisStamos\Metadata\Driver\MetadataDriverInterface;
use AlkisStamos\Metadata\Driver\ReflectionMetadataDriver;
use AlkisStamos\Metadata\Metadata\ClassMetadata;
use AlkisStamos\Metadata\Metadata\MethodMetadata;
use AlkisStamos\Metadata\Metadata\PropertyMetadata;
use AlkisStamos\Metadata\MetadataDriver;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;

class MetadataDriverTest extends TestCase
{
    /**
     * @var vfsStreamDirectory
     */
    private $root;

    public function setUp()
    {
        $this->root = vfsStream::setup('testDir');
    }

    public function testLazyInitialization()
    {
        $driver = new MetadataDriver();
        $registered = $driver->getDrivers();
        $this->assertArrayHasKey(0, $registered);
        $this->assertInstanceOf(ReflectionMetadataDriver::class,$registered[0]);
    }

    public function testAnnotationInitialization()
    {
        $driver = new MetadataDriver();
        $driver->enableAnnotations();
        $registered = $driver->getDrivers();
        $this->assertArrayHasKey(0, $registered);
        $this->assertInstanceOf(AnnotationMetadataMetadataDriver::class, $registered[0]);
    }

    public function testAnnotationWithCacheInitialization()
    {
        $driver = new MetadataDriver();
        $dirname = 'annotationsCacheDir';
        $this->assertFalse($this->root->hasChild($dirname));
        $driver->enableAnnotations(vfsStream::url('testDir').DIRECTORY_SEPARATOR.$dirname);
        $this->assertTrue($this->root->hasChild($dirname));
    }

    public function testCustomDriverInitialization()
    {
        $customDriver = $this->createMock(MetadataDriverInterface::class);
        $driver = new MetadataDriver([
            $customDriver
        ]);
        $this->assertEquals(1, count($driver->getDrivers()));
        $this->assertSame($customDriver,$driver->getDrivers()[0]);
    }

    public function testGetCachedClassMetadata()
    {
        $metadata = $this->createMock(ClassMetadata::class);
        $cache = $this->createMock(CacheInterface::class);
        $cache->expects($this->once())
            ->method('has')
            ->with('class.class_name')
            ->willReturn(true);
        $cache->expects($this->once())
            ->method('get')
            ->with('class.class_name')
            ->willReturn($metadata);
        $class = $this->createMock(\ReflectionClass::class);
        $class->expects($this->once())
            ->method('getName')
            ->willReturn('class_name');
        $driver = new MetadataDriver();
        $driver->registerCache($cache);
        $this->assertSame($metadata,$driver->getClassMetadata($class));
    }

    public function testNotCachedClassMetadata()
    {
        $metadata = $this->createMock(ClassMetadata::class);
        $cache = $this->createMock(CacheInterface::class);
        $cache->expects($this->once())
            ->method('has')
            ->with('class.class_name')
            ->willReturn(false);
        $cache->expects($this->once())
            ->method('set')
            ->with('class.class_name',$metadata)
            ->willReturn(true);
        $class = $this->createMock(\ReflectionClass::class);
        $class->expects($this->once())
            ->method('getName')
            ->willReturn('class_name');
        $customDriver = $this->createMock(MetadataDriverInterface::class);
        $customDriver->expects($this->once())
            ->method('getClassMetadata')
            ->with($class)
            ->willReturn($metadata);
        $driver = new MetadataDriver();
        $driver->registerCache($cache);
        $driver->register($customDriver);
        $this->assertSame($metadata,$driver->getClassMetadata($class));
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Cannot generate class metadata for class_name with the registered drivers
     */
    public function testGetClassMetadataWithNoValidDriver()
    {
        $cache = $this->createMock(CacheInterface::class);
        $cache->expects($this->once())
            ->method('has')
            ->with('class.class_name')
            ->willReturn(false);
        $class = $this->createMock(\ReflectionClass::class);
        $class->expects($this->exactly(2))
            ->method('getName')
            ->willReturn('class_name');
        $customDriver = $this->createMock(MetadataDriverInterface::class);
        $customDriver->expects($this->once())
            ->method('getClassMetadata')
            ->with($class)
            ->willReturn(null);
        $driver = new MetadataDriver();
        $driver->registerCache($cache);
        $driver->register($customDriver);
        $driver->getClassMetadata($class);
    }

    public function testGetCachedPropertyMetadata()
    {
        $metadata = $this->createMock(PropertyMetadata::class);
        $cache = $this->createMock(CacheInterface::class);
        $cache->expects($this->once())
            ->method('has')
            ->with('property.class_name.property_name')
            ->willReturn(true);
        $cache->expects($this->once())
            ->method('get')
            ->with('property.class_name.property_name')
            ->willReturn($metadata);
        $class = $this->createMock(\ReflectionClass::class);
        $class->expects($this->once())
            ->method('getName')
            ->willReturn('class_name');
        $property = $this->createMock(\ReflectionProperty::class);
        $property->expects($this->once())
            ->method('getDeclaringClass')
            ->willReturn($class);
        $property->expects($this->once())
            ->method('getName')
            ->willReturn('property_name');
        $driver = new MetadataDriver();
        $driver->registerCache($cache);
        $this->assertSame($metadata,$driver->getPropertyMetadata($property));
    }

    public function testNotCachedPropertyMetadata()
    {
        $metadata = $this->createMock(PropertyMetadata::class);
        $cache = $this->createMock(CacheInterface::class);
        $cache->expects($this->once())
            ->method('has')
            ->with('property.class_name.property_name')
            ->willReturn(false);
        $cache->expects($this->once())
            ->method('set')
            ->with('property.class_name.property_name',$metadata)
            ->willReturn(true);
        $class = $this->createMock(\ReflectionClass::class);
        $class->expects($this->once())
            ->method('getName')
            ->willReturn('class_name');
        $property = $this->createMock(\ReflectionProperty::class);
        $property->expects($this->once())
            ->method('getDeclaringClass')
            ->willReturn($class);
        $property->expects($this->once())
            ->method('getName')
            ->willReturn('property_name');
        $customDriver = $this->createMock(MetadataDriverInterface::class);
        $customDriver->expects($this->once())
            ->method('getPropertyMetadata')
            ->with($property)
            ->willReturn($metadata);
        $driver = new MetadataDriver();
        $driver->registerCache($cache);
        $driver->register($customDriver);
        $this->assertSame($metadata,$driver->getPropertyMetadata($property));
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Cannot generate metadata for class_name::property_name with the registered drivers
     */
    public function testGetPropertyMetadataWithNoValidDriver()
    {
        $cache = $this->createMock(CacheInterface::class);
        $cache->expects($this->once())
            ->method('has')
            ->with('property.class_name.property_name')
            ->willReturn(false);
        $class = $this->createMock(\ReflectionClass::class);
        $class->expects($this->exactly(2))
            ->method('getName')
            ->willReturn('class_name');
        $property = $this->createMock(\ReflectionProperty::class);
        $property->expects($this->exactly(2))
            ->method('getDeclaringClass')
            ->willReturn($class);
        $property->expects($this->exactly(2))
            ->method('getName')
            ->willReturn('property_name');
        $customDriver = $this->createMock(MetadataDriverInterface::class);
        $customDriver->expects($this->once())
            ->method('getPropertyMetadata')
            ->with($property)
            ->willReturn(null);
        $driver = new MetadataDriver();
        $driver->registerCache($cache);
        $driver->register($customDriver);
        $driver->getPropertyMetadata($property);
    }

    public function testGetCachedMethodMetadata()
    {
        $metadata = $this->createMock(MethodMetadata::class);
        $cache = $this->createMock(CacheInterface::class);
        $cache->expects($this->once())
            ->method('has')
            ->with('method.class_name.method_name')
            ->willReturn(true);
        $cache->expects($this->once())
            ->method('get')
            ->with('method.class_name.method_name')
            ->willReturn($metadata);
        $class = $this->createMock(\ReflectionClass::class);
        $class->expects($this->once())
            ->method('getName')
            ->willReturn('class_name');
        $method = $this->createMock(\ReflectionMethod::class);
        $method->expects($this->once())
            ->method('getDeclaringClass')
            ->willReturn($class);
        $method->expects($this->once())
            ->method('getName')
            ->willReturn('method_name');
        $driver = new MetadataDriver();
        $driver->registerCache($cache);
        $this->assertSame($metadata,$driver->getMethodMetadata($method));
    }

    public function testNotCachedMethodMetadata()
    {
        $metadata = $this->createMock(MethodMetadata::class);
        $cache = $this->createMock(CacheInterface::class);
        $cache->expects($this->once())
            ->method('has')
            ->with('method.class_name.method_name')
            ->willReturn(false);
        $cache->expects($this->once())
            ->method('set')
            ->with('method.class_name.method_name',$metadata)
            ->willReturn(true);
        $class = $this->createMock(\ReflectionClass::class);
        $class->expects($this->once())
            ->method('getName')
            ->willReturn('class_name');
        $method = $this->createMock(\ReflectionMethod::class);
        $method->expects($this->once())
            ->method('getDeclaringClass')
            ->willReturn($class);
        $method->expects($this->once())
            ->method('getName')
            ->willReturn('method_name');
        $customDriver = $this->createMock(MetadataDriverInterface::class);
        $customDriver->expects($this->once())
            ->method('getMethodMetadata')
            ->with($method)
            ->willReturn($metadata);
        $driver = new MetadataDriver();
        $driver->registerCache($cache);
        $driver->register($customDriver);
        $this->assertSame($metadata,$driver->getMethodMetadata($method));
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Cannot generate metadata for class_name::method_name with the registered drivers
     */
    public function testGetMethodMetadataWithNoValidDriver()
    {
        $cache = $this->createMock(CacheInterface::class);
        $cache->expects($this->once())
            ->method('has')
            ->with('method.class_name.method_name')
            ->willReturn(false);
        $class = $this->createMock(\ReflectionClass::class);
        $class->expects($this->exactly(2))
            ->method('getName')
            ->willReturn('class_name');
        $method = $this->createMock(\ReflectionMethod::class);
        $method->expects($this->exactly(2))
            ->method('getDeclaringClass')
            ->willReturn($class);
        $method->expects($this->exactly(2))
            ->method('getName')
            ->willReturn('method_name');
        $customDriver = $this->createMock(MetadataDriverInterface::class);
        $customDriver->expects($this->once())
            ->method('getMethodMetadata')
            ->with($method)
            ->willReturn(null);
        $driver = new MetadataDriver();
        $driver->registerCache($cache);
        $driver->register($customDriver);
        $driver->getMethodMetadata($method);
    }
}