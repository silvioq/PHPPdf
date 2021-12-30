<?php

declare(strict_types=1);

namespace PHPPdf\Test\Core\Node\BasicList;

use PHPPdf\Core\Document;
use PHPPdf\Core\Node\BasicList;
use PHPPdf\Core\Point;
use PHPPdf\Core\Node\BasicList\ImageEnumerationStrategy;
use PHPPdf\PHPUnit\Framework\TestCase;
use PHPPdf\Core\Node\Container;
use PHPPdf\Core\Engine\GraphicsContext;
use PHPPdf\Core\Engine\Image;
use PHPUnit\Framework\MockObject\MockObject;

class ImageEnumerationStrategyTest extends TestCase
{
    private ImageEnumerationStrategy $strategy;

    public function setUp(): void
    {
        $this->strategy = new ImageEnumerationStrategy();
    }

    /**
     * @test
     * @dataProvider enumerationProvider
     */
    public function drawEnumerationInValidPosition(Point $point, $position, $childMarginLeft, $fontSize): void
    {
        $elementIndex = 1;

        $listMock = $this->getMockBuilder(BasicList::class)
                         ->enableOriginalConstructor()
                         ->onlyMethods(['getChild', 'getAttribute', 'getRecurseAttribute', 'getImage'])
                         ->getMock();

        $imageWidth  = 100;
        $imageHeight = 100;
        $image       = $this->createImageMock($imageWidth, $imageHeight);

        if ($imageWidth > $fontSize) {
            $imageHeight = $imageHeight * $fontSize / $imageWidth;
            $imageWidth  = $fontSize;
        }

        if ($imageHeight > $fontSize) {
            $imageWidth  = $imageWidth * $fontSize / $imageHeight;
            $imageHeight = $fontSize;
        }

        $listMock->expects($this->atLeastOnce())
                 ->method('getImage')
                 ->willReturn($image);

        $listMock->expects($this->atLeastOnce())
                 ->method('getAttribute')
                 ->with('list-position')
                 ->willReturn($position);

        $listMock->expects($this->atLeastOnce())
                 ->method('getRecurseAttribute')
                 ->with('font-size')
                 ->willReturn($fontSize);

        $child = $this->getMockBuilder(Container::class)
                      ->enableOriginalConstructor()
                      ->onlyMethods(['getFirstPoint', 'getMarginLeft'])
                      ->getMock();
        $child->expects($this->atLeastOnce())
              ->method('getFirstPoint')
              ->willReturn($point);
        $child->expects($this->atLeastOnce())
              ->method('getMarginLeft')
              ->willReturn($childMarginLeft);

        $listMock->expects($this->atLeastOnce())
                 ->method('getChild')
                 ->with($elementIndex)
                 ->willReturn($child);

        $xTranslation = 0;
        if ($position === BasicList::LIST_POSITION_OUTSIDE) {
            $xTranslation = $imageWidth;
        }

        $expectedX1Coord = $point->getX() - $childMarginLeft - $xTranslation;
        $expectedY1Coord = $point->getY() - $imageHeight;
        $expectedX2Coord = $point->getX() + $imageWidth - $childMarginLeft - $xTranslation;
        $expectedY2Coord = $point->getY();

        $gc = $this->getMockBuilder(GraphicsContext::class)
                   ->getMock();
        $gc->expects($this->once())
           ->method('drawImage')
           ->with($image, $expectedX1Coord, $expectedY1Coord, $expectedX2Coord, $expectedY2Coord);

        $this->strategy->setIndex($elementIndex);
        $this->strategy->drawEnumeration($this->getDocumentStub(), $listMock, $gc);
    }

    private function getDocumentStub(): Document
    {
        return $this->createDocumentStub();
    }

    private function createImageMock($width, $height): MockObject|\PHPPdf\Core\Engine\Image
    {
        $image = $this->getMockBuilder(Image::class)
                      ->onlyMethods(['getOriginalHeight', 'getOriginalWidth'])
                      ->disableOriginalConstructor()
                      ->getMock();

        $image->expects($this->atLeastOnce())
              ->method('getOriginalHeight')
              ->willReturn($height);
        $image->expects($this->atLeastOnce())
              ->method('getOriginalWidth')
              ->willReturn($width);

        return $image;
    }

    public function enumerationProvider(): array
    {
        return [
            [Point::getInstance(50, 200), BasicList::LIST_POSITION_INSIDE, 20, 10],
        ];
    }

    /**
     * @test
     *
     */
    public function throwExceptionIfImageIsNotSet(): void
    {
        $this->expectException(\LogicException::class);
        $elementIndex = 1;
        $listMock     = $this->getMockBuilder(BasicList::class)
                             ->enableOriginalConstructor()
                             ->onlyMethods(['getImage', 'getChild'])
                             ->getMock();

        $listMock->expects($this->once())
                 ->method('getImage')
                 ->willReturn(null);

        $child = $this->createPartialMock(Container::class, []);

        $listMock->expects($this->atLeastOnce())
                 ->method('getChild')
                 ->with($elementIndex)
                 ->willReturn($child);

        $gc = $this->getMockBuilder(GraphicsContext::class)
                   ->getMock();

        $this->strategy->setIndex($elementIndex);
        $this->strategy->drawEnumeration($this->getDocumentStub(), $listMock, $gc);
    }
}
