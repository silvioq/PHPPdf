<?php

declare(strict_types=1);

namespace PHPPdf\Test\Core\ComplexAttribute;

use PHPPdf\Core\Boundary;
use PHPPdf\Core\Node\Circle;
use PHPPdf\Core\Engine\GraphicsContext;
use PHPPdf\ObjectMother\NodeObjectMother;
use PHPPdf\Core\Document;
use PHPPdf\Core\ComplexAttribute\Background,
    PHPPdf\Core\Node\Page,
    PHPPdf\Core\Point;
use PHPPdf\Core\Node\Container;
use PHPPdf\Core\Engine\Image;
use PHPUnit\Framework\MockObject\MockObject;
use PHPPdf\Core\Node\Node;

class BackgroundTest extends ComplexAttributeTest
{
    private const IMAGE_WIDTH  = 30;
    private const IMAGE_HEIGHT = 30;

    private NodeObjectMother $objectMother;

    public function init(): void
    {
        $this->objectMother = new NodeObjectMother($this);
    }

    public function setUp(): void
    {
        $this->document = $this->getMockBuilder(Document::class)
                               ->onlyMethods(['convertUnit'])
                               ->disableOriginalConstructor()
                               ->getMock();
    }

    /**
     * @test
     */
    public function backgroundWithoutRepeat(): void
    {
        $imageWidth  = 100;
        $imageHeight = 120;

        $imagePath  = 'image/path';
        $background = new Background(null, $imagePath);

        $image    = $this->createImageMock($imageWidth, $imageHeight);
        $document = $this->createDocumentMock($imagePath, $image);

        $x     = 0;
        $y     = 200;
        $width = $height = 100;

        $gcMock = $this->getMockBuilder(GraphicsContext::class)
                       ->getMock();

        $nodeMock = $this->getNodeMock($x, $y, $width, $height, $gcMock);

        $gcMock->expects($this->once())
               ->id('1')
               ->method('saveGS');

        $gcMock->expects($this->once())
               ->id('2')
               ->after('1')
               ->method('clipRectangle')
               ->with($x, $y, $x + $width, $y - $height);

        $gcMock->expects($this->once())
               ->id('3')
               ->after('2')
               ->method('drawImage')
               ->with($image, $x, $y - $imageHeight, $x + $imageWidth, $y);

        $gcMock->expects($this->once())
               ->after('3')
               ->method('restoreGS');

        $background->enhance($nodeMock, $document);
    }

    private function createImageMock($width, $height): Image|MockObject
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

    private function createDocumentMock($imagePath, $image, $mockUnitConverterInterface = true): MockObject|Document
    {
        $methods = ['createImage'];

        if ($mockUnitConverterInterface) {
            $methods = array_merge($methods, ['convertUnit', 'convertPercentageValue']);
        }

        $document = $this->getMockBuilder(Document::class)
                         ->onlyMethods($methods)
                         ->disableOriginalConstructor()
                         ->getMock();
        $document->expects($this->once())
                 ->method('createImage')
                 ->with($imagePath)
                 ->willReturn($image);

        return $document;
    }

    /**
     * @test
     * @dataProvider kindOfBackgroundsProvider
     */
    public function backgroundWithRepeat($repeat): void
    {
        $x     = 0;
        $y     = 200;
        $width = $height = 100;

        $imageWidth  = 100;
        $imageHeight = 120;
        $imagePath   = 'image/path';

        $image    = $this->createImageMock($imageWidth, $imageHeight);
        $document = $this->createDocumentMock($imagePath, $image);

        $background = new Background(null, $imagePath, $repeat);

        $x = 1;
        if ($repeat & Background::REPEAT_X) {
            $x = ceil($width / $imageWidth);
        }

        $y = 1;
        if ($repeat & Background::REPEAT_Y) {
            $y = ceil($height / $imageHeight);
        }

        $count = (int) ($x * $y);

        $gcMock = $this->getMockBuilder(GraphicsContext::class)
                       ->getMock();

        $nodeMock = $this->getNodeMock($x, $y, $width, $height, $gcMock);

        $gcMock->expects($this->once())
               ->method('saveGS');

        $gcMock->expects($this->once())
               ->method('clipRectangle')
               ->with($x, $y, $x + $width, $y - $height);

        $gcMock->expects($this->exactly($count))
               ->method('drawImage');

        $gcMock->expects($this->once())
               ->method('restoreGS');


        $background->enhance($nodeMock, $document);
    }

    public function kindOfBackgroundsProvider()
    {
        return [
            [Background::REPEAT_X],
            [Background::REPEAT_Y],
            [Background::REPEAT_ALL],
        ];
    }

