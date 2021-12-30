<?php

declare(strict_types=1);

namespace PHPPdf\Test\Core\Node\BasicList;

require_once __DIR__.'/EnumerationStrategyTest.php';

use PHPPdf\Core\Node\BasicList;
use PHPPdf\Core\Node\BasicList\UnorderedEnumerationStrategy;
use PHPPdf\Core\Node\BasicList\EnumerationStrategy;

class UnorderedEnumerationStrategyTest extends EnumerationStrategyTest
{
    protected function createStrategy(): UnorderedEnumerationStrategy
    {
        return new UnorderedEnumerationStrategy();
    }

    protected function getExpectedText($elementIndex, $elementPattern)
    {
        return $elementPattern;
    }

    protected function getElementPattern($index)
    {
        $patterns = [BasicList::TYPE_CIRCLE, BasicList::TYPE_SQUARE];//, '%d)');

        return $patterns[$index % 2];
    }

    protected function setElementPattern($list, $pattern): void
    {
        $list->expects($this->atLeastOnce())
             ->method('getType')
             ->willReturn($pattern);
    }

    protected function getListMockedMethod(): array
    {
        return ['getType'];
    }
}
