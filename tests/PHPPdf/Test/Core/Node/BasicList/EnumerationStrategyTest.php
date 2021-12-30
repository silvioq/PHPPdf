<?php

declare(strict_types=1);

namespace PHPPdf\Test\Core\Node\BasicList;

use PHPPdf\Core\Node\BasicList\AbstractEnumerationStrategy;
use PHPPdf\Core\Node\BasicList\EnumerationStrategy;
use PHPPdf\Core\Point;
use PHPPdf\Core\Node\BasicList;
use PHPPdf\PHPUnit\Framework\TestCase;
use PHPPdf\Core\Node\Container;
use PHPPdf\Core\Engine\GraphicsContext;
use PHPPdf\Core\Document;
use PHPPdf\Core\Engine\Font;

abstract class EnumerationStrategyTest extends TestCase
{
    protected AbstractEnumerationStrategy $strategy;

    public function setUp(): void
    {
        $this->strategy = $this->createStrategy();
    }

    abstract protected function createStrategy();

    /**
     * @test
     * @dataProvider integerProvider
     */
    public function drawEnumerationOnValidPosition($elementIndex, Point $point, $position, $childMarginLeft, $elementPattern, $paddingTop = 0): void
    {
        $listMock     = $this->getMockBuilder(BasicList::class)
                             ->onlyMethods(array_merge($this->getListMockedMethod(), ['getChild', 'getAttribute', 'getEncoding', 'getFontSizeRecursively', 'getRecurseAttribute', 'getFontType', 'getFont']))
                             ->getMock();
        $fontTypeMock = $this->getMockBuilder(Font::class)
                             ->getMock();

        $colorStub = '#123456';

        $this->setElementPattern($listMock, $elementPattern);

        $fontSize = random_int(10, 15);
        $encoding = 'utf-8';

        $expectedText = $this->getExpectedText($elementIndex, $elementPattern);

        $child = $this->createPartialMock(Container::class, ['getFirstPoint', 'getMarginLeft', 'getPaddingTop']);
        $child->expects($this->atLeastOnce())
              ->method('getFirstPoint')
              ->willReturn($point);
        $child->expects($this->once())
              ->method('getMarginLeft')
              ->willReturn($childMarginLeft);
        $child->expects($this->once())
              ->method('getPaddingTop')
              ->willReturn($paddingTop);

        $listMock->expects($this->once())
                 ->method('getChild')
                 ->with($elementIndex)
                 ->willReturn($child);

        $positionTranslation = 0;

        if ($position == BasicList::LIST_POSITION_OUTSIDE) {
            $expectedWidth       = random_int(3, 7);
            $positionTranslation -= $expectedWidth;

            $fontTypeMock->expects($this->once())
                         ->method('getWidthOfText')
                         ->with($expectedText, $fontSize)
                         ->willReturn($expectedWidth);
        } else {
            $fontTypeMock->expects($this->atLeastOnce())
                         ->method('getWidthOfText');
        }

        $document = $this->getMockBuilder(Document::class)
                         ->onlyMethods(['getFont'])
                         ->disableOriginalConstructor()
                         ->getMock();

        $listMock->expects($this->atLeastOnce())
                 ->method('getAttribute')
                 ->with('list-position')
                 ->willReturn($position);
        $listMock->expects($this->atLeastOnce())
                 ->method('getFontSizeRecursively')
                 ->willReturn($fontSize);
        $listMock->expects($this->atLeastOnce())
                 ->method('getRecurseAttribute')
                 ->with('color')
                 ->willReturn($colorStub);

        $listMock->expects($this->once())
                 ->method('getEncoding')
                 ->willReturn($encoding);
        $listMock->expects($this->atLeastOnce())
                 ->method('getFont')
                 ->with($document)
                 ->willReturn($fontTypeMock);

        $gc = $this->getMockBuilder(GraphicsContext::class)
                   ->getMock();

        $expectedXCoord = $point->getX() + $positionTranslation - $childMarginLeft;
        //padding-top has influence also on position of enumeration symbol
        $expectedYCoord = $point->getY() - $fontSize - $paddingTop;

        $i = 2;
        $gc->expects($this->once())
           ->id('1')
           ->method('saveGS');
        $gc->expects($this->once())
           ->id('2')
           ->after('1')
           ->method('setLineColor')
           ->with($colorStub);
        $gc->expects($this->once())
           ->id('3')
           ->after('2')
           ->method('setFillColor')
           ->with($colorStub);
        $gc->expects($this->once())
           ->id('4')
           ->after('3')
           ->method('setFont')
           ->with($fontTypeMock, $fontSize);

        $gc->expects($this->once())
           ->id('5')
           ->after('4')
           ->method('drawText')
           ->with($expectedText, $expectedXCoord, $expectedYCoord, $encoding);
        $gc->expects($this->once())
           ->after('5')
           ->method('restoreGS');

        $this->strategy->setIndex($elementIndex);
        $this->strategy->setVisualIndex($elementIndex + 1);
        $this->strategy->drawEnumeration($document, $listMock, $gc);
    }

    public function integerProvider(): array
    {
        return [
            [5, Point::getInstance(10, 30), BasicList::LIST_POSITION_OUTSIDE, 20, $this->getElementPattern(0)],
            [12, Point::getInstance(100, 300), BasicList::LIST_POSITION_INSIDE, 40, $this->getElementPattern(1)],
            [12, Point::getInstance(100, 300), BasicList::LIST_POSITION_INSIDE, 40, $this->getElementPattern(1), 5],
        ];
    }

    abstract protected function getExpectedText($elementIndex, $elementPattern);

    abstract protected function getElementPattern($index);

    abstract protected function setElementPattern($list, $pattern);

    protected function getListMockedMethod(): array
    {
        return [];
    }
}