    private function getNodeMock($x, $y, $width, $height, $gcMock): Node|MockObject
    {
        $boundaryMock = $this->getBoundaryStub($x, $y, $width, $height);

        $nodeMock = $this->getMockBuilder(Node::class)
                         ->enableOriginalConstructor()
                         ->onlyMethods(['getBoundary', 'getWidth', 'getHeight', 'getGraphicsContext'])
                         ->getMock();
        $nodeMock->expects($this->atLeastOnce())
                 ->method('getBoundary')
                 ->willReturn($boundaryMock);
        $nodeMock
            ->method('getWidth')
            ->willReturn($width);

        $nodeMock
            ->method('getHeight')
            ->willReturn($height);

        $nodeMock->expects($this->atLeastOnce())
                 ->method('getGraphicsContext')
                 ->willReturn($gcMock);

        return $nodeMock;
    }

    private function getBoundaryStub($x, $y, $width, $height): Boundary
    {
        $boundaryMock = new Boundary();

        $points = [
            Point::getInstance($x, $y),
            Point::getInstance($x + $width, $y),
            Point::getInstance($x + $width, $y - $height),
            Point::getInstance($x, $y - $height),
        ];

        foreach ($points as $point) {
            $boundaryMock->setNext($point);
        }
        $boundaryMock->close();

        return $boundaryMock;
    }

    /**
     * @test
     */
    public function radiusColorBorder(): void
    {
        $radius = 50;

        $gcMock = $this->getMockBuilder(GraphicsContext::class)
                       ->getMock();
        $gcMock->expects($this->once())
               ->method('drawRoundedRectangle')
               ->with(0, 70, 50, 100, $radius, GraphicsContext::SHAPE_DRAW_FILL_AND_STROKE);

        $this->addStandardExpectationsToGraphicContext($gcMock);

        $nodeMock = $this->getNodeMock(0, 100, 50, 30, $gcMock);

        $border = new Background('black', null, Background::REPEAT_ALL, $radius);

        $border->enhance($nodeMock, $this->document);
    }

    private function addStandardExpectationsToGraphicContext($gcMock): void
    {
        $gcMock->expects($this->once())
               ->method('saveGS');
        $gcMock->expects($this->once())
               ->method('restoreGS');
        $gcMock->expects($this->once())
               ->method('setLineColor');
        $gcMock->expects($this->once())
               ->method('setFillColor');
    }

    /**
     * @test
     * @dataProvider repeatProvider
     */
    public function convertRepeatAsStringToConstat($string, $expected): void
    {
        $complexAttribute = new Background(null, null, $string);

        $this->assertEquals($expected, $complexAttribute->getRepeat());
    }

    public function repeatProvider(): array
    {
        return [
            ['none', Background::REPEAT_NONE],
            ['x', Background::REPEAT_X],
            ['y', Background::REPEAT_Y],
            ['all', Background::REPEAT_ALL],
        ];
    }

    /**
     * @test
     */
    public function useRealBoundaryWhenRealDimensionParameterIsSetted(): void
    {
        $complexAttribute = new Background('black', null, Background::REPEAT_ALL, null, true);

        $node = $this->getMockBuilder(Container::class)
                     ->onlyMethods(['getRealBoundary', 'getBoundary', 'getGraphicsContext'])
                     ->getMock();

        $height   = 100;
        $width    = 100;
        $boundary = $this->getBoundaryStub(0, 100, $width, $height);

        $node->expects($this->atLeastOnce())
             ->method('getRealBoundary')
             ->willReturn($boundary);

        $node->expects($this->never())
             ->method('getBoundary');

        $gc = $this->getMockBuilder(GraphicsContext::class)
                   ->getMock();

        $expectedXCoords = [
            $boundary[0]->getX(),
            $boundary[1]->getX(),
            $boundary[1]->getX(),
            $boundary[0]->getX(),
            $boundary[0]->getX(),
        ];
        $expectedYCoords = [
            $boundary[0]->getY(),
            $boundary[0]->getY(),
            $boundary[2]->getY(),
            $boundary[2]->getY(),
            $boundary[0]->getY(),
        ];

        $gc->expects($this->once())
           ->method('drawPolygon')
           ->with($expectedXCoords, $expectedYCoords, $this->anything());

        $node->expects($this->atLeastOnce())
             ->method('getGraphicsContext')
             ->willReturn($gc);

        $complexAttribute->enhance($node, $this->document);
    }

    /**
     * @test
     * @dataProvider booleanProvider
     */
    public function convertStringBooleanValuesToBooleanTypeForUseRealDimensionParameter($value, $expected): void
    {
        $complexAttribute = new Background('black', null, Background::REPEAT_ALL, null, $value);

        $this->assertSame($expected, $this->getAttribute($complexAttribute, 'useRealDimension'));
    }

    public function booleanProvider(): array
    {
        return [
            ['1', true],
            ['0', false],
            ['true', true],
            ['false', false],
            ['no', false],
            ['yes', true],
        ];
    }

