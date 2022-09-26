<?php


namespace Alks\Metadata\Driver;

use InvalidArgumentException;
use ReflectionClass;

/**
 * Had to manually copy and edit the file due to several deprecation warnings
 */

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */


/**
 * @author David Grudl <david@grudl.com>
 * @copyright David Grudl (https://davidgrudl.com)
 * @see https://github.com/nette/di/blob/ed1b90255688b08b87ae641f2bf1dfcba586ae5b/src/DI/PhpReflection.php
 */
class UseStatements
{

    /** @var array */
    private static array $cache = [];


    /**
     * Expands class name into full name.
     *
     * @param string $name
     * @param ReflectionClass $rc
     * @return string  full name
     */
    public static function expandClassName(string $name, ReflectionClass $rc): string
    {
        $lower = strtolower($name);
        if (empty($name)) {
            throw new InvalidArgumentException('Class name must not be empty.');
        }
        if (self::isBuiltinType($lower)) {
            return $lower;
        }
        if ($lower === 'self' || $lower === 'static' || $lower === '$this') {
            return $rc->getName();
        }
        if ($name[0] === '\\') { // fully qualified name
            return ltrim($name, '\\');
        }
        $uses = self::getUseStatements($rc);
        $parts = explode('\\', $name, 2);
        if (isset($uses[$parts[0]])) {
            $parts[0] = $uses[$parts[0]];
            return implode('\\', $parts);
        }
        if ($rc->inNamespace()) {
            return $rc->getNamespaceName() . '\\' . $name;
        }
        return $name;
    }

    /**
     * @param string $type
     * @return bool
     */
    public static function isBuiltinType(string $type): bool
    {
        return in_array(strtolower($type), ['string', 'int', 'float', 'bool', 'array', 'callable'], TRUE);
    }

    /**
     * @return array of [alias => class]
     */
    public static function getUseStatements(ReflectionClass $class): array
    {
        if (!isset(self::$cache[$name = $class->getName()])) {
            if ($class->isInternal()) {
                self::$cache[$name] = [];
            } else {
                $code = file_get_contents($class->getFileName());
                $currentUseStatements = self::parseUseStatements($code, $name);
                self::$cache = $currentUseStatements + self::$cache;
            }
        }
        return self::$cache[$name];
    }

    /**
     * Parses PHP code.
     *
     * @param string $code
     * @param null $forClass
     * @return array of [class => [alias => class, ...]]
     */
    public static function parseUseStatements(string $code, $forClass = NULL): array
    {
        $tokens = token_get_all($code);
        $namespace = $class = $classLevel = $level = NULL;
        $res = $uses = [];
        for ($i = 0, $iMax = count($tokens); $i < $iMax; $i++) {
            $token = current($tokens);
            next($tokens);
            switch (is_array($token) ? $token[0] : $token) {
                case T_NAMESPACE:
                    $namespace = ltrim(self::fetch($tokens, [T_STRING, T_NS_SEPARATOR]) . '\\', '\\');
                    $uses = [];
                    break;

                case T_CLASS:
                case T_INTERFACE:
                case T_TRAIT:
                    if ($name = self::fetch($tokens, T_STRING)) {
                        $class = $namespace . $name;
                        $classLevel = $level + 1;
                        $res[$class] = $uses;
                        if ($class === $forClass) {
                            return $res;
                        }
                    }
                    break;

                case T_USE:
                    while (!$class && ($name = self::fetch($tokens, [T_STRING, T_NS_SEPARATOR]))) {
                        $name = ltrim($name, '\\');
                        if (self::fetch($tokens, '{')) {
                            while ($suffix = self::fetch($tokens, [T_STRING, T_NS_SEPARATOR])) {
                                if (self::fetch($tokens, T_AS)) {
                                    $uses[self::fetch($tokens, T_STRING)] = $name . $suffix;
                                } else {
                                    $tmp = explode('\\', $suffix);
                                    $uses[end($tmp)] = $name . $suffix;
                                }
                                if (!self::fetch($tokens, ',')) {
                                    break;
                                }
                            }

                        } elseif (self::fetch($tokens, T_AS)) {
                            $uses[self::fetch($tokens, T_STRING)] = $name;

                        } else {
                            $tmp = explode('\\', $name);
                            $uses[end($tmp)] = $name;
                        }
                        if (!self::fetch($tokens, ',')) {
                            break;
                        }
                    }
                    break;

                case T_CURLY_OPEN:
                case T_DOLLAR_OPEN_CURLY_BRACES:
                case '{':
                    $level++;
                    break;

                case '}':
                    if ($level === $classLevel) {
                        $class = $classLevel = NULL;
                    }
                    $level--;
            }
        }

        return $res;
    }

    private static function fetch(&$tokens, $take): ?string
    {
        $res = NULL;
        while ($token = current($tokens)) {
            [$token, $s] = is_array($token) ? $token : [$token, $token];
            if (in_array($token, (array)$take, TRUE)) {
                $res .= $s;
            } elseif (!in_array($token, [T_DOC_COMMENT, T_WHITESPACE, T_COMMENT], TRUE)) {
                break;
            }
            next($tokens);
        }
        return $res;
    }

    private static function iterateTokens($tokens): \Generator
    {
        foreach ($tokens as $key => $token) {
            yield [$key => $token];
        }
    }

}
