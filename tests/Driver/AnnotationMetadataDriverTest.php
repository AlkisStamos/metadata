<?php
/*
 * (c) Alkis Stamos <stamosalkis@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AlkisStamos\Metadata\Tests\Driver;

use AlkisStamos\Metadata\Annotation\Annotation;
use AlkisStamos\Metadata\Annotation\Property;
use AlkisStamos\Metadata\Driver\AnnotationMetadataMetadataDriver;
use AlkisStamos\Metadata\Metadata\ClassMetadata;
use Doctrine\Common\Annotations\Reader;
use PHPUnit\Framework\TestCase;

class AnnotationMetadataDriverTest extends TestCase
{
    /** Class Metadata tests */

    public function testGetClassMetadataWithNoMetadata()
    {
        $reader = $this->createMock(Reader::class);
        $reader->expects($this->once())
            ->method('getClassAnnotations')
            ->willReturn([]);
        $driver = new AnnotationMetadataMetadataDriver($reader);
        $class = $driver->getClassMetadata(new \ReflectionClass(MockClass::class));
        $this->assertInstanceOf(ClassMetadata::class,$class);
        $this->assertSame(MockClass::class,$class->name);
        $this->assertArrayHasKey('path',$class->fileProperties);
        $this->assertSame(__FILE__,$class->fileProperties['path']);
        $this->assertEmpty($class->properties);
        $this->assertEmpty($class->metadata);
        $this->assertEmpty($class->methods);
    }

    public function testGetClassMetadataWithAnnotationMetadata()
    {
        $reader = $this->createMock(Reader::class);
        $annotation = $this->createMock(Annotation::class);
        $reader->expects($this->once())
            ->method('getClassAnnotations')
            ->willReturn([
                $annotation
            ]);
        $driver = new AnnotationMetadataMetadataDriver($reader);
        $class = $driver->getClassMetadata(new \ReflectionClass(MockClass::class));
        $this->assertArrayHasKey(get_class($annotation),$class->metadata);
        $this->assertArrayHasKey(0, $class->metadata[get_class($annotation)]);
        $this->assertSame($annotation,$class->metadata[get_class($annotation)][0]);
    }

    public function testGetClassMetadataFromCache()
    {
        $reader = $this->createMock(Reader::class);
        $annotation = $this->createMock(Annotation::class);
        $reflection = new \ReflectionClass(MockClass::class);
        $reader->expects($this->once())
            ->method('getClassAnnotations')
            ->with($reflection)
            ->willReturn([$annotation]);
        $driver = new AnnotationMetadataMetadataDriver($reader);
        $class = $driver->getClassMetadata($reflection);
        $this->assertSame($annotation,$class->metadata[get_class($annotation)][0]);
        $class2 = $driver->getClassMetadata(new \ReflectionClass(MockClass::class));
        $this->assertSame($class,$class2);
        $this->assertSame($annotation,$class2->metadata[get_class($annotation)][0]);
    }

    public function testGetClassMembersMetadataWithoutAnnotations()
    {
        $reader = $this->createMock(Reader::class);
        $reflection = new \ReflectionClass(MockClassWithMethodsAndProperties::class);
        $reader->expects($this->once())
            ->method('getClassAnnotations')
            ->willReturn([]);
        $reader->expects($this->once())
            ->method('getMethodAnnotations')
            ->willReturn([]);
        $reader->expects($this->once())
            ->method('getPropertyAnnotations')
            ->willReturn([]);
        $driver = new AnnotationMetadataMetadataDriver($reader);
        $classMetadata = $driver->getClassMetadata($reflection);
        $this->assertInstanceOf(ClassMetadata::class, $classMetadata);
        $this->assertSame(1, count($classMetadata->properties));
        $this->assertSame(1, count($classMetadata->methods));
        $this->assertArrayHasKey('method',$classMetadata->methods);
        $this->assertSame('double',$classMetadata->methods['method']->returnType->name);
        $this->assertTrue($classMetadata->methods['method']->returnType->isArray);
        $this->assertArrayHasKey('property', $classMetadata->properties);
        $this->assertSame('integer',$classMetadata->properties['property']->type->name);
    }

    /** Method metadata tests */

    public function testGetMethodMetadataWithDocBlockAnnotation()
    {
        $reader = $this->createMock(Reader::class);
        $reader->expects($this->once())
            ->method('getMethodAnnotations')
            ->willReturn([]);
        $reflection = new \ReflectionMethod(MockClassWithMethods::class,'method1');
        $driver = new AnnotationMetadataMetadataDriver($reader);
        $methodMetadata = $driver->getMethodMetadata($reflection);
        $this->assertSame($methodMetadata->name, 'method1');
        $this->assertSame('string', $methodMetadata->returnType->name);
        $this->assertSame(0, count($methodMetadata->arguments));
        $this->assertSame('protected', $methodMetadata->access);
    }

    public function testGetMethodMetadataWithReturnTypeHint()
    {
        $reader = $this->createMock(Reader::class);
        $reader->expects($this->once())
            ->method('getMethodAnnotations')
            ->willReturn([]);
        $reflection = new \ReflectionMethod(MockClassWithMethods::class,'method2');
        $driver = new AnnotationMetadataMetadataDriver($reader);
        $methodMetadata = $driver->getMethodMetadata($reflection);
        $this->assertSame($methodMetadata->name, 'method2');
        $this->assertSame('int', $methodMetadata->returnType->name);
        $this->assertSame(0, count($methodMetadata->arguments));
        $this->assertSame('private', $methodMetadata->access);
    }

    public function testGetMethodMetadataWithArgument()
    {
        $reader = $this->createMock(Reader::class);
        $reader->expects($this->once())
            ->method('getMethodAnnotations')
            ->willReturn([]);
        $reflection = new \ReflectionMethod(MockClassWithMethods::class,'method3');
        $driver = new AnnotationMetadataMetadataDriver($reader);
        $methodMetadata = $driver->getMethodMetadata($reflection);
        $this->assertSame($methodMetadata->name, 'method3');
        $this->assertSame('array', $methodMetadata->returnType->name);
        $this->assertSame(1, count($methodMetadata->arguments));
        $this->assertSame('public', $methodMetadata->access);
        $this->assertArrayHasKey('param', $methodMetadata->arguments);
        $this->assertSame('int', $methodMetadata->arguments['param']->type->name);
    }

    public function testGetMethodMetadataWithArgumentDocBlock()
    {
        $reader = $this->createMock(Reader::class);
        $reader->expects($this->once())
            ->method('getMethodAnnotations')
            ->willReturn([]);
        $reflection = new \ReflectionMethod(MockClassWithMethods::class,'method4');
        $driver = new AnnotationMetadataMetadataDriver($reader);
        $methodMetadata = $driver->getMethodMetadata($reflection);
        $this->assertSame($methodMetadata->name, 'method4');
        $this->assertSame('bool', $methodMetadata->returnType->name);
        $this->assertTrue($methodMetadata->returnType->isArray);
        $this->assertSame(2, count($methodMetadata->arguments));
        $this->assertSame('public', $methodMetadata->access);
        $this->assertArrayHasKey('param1', $methodMetadata->arguments);
        $this->assertArrayHasKey('param2', $methodMetadata->arguments);
        $this->assertSame('bool', $methodMetadata->arguments['param1']->type->name);
        $this->assertSame('boolean', $methodMetadata->arguments['param2']->type->name);
    }

    public function testGetMethodMetadataWithCustomAnnotations()
    {
        $reader = $this->createMock(Reader::class);
        $annotation1 = new MockClass();
        $annotation2 = new CustomAnnotationForMethod();
        $reader->expects($this->once())
            ->method('getMethodAnnotations')
            ->willReturn([
                $annotation1, $annotation2
            ]);
        $reflection = new \ReflectionMethod(MockClassWithMethods::class,'method1');
        $driver = new AnnotationMetadataMetadataDriver($reader);
        $methodMetadata = $driver->getMethodMetadata($reflection);
        $this->assertSame($methodMetadata->name, 'method1');
        $this->assertSame(1, count($methodMetadata->metadata));
        $this->assertArrayHasKey(CustomAnnotationForMethod::class, $methodMetadata->metadata);
        $this->assertArrayNotHasKey(MockClass::class, $methodMetadata->metadata);
        $this->assertSame($annotation2, $methodMetadata->metadata[CustomAnnotationForMethod::class][0]);
    }

    /** Property Metadata tests */

    public function testGetPropertyMetadataWithoutAnnotations()
    {
        $reader = $this->createMock(Reader::class);
        $reader->expects($this->once())
            ->method('getPropertyAnnotations')
            ->willReturn([]);
        $reflection = new \ReflectionProperty(MockClassWithProperties::class,'property1');
        $driver = new AnnotationMetadataMetadataDriver($reader);
        $property = $driver->getPropertyMetadata($reflection);
        $this->assertSame('property1',$property->name);
        $this->assertNull($property->setter);
        $this->assertSame('private',$property->access);
        $this->assertSame('mixed',$property->type->name);
    }

    public function testGetPropertyMetadataWithDocBlockType()
    {
        $reader = $this->createMock(Reader::class);
        $reader->expects($this->once())
            ->method('getPropertyAnnotations')
            ->willReturn([]);
        $reflection = new \ReflectionProperty(MockClassWithProperties::class,'property2');
        $driver = new AnnotationMetadataMetadataDriver($reader);
        $property = $driver->getPropertyMetadata($reflection);
        $this->assertSame('property2',$property->name);
        $this->assertNull($property->setter);
        $this->assertSame('protected',$property->access);
        $this->assertSame('int',$property->type->name);
    }

    public function testGetPropertyMetadataWithCustomAnnotation()
    {
        $annotation = $this->createMock(Property::class);
        $annotation->expects($this->once())
            ->method('getSetter')
            ->willReturn('setter');
        $annotation->expects($this->once())
            ->method('getGetter')
            ->willReturn('getter');
        $annotation->expects($this->once())
            ->method('getType')
            ->willReturn('string[]');
        $annotation->expects($this->once())
            ->method('getFormat')
            ->willReturn('custom-format');
        $reader = $this->createMock(Reader::class);
        $reader->expects($this->once())
            ->method('getPropertyAnnotations')
            ->willReturn([$annotation]);
        $reflection = new \ReflectionProperty(MockClassWithProperties::class,'property3');
        $driver = new AnnotationMetadataMetadataDriver($reader);
        $property = $driver->getPropertyMetadata($reflection);
        $this->assertSame('property3',$property->name);
        $this->assertSame('protected',$property->access);
        $key = get_class($annotation);
        $this->assertArrayHasKey($key,$property->metadata);
        $this->assertSame($annotation,$property->metadata[$key][0]);
        $this->assertSame('string', $property->type->name);
        $this->assertTrue($property->type->isArray);
        $this->assertSame('setter',$property->setter);
        $this->assertSame('getter',$property->getter);
        $this->assertSame('custom-format',$property->type->format);
    }

    public function testGetPropertyMetadataWithNoTypeAnnotation()
    {
        $annotation = $this->createMock(Property::class);
        $reader = $this->createMock(Reader::class);
        $reader->expects($this->once())
            ->method('getPropertyAnnotations')
            ->willReturn([$annotation]);
        $reflection = new \ReflectionProperty(MockClassWithProperties::class,'property3');
        $driver = new AnnotationMetadataMetadataDriver($reader);
        $property = $driver->getPropertyMetadata($reflection);
        $this->assertSame('property3',$property->name);
        $this->assertSame('protected',$property->access);
        $key = get_class($annotation);
        $this->assertArrayHasKey($key,$property->metadata);
        $this->assertSame($annotation,$property->metadata[$key][0]);
        $this->assertSame('bool', $property->type->name);
    }

    public function testGetPropertyMetadataWithInvalidType()
    {
        $annotation = $this->createMock(Property::class);
        $annotation->expects($this->once())
            ->method('getType')
            ->willReturn('InvalidType');
        $reader = $this->createMock(Reader::class);
        $reader->expects($this->once())
            ->method('getPropertyAnnotations')
            ->willReturn([$annotation]);
        $reflection = new \ReflectionProperty(MockClassWithProperties::class,'property4');
        $driver = new AnnotationMetadataMetadataDriver($reader);
        $property = $driver->getPropertyMetadata($reflection);
        $this->assertSame('property4',$property->name);
        $this->assertSame('protected',$property->access);
        $key = get_class($annotation);
        $this->assertArrayHasKey($key,$property->metadata);
        $this->assertSame($annotation,$property->metadata[$key][0]);
        $this->assertNotNull($property->type);
        $key = get_class($annotation);
        $this->assertArrayHasKey($key,$property->metadata);
        $this->assertSame($annotation,$property->metadata[$key][0]);
        $this->assertSame('mixed', $property->type->name);
    }

    public function testGetPropertyMetadataWithMultipleTypes()
    {
        $reader = $this->createMock(Reader::class);
        $reader->expects($this->once())
            ->method('getPropertyAnnotations')
            ->willReturn([]);
        $reflection = new \ReflectionProperty(MockClassWithProperties::class,'property5');
        $driver = new AnnotationMetadataMetadataDriver($reader);
        $property = $driver->getPropertyMetadata($reflection);
        $this->assertSame('property5',$property->name);
        $this->assertNull($property->setter);
        $this->assertSame('protected',$property->access);
        $typeName = $property->type;
        $this->assertSame('string',"$typeName");
    }
}

class MockClass {}
class MockClassWithMethodsAndProperties
{
    /** @var integer */
    protected $property;
    /** @return double[] */
    protected function method() { return []; }
}
class MockClassWithProperties
{
    private $property1;
    /** @var int */
    protected $property2 = 1;
    /** @var bool */
    protected $property3;
    /** @var Invalid-type */
    protected $property4;
    /** @var string|bool[]|int|null */
    protected $property5;
}
class MockClassWithMethods
{
    public function __construct(){}
    /** @return string */
    protected function method1() { return ''; }
    private function method2():int { return 0; }
    public function method3(int $param): array { return []; }
    /**
     * @param boolean $param2
     * @return bool[]
     */
    public function method4(bool $param1, $param2) { return []; }
}
class CustomAnnotationForMethod extends Annotation {}