    /**
     * @test
     * @dataProvider imageDimensionProvider
     */
    public function useBackgrounImageDimension($percentWidth, $expectedWidth, $percentHeight, $expectedHeight, $expectedHorizontalTranslation, $expectedVertiacalTranslation, $nodeWidth = 100, $nodeHeight = 100): void
    {
        $imagePath = 'image/path';

        $image    = $this->createImageMock(self::IMAGE_WIDTH, self::IMAGE_HEIGHT);
        $document = $this->createDocumentMock($imagePath, $image);

        $x = 0;
        $y = $nodeHeight;
        $document->expects($this->exactly(2))
                 ->method('convertUnit')
                 ->withConsecutive([$percentWidth], [$percentHeight])
                 ->willReturnOnConsecutiveCalls($this->returnValue($percentWidth), $this->returnValue($percentHeight));

        $document->expects($this->exactly(2))
                 ->method('convertPercentageValue')
                 ->withConsecutive([$percentWidth, $nodeWidth], [$percentHeight, $nodeHeight])
                 ->willReturnOnConsecutiveCalls($this->returnValue($expectedWidth), $this->returnValue($expectedHeight));

        $complexAttribute = new Background(null, $imagePath, Background::REPEAT_NONE, null, false, $percentWidth, $percentHeight);

        $gcMock = $this->getMockBuilder(GraphicsContext::class)
                       ->getMock();

        $nodeMock = $this->getNodeMock($x, $y, $nodeWidth, $nodeHeight, $gcMock);

        $gcMock->expects($this->once())
               ->method('drawImage')
               ->with($image, $x, $y - $expectedVertiacalTranslation, $x + $expectedHorizontalTranslation, $y);

        $complexAttribute->enhance($nodeMock, $document);
    }

    public function imageDimensionProvider(): array
    {
        return [
            [self::IMAGE_WIDTH / 2, self::IMAGE_WIDTH / 2, null, null, self::IMAGE_WIDTH / 2, self::IMAGE_HEIGHT / 2],
            [null, null, self::IMAGE_HEIGHT / 2, self::IMAGE_HEIGHT / 2, self::IMAGE_WIDTH / 2, self::IMAGE_HEIGHT / 2],
            ['30%', 60, null, null, 60, 60, 200, 200],
            [null, null, '40%', 80, 80, 80, 200, 200],
        ];
    }

    /**
     * @test
     * @dataProvider positionProvider
     */
    public function drawImageAsBackgroundInProperPosition($nodeXCoord, $positionX, $positionY, $expectedPositionX, $expectedPositionY): void
    {
        $imagePath = 'image/path';

        $image    = $this->createImageMock(self::IMAGE_WIDTH, self::IMAGE_HEIGHT);
        $document = $this->createDocumentMock($imagePath, $image, false);

        $complexAttribute = new Background(null, $imagePath, Background::REPEAT_NONE, null, false, null, null, $positionX, $positionY);

        $gcMock = $this->getMockBuilder(GraphicsContext::class)
                       ->getMock();

        $y          = self::IMAGE_HEIGHT * 2;
        $nodeWidth  = 100;
        $nodeHeight = $y;

        $nodeMock = $this->getNodeMock($nodeXCoord, $y, $nodeWidth, $nodeHeight, $gcMock);

        $gcMock->expects($this->once())
               ->method('drawImage')
               ->with($image, $expectedPositionX, $expectedPositionY - self::IMAGE_HEIGHT, $expectedPositionX + self::IMAGE_WIDTH, $expectedPositionY);

        $complexAttribute->enhance($nodeMock, $document);
    }

    public function positionProvider(): array
    {
        return [
            [10, Background::POSITION_LEFT, Background::POSITION_TOP, 10, 2 * self::IMAGE_HEIGHT],
            [20, Background::POSITION_RIGHT, Background::POSITION_TOP, 20 + 100 - self::IMAGE_WIDTH, 2 * self::IMAGE_HEIGHT],
            [10, Background::POSITION_CENTER, Background::POSITION_TOP, 10 + 50 - self::IMAGE_WIDTH / 2, 2 * self::IMAGE_HEIGHT],
            [15, Background::POSITION_LEFT, Background::POSITION_BOTTOM, 15, self::IMAGE_HEIGHT],
            [13, Background::POSITION_LEFT, Background::POSITION_CENTER, 13, 2 * 3 / 4 * self::IMAGE_HEIGHT],
            [12, 40, 50, 12 + 40, self::IMAGE_HEIGHT * 2 - 50],
            [12, '40px', '50%', 12 + 40, self::IMAGE_HEIGHT * 2 - 50],
        ];
    }

    /**
     * @test
     * @dataProvider invalidPositionProvider
     *
     */
    public function throwExceptionOnInvalidBackgroundPosition($positionX, $positionY): void
    {
        $this->expectException(\PHPPdf\Exception\InvalidArgumentException::class);
        new Background(null, 'path', Background::REPEAT_NONE, null, false, null, null, $positionX, $positionY);
    }

    public function invalidPositionProvider(): array
    {
        return [
            ['a10', 10],
            [10, 'a10'],
        ];
    }

    /**
     * @test
     */
    public function drawCircleBackground(): void
    {
        $color       = '#ffffff';
        $radius      = 100;
        $centerPoint = Point::getInstance(100, 100);
        $background  = new Background('#ffffff');

        $this->assertDrawCircle($background, $color, $radius, $centerPoint, GraphicsContext::SHAPE_DRAW_FILL);
    }
}
