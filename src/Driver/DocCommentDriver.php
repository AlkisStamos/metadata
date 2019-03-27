<?php
/*
 * (c) Alkis Stamos <stamosalkis@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AlkisStamos\Metadata\Driver;
use AlkisStamos\Metadata\Metadata\ArgumentMetadata;
use AlkisStamos\Metadata\Metadata\MethodMetadata;
use AlkisStamos\Metadata\Metadata\PropertyMetadata;
use AlkisStamos\Metadata\Metadata\TypeMetadata;

/**
 * @package Metadata
 * @author Alkis Stamos <stamosalkis@gmail.com>
 * @license MIT
 * @copyright Alkis Stamos
 *
 * Generates metadata by parsing common PHPDoc (https://www.phpdoc.org/) comments
 */
class DocCommentDriver extends ReflectionMetadataDriver
{
    /**
     * Generates the property metadata from a reflection property
     *
     * @param \ReflectionProperty $property
     * @return PropertyMetadata|null
     */
    public function getPropertyMetadata(\ReflectionProperty $property): ?PropertyMetadata
    {
        $propertyMetadata = parent::getPropertyMetadata($property);
        $type = $this->fromDocBlockString($property->getDocComment());
        if($type !== null)
        {
            $propertyMetadata->type = $type;
        }
        return $propertyMetadata;
    }

    /**
     * Generates the method metadata from a reflection method
     *
     * @param \ReflectionMethod $method
     * @return MethodMetadata|null
     */
    public function getMethodMetadata(\ReflectionMethod $method): ?MethodMetadata
    {
        $methodMetadata = parent::getMethodMetadata($method);
        $docBlock = null;
        if($methodMetadata->returnType === null)
        {
            $docBlock = $this->parseDocblockProperties($method->getDocComment());
            $methodMetadata->returnType = $this->fromDocBlockString($docBlock,'return',true);
        }
        $arguments = $method->getParameters();
        foreach($arguments as $argument)
        {
            $argumentMetadata = $methodMetadata->getArgumentMetadata($argument->getName());
            if($argumentMetadata === null)
            {
                $argumentMetadata = new ArgumentMetadata($argument->getName());
                $methodMetadata->addArgumentMetadata($argumentMetadata);
            }
            if($argumentMetadata->type === null)
            {
                $docBlock = $docBlock ?? $this->parseDocblockProperties($method->getDocComment());
                $argumentMetadata->type = $this->parseDocBlockParameter($docBlock,$argumentMetadata->name,true);
            }
        }
        return $methodMetadata;
    }

    /**
     * Generates a type from a doc block string. The method will search the $target in the doc block, if the $target is
     * not found or the type generated is unknown the method will return null. If the parsed flag is set to true the
     * method will assume that the $docBlock is already parsed from the parseDocblockProperties method (in order to
     * reuse the same parsed info)
     *
     * @param $docBlock
     * @param string $target
     * @param boolean $parsed
     * @return TypeMetadata|null
     */
    private function fromDocBlockString($docBlock, $target='var', $parsed=false)
    {
        $props = $parsed === true ? $docBlock : $this->parseDocblockProperties($docBlock);
        if(isset($props[$target][0]))
        {
            return self::fromDocBlockProperty($props[$target][0]);
        }
        return null;
    }
    
    /**
     * Parses the type from a doc block param line ("@param string $argument"). For complex types/classes the fully
     * qualified name should be used in order to parse the type
     *
     * @param $docBlock
     * @param string $parameterName
     * @param bool $parsed
     * @param string $target
     * @return TypeMetadata|null
     */
    private function parseDocBlockParameter($docBlock, string $parameterName, bool $parsed, string $target='param'): ?TypeMetadata
    {
        $props = $parsed === true ? $docBlock : $this->parseDocblockProperties($docBlock);
        if(isset($props[$target]))
        {
            foreach($props[$target] as $prop)
            {
                if(strpos($prop,$parameterName) !== false)
                {
                    return $this->fromDocBlockProperty(trim(str_replace('$'.$parameterName,'',$prop)));
                }
            }
        }
        return null;
    }

    /**
     * Does generate the type instance from a doc block property. Is defined separately for reusability.
     *
     * @param $property
     * @return TypeMetadata|null
     */
    private function fromDocBlockProperty($property)
    {
        $isNullable = false;
        $isArray = false;
        $typeName = $property;
        $this->isMultipleTypes($property,$typeName,$isNullable);
        if($this->isCollectionType($typeName,$typeName))
        {
            $isArray = true;
        }
        $isFlat = $this->isFlatType($typeName);
        if(!$isFlat)
        {
            if(!class_exists($typeName))
            {
                return null;
            }
        }
        $typeMetadata= new TypeMetadata($typeName);
        $typeMetadata->isArray = $isArray;
        $typeMetadata->isNullable = $isNullable;
        $typeMetadata->isFlat = $isFlat;
        return $typeMetadata;
    }

    /**
     * The method will check if multiple types were defined in a doc block for a given type. If so it will extract the
     * real type name (the first one defined) but will also check if the type is nullable, eg:
     * string|null|mixed => true, realType='string', nullable=true
     *
     * @param $docBlockType
     * @param $realType
     * @param $isNullable
     * @return bool
     */
    private function isMultipleTypes($docBlockType, &$realType, &$isNullable)
    {
        if(strpos($docBlockType,'|') !== false)
        {
            if(stripos('|' . $docBlockType . '|', '|null|') !== false)
            {
                $isNullable = true;
            }
            $realType = explode('|',$docBlockType)[0];
            return true;
        }
        $realType = $docBlockType;
        $isNullable = false;
        return false;
    }

    /**
     * Copied from PHPUnit 3.7.29, Util/Test.php
     *
     * @param string $docBlock Full method docblock
     * @return array
     */
    private function parseDocblockProperties($docBlock)
    {
        $properties = array();
        $docBlock = substr($docBlock, 3, -2);
        $re = '/@(?P<name>[A-Za-z_-]+)(?:[ \t]+(?P<value>.*?))?[ \t]*\r?$/m';
        if (preg_match_all($re, $docBlock, $matches)) {
            $numMatches = count($matches[0]);
            for ($i = 0; $i < $numMatches; ++$i) {
                $properties[$matches['name'][$i]][] = $matches['value'][$i];
            }
        }
        return $properties;
    }
}