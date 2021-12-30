<?php

declare(strict_types=1);

namespace PHPPdf\PHPUnit\Framework\Constraint;

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Framework\TestCase;

class ValidateByCallback extends Constraint
{
    private \Closure $closure;
    private TestCase $testCase;
    private          $failureException;
    private ?bool    $valid = null;

    public function __construct(\Closure $closure, TestCase $testCase)
    {
        $this->closure  = $closure;
        $this->testCase = $testCase;
    }

    public function evaluate($other, $description = '', $returnResult = false): ?bool
    {
        if ($this->valid !== null) {
            return $this->valid;
        }

        try {
            $closure = $this->closure;
            $closure($other, $this->testCase);
        } catch (AssertionFailedError $e) {
            $this->failureException = $e;
            $this->valid            = false;

            return false;
        }

        $this->valid = true;

        return true;
    }

    public function toString(): string
    {
        return $this->failureException->toString();
    }
}
