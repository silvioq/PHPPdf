<?php

declare(strict_types=1);

namespace PHPPdf\Test\Core\ComplexAttribute;

use PHPPdf\Core\Node\Container;

use PHPPdf\Core\PdfUnitConverter;
use PHPPdf\Core\Document;
use PHPPdf\ObjectMother\NodeObjectMother;
use PHPPdf\Core\ComplexAttribute\Border;
use PHPPdf\Core\Boundary;
use PHPPdf\Core\Node\Page;
use PHPPdf\Core\Point;
use PHPPdf\Core\Engine\GraphicsContext;
use PHPPdf\Core\Engine\Engine;

class BorderTest extends ComplexAttributeTest
{
    private Border           $border;
    private NodeObjectMother $objectMother;

    public function init(): void
    {
        $this->objectMother = new NodeObjectMother($this);
    }

    public function setUp(): void
    {
        $this->border   = new Border();
        $this->document = new Document($this->createMock(Engine::class));
        $this->document->setUnitConverter(new PdfUnitConverter());
    }

    /**
     * @test
     */
    public function genericEnhance(): void
    {
        $x      = 0;
        $y      = 100;
        $width  = 100;
        $height = 50;

        $gcMock = $this->getMockBuilder(GraphicsContext::class)
                       ->getMock();
        $gcMock->expects($this->once())
               ->method('drawPolygon')
               ->with([-0.5, 100, 100, 0, 0], [100, 100, 50, 50, 100.5], GraphicsContext::SHAPE_DRAW_STROKE);

        $nodeMock = $this->objectMother->getNodeMock($x, $y, $width, $height, $gcMock);

        $this->border->enhance($nodeMock, $this->document);
    }

    /**
     * @test
     * @dataProvider getTypes
     */
    public function settingBorderTypes($typePassed, $typeExcepted): void
    {
        $border = new Border(null, $typePassed);
        $this->assertEquals($typeExcepted, $border->getType());
    }

    public function getTypes(): array
    {
        return [
            ['left+right', Border::TYPE_LEFT | Border::TYPE_RIGHT],
            [Border::TYPE_LEFT | Border::TYPE_RIGHT, Border::TYPE_LEFT | Border::TYPE_RIGHT],
        ];
    }

    /**
     * @test
     */
    public function defaultBorderType(): void
    {
        $this->assertEquals(Border::TYPE_ALL, $this->border->getType());
    }

    /**
     * @test
     */
    public function drawingPartialBorder(): void
    {
        $gcMock = $this->getMockBuilder(GraphicsContext::class)
                       ->getMock();

        $gcMock->expects($this->exactly(2))
               ->method('drawLine')
               ->withConsecutive([], [], [-0.5, 100, 50.5, 100], [50.5, 50, -0.5, 50]);


        $nodeMock = $this->objectMother->getNodeMock(0, 100, 50, 50, $gcMock);

        $border = new Border(null, Border::TYPE_TOP | Border::TYPE_BOTTOM);

        $border->enhance($nodeMock, $this->document);
    }

    /**
     * @test
     */
    public function borderWithNotStandardSize(): void
    {
        $document = $this->getMockBuilder(Document::class)
                         ->onlyMethods(['convertUnit'])
                         ->disableOriginalConstructor()
                         ->getMock();

        $actualSize   = '12px';
        $expectedSize = 2;


        $document->expects($this->exactly(2))
                 ->method('convertUnit')
                 ->withConsecutive([$actualSize], [0])
                 ->willReturnOnConsecutiveCalls($expectedSize, 0);

        $gcMock = $this->getMockBuilder(GraphicsContext::class)
                       ->getMock();

        $gcMock->expects($this->once())
               ->method('setLineWidth')
               ->with($expectedSize);

        $gcMock->expects($this->once())
               ->method('drawLine')
               ->with(0, 2, 0, 11);

        $nodeMock = $this->objectMother->getNodeMock(0, 10, 5, 7, $gcMock);

        $border = new Border(null, Border::TYPE_LEFT, $actualSize);

        $border->enhance($nodeMock, $document);
    }

    /**
     * @test
     */
    public function fullRadiusBorder(): void
    {
        $radius = 50;

        $gcMock = $this->getMockBuilder(GraphicsContext::class)
                       ->getMock();
        $gcMock->expects($this->once())
               ->method('drawRoundedRectangle')
               ->with(0, 70, 50, 100, $radius, GraphicsContext::SHAPE_DRAW_STROKE);

        $nodeMock = $this->objectMother->getNodeMock(0, 100, 50, 30, $gcMock);

        $border = new Border(null, Border::TYPE_ALL, 1, $radius);

        $border->enhance($nodeMock, $this->document);
    }

    /**
     * @test
     */
    public function settingRadiusInStringStyle(): void
    {
        $border = new Border(null, Border::TYPE_ALL, 1, '5 5');

        $this->assertEquals([5, 5, 5, 5], $border->getRadius());
    }

    /**
     * @test
     */
    public function settingCustomizedDashingPatternInStringStyle(): void
    {
        $border = new Border(null, Border::TYPE_ALL, 1, null, '1 2 3');

        $this->assertEquals([1, 2, 3], $border->getStyle());
    }

    /**
     * @test
     * @dataProvider borderStyleProvider
     */
    public function borderStyle($style): void
    {
        $gcMock = $this->getMockBuilder(GraphicsContext::class)
                       ->getMock();

        $gcMock->expects($this->once())
               ->method('setLineDashingPattern')
               ->with($style);

        //at(1) for setLineWidth

        $gcMock->expects($this->once())
               ->method('drawLine')
               ->with(0, 69.5, 0, 100.5);

        $nodeMock = $this->objectMother->getNodeMock(0, 100, 50, 30, $gcMock);

        $border = new Border(null, Border::TYPE_LEFT, 1, null, $style);

        $border->enhance($nodeMock, $this->document);
    }

