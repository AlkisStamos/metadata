<?php
/*
 * (c) Alkis Stamos <stamosalkis@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AlkisStamos\Metadata\Tests\Driver;
use AlkisStamos\Metadata\Driver\DocCommentMetadataDriver;
use PHPUnit\Framework\TestCase;

class DocCommentMetadataDriverTest extends TestCase
{
    public function testGetMethodMetadataForUnknownArgument()
    {
        $argument = $this->createMock(\ReflectionParameter::class);
        $argument->expects($this->exactly(2))
            ->method('getName')
            ->willReturn('argumentName');
        $method = $this->createMock(\ReflectionMethod::class);
        $method->expects($this->exactly(2))
            ->method('getParameters')
            ->will($this->onConsecutiveCalls([],[$argument]));
        $method->expects($this->once())
            ->method('getName')
            ->willReturn('methodName');
        $method->expects($this->once())
            ->method('getReturnType')
            ->willReturn(null);
        $driver = new DocCommentMetadataDriver();
        $methodMetadata = $driver->getMethodMetadata($method);
        $this->assertEquals('methodName',$methodMetadata->name);
        $this->assertEquals(1,count($methodMetadata->arguments));
        $this->assertArrayHasKey('argumentName',$methodMetadata->arguments);
        $this->assertEquals('argumentName',$methodMetadata->arguments['argumentName']->name);
    }
}