<?php
/*
 * (c) Alkis Stamos <stamosalkis@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AlkisStamos\Metadata\Cache;
/**
 * @package Metadata
 * @author Alkis Stamos <stamosalkis@gmail.com>
 * @license MIT
 * @copyright Alkis Stamos
 *
 * Implementation of the psr/simple-cache InvalidArgumentException interface
 */
class InvalidArgumentException extends \InvalidArgumentException implements \Psr\SimpleCache\InvalidArgumentException {}