    public function borderStyleProvider(): array
    {
        return [
            [Border::STYLE_SOLID],
            [Border::STYLE_DOTTED],
        ];
    }

    /**
     * @test
     * @dataProvider borderStyleByStringProvider
     */
    public function settingBorderStyleByString($style, $excepted): void
    {
        $border = new Border(null, Border::TYPE_LEFT, 1, null, $style);
        $this->assertEquals($excepted, $border->getStyle());
    }

    public function borderStyleByStringProvider(): array
    {
        return [
            ['solid', Border::STYLE_SOLID],
            ['dotted', Border::STYLE_DOTTED],
        ];
    }

    /**
     * @test
     *
     */
    public function throwExceptionIfBorderStyleIsInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Border(null, Border::TYPE_LEFT, 1, null, 'invalid_style');
    }

    /**
     * @test
     */
    public function positionCorrectionInFullBorder(): void
    {
        $x                = 0;
        $y                = 100;
        $width            = 50;
        $height           = 30;
        $actualPosition   = '12px';
        $expectedPosition = 2;
        $size             = 1;

        $document = $this->getMockBuilder(Document::class)
                         ->onlyMethods(['convertUnit'])
                         ->disableOriginalConstructor()
                         ->getMock();

        //size conversion
        $document->expects($this->exactly(2))
                 ->method('convertUnit')
                 ->withConsecutive([$size], [$actualPosition])
                 ->willReturnOnConsecutiveCalls($this->returnValue($size), $this->returnValue($expectedPosition));

        $border = new Border(null, Border::TYPE_ALL, $size, null, Border::STYLE_SOLID, $actualPosition);

        $gcMock = $this->getMockBuilder(GraphicsContext::class)
                       ->getMock();

        $nodeMock = $this->objectMother->getNodeMock($x, $y, $width, $height, $gcMock);
        $halfSize = $size / 2;

        $gcMock->expects($this->once())
               ->method('drawPolygon')
               ->with([$x - $halfSize - $expectedPosition, $x + $width + $expectedPosition, $x + $width + $expectedPosition, $x - $expectedPosition, $x - $expectedPosition],
                      [$y + $expectedPosition, $y + $expectedPosition, $y - $height - $expectedPosition, $y - $height - $expectedPosition, $y + $halfSize + $expectedPosition]);

        $border->enhance($nodeMock, $document);
    }

    /**
     * @test
     */
    public function positionCorrectionInPartialBorder(): void
    {
        $x      = 0;
        $y      = 100;
        $width  = 50;
        $height = 30;

        $type             = Border::TYPE_BOTTOM;
        $actualPosition   = '12px';
        $expectedPosition = 2;
        $size             = 1;
        $halfSize         = $size / 2;

        $document = $this->getMockBuilder(Document::class)
                         ->onlyMethods(['convertUnit'])
                         ->disableOriginalConstructor()
                         ->getMock();

        //size conversion
        $document->expects($this->exactly(2))
                 ->method('convertUnit')
                 ->withConsecutive([$size], [$actualPosition])
                 ->willReturnOnConsecutiveCalls($this->returnValue($size), $this->returnValue($expectedPosition));

        $border = new Border(null, $type, $size, null, Border::STYLE_SOLID, $actualPosition);

        $gcMock = $this->getMockBuilder(GraphicsContext::class)
                       ->getMock();

        $nodeMock = $this->objectMother->getNodeMock($x, $y, $width, $height, $gcMock);

        $gcMock->expects($this->once())
               ->method('drawLine')
               ->with($x + $width + $expectedPosition + $halfSize, $y - $height - $expectedPosition, $x - $expectedPosition - $halfSize, $y - $height - $expectedPosition);

        $border->enhance($nodeMock, $document);
    }

    /**
     * @test
     * @dataProvider typeProvider
     */
    public function borderWithNoneAsTypeIsEmpty($type, $expectedEmpty): void
    {
        $border = new Border(null, $type);

        $this->assertEquals($expectedEmpty, $border->isEmpty());
    }

    public function typeProvider(): array
    {
        return [
            [Border::TYPE_NONE, true],
            [Border::TYPE_ALL, false],
            [Border::TYPE_LEFT, false],
        ];
    }

    /**
     * @test
     */
    public function convertColorViaDocumentColorPalette(): void
    {
        $color         = 'color';
        $expectedColor = '#123123';

        $border = new Border($color);

        $gcMock = $this->createMock(GraphicsContext::class);

        $document = $this->getMockBuilder(Document::class)
                         ->onlyMethods(['getColorFromPalette'])
                         ->disableOriginalConstructor()
                         ->getMock();

        $document->expects($this->once())
                 ->method('getColorFromPalette')
                 ->with($color)
                 ->willReturn($expectedColor);

        foreach (['setLineColor', 'setFillColor'] as $method) {
            $gcMock->expects($this->once())
                   ->method($method)
                   ->with($expectedColor);
        }

        $nodeMock = $this->objectMother->getNodeMock(0, 0, 100, 100, $gcMock);

        $border->enhance($nodeMock, $document);
    }

    /**
     * @test
     */
    public function drawCircleBorder(): void
    {
        $color       = '#ffffff';
        $radius      = 100;
        $centerPoint = Point::getInstance(100, 100);
        $background  = new Border('#ffffff');

        $this->assertDrawCircle($background, $color, $radius, $centerPoint, GraphicsContext::SHAPE_DRAW_STROKE);
    }
}
