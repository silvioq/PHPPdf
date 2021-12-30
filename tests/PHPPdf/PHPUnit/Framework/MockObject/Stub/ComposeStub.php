<?php

declare(strict_types=1);

namespace PHPPdf\PHPUnit\Framework\MockObject\Stub;

use PHPUnit\Framework\MockObject\Builder\InvocationStubber;
use PHPUnit\Framework\MockObject\Invocation;
use PHPUnit\Framework\MockObject\Stub\Stub;

/**
 * @method InvocationStubber method($constraint)
 */
class ComposeStub implements Stub
{
    private array $stubs;
    
    public function __construct(array $stubs)
    {

        foreach($stubs as $stub)
        {
            if(!$stub instanceof Stub)
            {
                throw new \InvalidArgumentException('Stubs have to implements PHPUnit_Framework_MockObject_Stub interface.');
            }
        }
        
        $this->stubs = $stubs;
    }

    public function invoke(Invocation $invocation)
    {
        $returnValue = null;
        foreach($this->stubs as $stub)
        {
            $value = $stub->invoke($invocation);
            
            if($value !== null)
            {
                $returnValue = $value;
            }
        }
        
        return $returnValue;        
    }
    
    public function toString(): string
    {
        $text = '';
        
        foreach($this->stubs as $stub)
        {
            $text .= $stub->toString();
        }
        
        return $text;
    }
}
