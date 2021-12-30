<?php

declare(strict_types=1);

namespace PHPPdf\Test\Core\Formatter;

use PHPPdf\Core\Node\BasicList;
use PHPPdf\Core\Document;
use PHPPdf\Core\Formatter\ListFormatter;
use PHPPdf\PHPUnit\Framework\TestCase;
use PHPPdf\Core\Engine\Engine;
use PHPPdf\Core\Node\BasicList\EnumerationStrategy;
use PHPPdf\Core\Node\Container;

class ListFormatterTest extends TestCase
{
    private ListFormatter $formatter;

    public function setUp(): void
    {
        $this->formatter = new ListFormatter();
    }

    /**
     * @test
     */
    public function ifListsPositionIsOutsidePositionOfChildrenWontBeTranslated(): void
    {
        $list = $this->createPartialMock(BasicList::class, ['getChildren', 'getAttribute', 'assignEnumerationStrategyFromFactory']);

        $list->expects($this->once())
             ->method('getAttribute')
             ->with('list-position')
             ->willReturn(BasicList::LIST_POSITION_OUTSIDE);

        $list->expects($this->never())
             ->method('getChildren');

        $list->expects($this->once())
             ->method('assignEnumerationStrategyFromFactory');

        $this->formatter->format($list, $this->createDocumentStub());
    }

    /**
     * @test
     */
    public function ifListsPositionIsInsidePositionOfChildrenWillBeTranslated(): void
    {
        $widthOfEnumerationChar = 7;

        $documentStub = new Document($this->createMock(Engine::class));

        $list = $this->createPartialMock(BasicList::class, ['getChildren', 'getEnumerationStrategy', 'getAttribute', 'assignEnumerationStrategyFromFactory']);

        $enumerationStrategy = $this->getMockBuilder(EnumerationStrategy::class)
                                    ->getMock();

        $list->expects($this->once())
             ->after('assign')
             ->method('getEnumerationStrategy')
             ->willReturn($enumerationStrategy);

        $list->expects($this->once())
             ->method('getAttribute')
             ->with('list-position')
             ->willReturn(BasicList::LIST_POSITION_INSIDE);

        $list->expects($this->once())
             ->id('assign')
             ->method('assignEnumerationStrategyFromFactory');

        $enumerationStrategy->expects($this->once())
                            ->method('getWidthOfTheBiggestPosibleEnumerationElement')
                            ->with($documentStub, $list)
                            ->willReturn($widthOfEnumerationChar);

        $children   = [];
        $leftMargin = 10;
        for ($i = 0; $i < 2; $i++) {
            $child = $this->createPartialMock(Container::class, ['setAttribute', 'getMarginLeft']);
            $child->expects($this->once())
                  ->method('getMarginLeft')
                  ->willReturn($leftMargin);
            $child->expects($this->once())
                  ->method('setAttribute')
                  ->with('margin-left', $widthOfEnumerationChar + $leftMargin);
            $children[] = $child;
        }

        $list->expects($this->atLeastOnce())
             ->method('getChildren')
             ->willReturn($children);

        $this->formatter->format($list, $documentStub);
    }
}
