<?php

declare(strict_types=1);

namespace PHPPdf\PHPUnit\Framework;

use PHPPdf\Core\Document;

use PHPPdf\PHPUnit\Framework\Constraint\ValidateByCallback;
use PHPPdf\PHPUnit\Framework\MockObject\Stub\ComposeStub;
use PHPPdf\Core\Engine\Engine;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    public function  __construct($name = NULL, array $data = array(), $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->init();
    }

    protected function init(): void
    {
    }

    public function invokeMethod($object, $methodName, array $args = array())
    {
        $refObject = new \ReflectionObject($object);
        $method = $refObject->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $args);
    }
    
    protected static function returnCompose(array $stubs): ComposeStub
    {
        return new ComposeStub($stubs);
    }
    
    protected static function validateByCallback(\Closure $closure, TestCase $testCase): ValidateByCallback
    {
        return new ValidateByCallback($closure, $testCase);
    }
    
    public function writeAttribute($object, $attributeName, $value): void
    {
        $class = new \ReflectionClass(get_class($object));
        $class->getParentClass();
        $attribute = $this->getProperty($class, $attributeName);
        $attribute->setAccessible(true);
        $attribute->setValue($object, $value);
    }

    protected function getAttribute($object, $attributeName): mixed
    {
        $class     = new \ReflectionClass(get_class($object));
        $attribute = $this->getProperty($class, $attributeName);
        $attribute->setAccessible(true);

        return $attribute->getValue($object);
    }
    
    private function getProperty(\ReflectionClass $class, $name)
    {
        while($class && !$class->hasProperty($name))
        {
            $class = $class->getParentClass();
        }
        
        if($class)
        {
            return $class->getProperty($name);
        }
        
        return null;
    }
    
    protected function createDocumentStub(): Document
    {
        return new Document($this->createMock(Engine::class));
    }
}
