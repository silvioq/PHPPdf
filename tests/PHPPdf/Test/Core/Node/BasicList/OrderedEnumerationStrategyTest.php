<?php

declare(strict_types=1);

namespace PHPPdf\Test\Core\Node\BasicList;

require_once __DIR__.'/EnumerationStrategyTest.php';

use PHPPdf\Core\Node\BasicList\EnumerationStrategy;
use PHPPdf\Core\Node\BasicList;
use PHPPdf\Core\Point;
use PHPPdf\Core\Node\Container;
use PHPPdf\Core\Node\BasicList\OrderedEnumerationStrategy;

class OrderedEnumerationStrategyTest extends EnumerationStrategyTest
{
    protected function createStrategy(): OrderedEnumerationStrategy
    {
        return new OrderedEnumerationStrategy();
    }

    protected function getExpectedText($elementIndex, $elementPattern): string
    {
        return sprintf($elementPattern, $elementIndex + 1);
    }

    protected function getElementPattern($index): string
    {
        $patterns = ['%d.'];//, '%d)');

        return $patterns[$index % 1];
    }

    protected function setElementPattern($list, $pattern)
    {
    }
}
