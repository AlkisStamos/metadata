# Metadata Driver
Provides metadata for PHP classes and their members using a collection of metadata drivers

## Installation
```bash
composer require alks/metadata
```
## Usage
The library comes with 3 metadata driver implementations.
 * The **Reflection** Metadata driver collects data using the PHP Reflection API
 * The **Doc Comment** Metadata driver parses [PHPDoc](https://www.phpdoc.org/) doc blocks
 * The **Annotation** Metadata driver parses [Doctrine Annotations](https://github.com/doctrine/annotations)
 
Basically each driver extends the one(s) above him (the doc comment driver will use the reflection api as well).
In order to use the library generate an instance of the default MetadataDriver and register which drivers (and caches)
to use:
```php
<?php
require_once 'vendor/autoload.php';
$driver = new \Alks\Metadata\MetadataDriver();
$driver->register(new \Alks\Metadata\Driver\DocCommentDriver());
```
If you want to get the full use out of the library just call the enableAnnotations method. This will register the 
Annoation metadata driver which makes use of all the installed drivers behaviour (remember to register the autoloader
in the driver registry):
```php
<?php
require_once 'vendor/autoload.php';
$driver = new \Alks\Metadata\MetadataDriver();
$driver->enableAnnotations(__DIR__.'/var');//extra parameter to cache the doctrine annotation separately
```
Anyway, once you have the driver in place get the metadata like this:
```php
<?php
class ThisIsTheTargetClass {}
// Composer and stuff..
$driver = new \Alks\Metadata\MetadataDriver();
// Register (or enable) all drivers..
/** @var \Alks\Metadata\Metadata\ClassMetadata $metadata */
$metadata = $driver->getClassMetadata(new ReflectionClass(ThisIsTheTargetClass::class));
```