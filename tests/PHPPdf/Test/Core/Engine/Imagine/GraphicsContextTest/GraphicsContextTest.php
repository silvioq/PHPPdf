<?php

declare(strict_types=1);

namespace PHPPdf\Test\Core\Engine\Imagine\GraphicsContextTest;

use Imagine\Draw\DrawerInterface;
use Imagine\Image\ImageInterface;
use Imagine\Image\ImagineInterface;
use Imagine\Image\Palette\RGB;
use PHPPdf\Core\Engine\Imagine\Font;
use PHPPdf\Core\Engine\EmptyImage;
use PHPPdf\Bridge\Imagine\Image\Point;
use Imagine\Image\Box;
use PHPPdf\Core\Engine\Imagine\GraphicsContext;
use PHPPdf\PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Imagine\Image\AbstractFont;

class GraphicsContextTest extends TestCase
{
    private MockObject|ImageInterface   $image;
    private ImagineInterface|MockObject $imagine;
    private MockObject|DrawerInterface  $drawer;
    private GraphicsContext             $gc;

    public function setUp(): void
    {
        $this->drawer  = $this->createMock(DrawerInterface::class);
        $this->image   = $this->createMock(ImageInterface::class);
        $this->imagine = $this->createMock(ImagineInterface::class);
        $this->gc      = new GraphicsContext($this->imagine, $this->image);
    }

    /**
     * @test
     */
    public function gsState(): void
    {
        $this->gc->setFillColor('#111111');
        $this->gc->saveGS();
        $this->gc->commit();

        $state = $this->readCurrentGsState();
        $this->assertEquals('#111111', $state['fillColor']);

        $this->gc->setFillColor('#222222');
        $this->gc->saveGS();
        $this->gc->commit();

        $state = $this->readCurrentGsState();
        $this->assertEquals('#222222', $state['fillColor']);

        $this->gc->restoreGS();
        $this->gc->commit();

        $state = $this->readCurrentGsState();
        $this->assertEquals('#222222', $state['fillColor']);

        $this->gc->restoreGS();
        $this->gc->commit();

        $state = $this->readCurrentGsState();
        $this->assertEquals('#111111', $state['fillColor']);

        $this->gc->setFillColor('#333333');
        $this->gc->commit();

        $state = $this->readCurrentGsState();
        $this->assertEquals('#333333', $state['fillColor']);

        $this->gc->restoreGS();
        $this->gc->commit();

        $state = $this->readCurrentGsState();
        $this->assertEquals(null, $state['fillColor']);
    }

    private function readCurrentGsState()
    {
        return $this->getAttribute($this->gc, 'state');
    }

    /**
     * @test
     */
    public function setAttributes()
    {
        $font = $this->createMock(\PHPPdf\Core\Engine\Font::class);

        $attributes = [
            'fillColor'          => '#222222',
            'lineColor'          => '#333333',
            'lineWidth'          => 3,
            'lineDashingPattern' => [[1, 2, 0]],
            'alpha'              => 1,
            'font'               => [$font, 13],
        ];

        $expected                       = $attributes;
        $expected['lineDashingPattern'] = $expected['lineDashingPattern'][0];
        $expected['fontSize']           = $expected['font'][1];
        $expected['font']               = $expected['font'][0];
        $expected['fontStyle']          = null;
        $expected['clips']              = [];

        foreach ($attributes as $name => $value) {
            call_user_func_array([$this->gc, 'set'.$name], (array) $value);
        }

        $this->gc->commit();

        $actual = $this->readCurrentGsState();

        ksort($expected);
        ksort($actual);

        $this->assertEquals($expected, $this->readCurrentGsState());
    }

    /**
     * @test
     */
    public function drawLine(): void
    {
        $x1    = 0;
        $y1    = 500;
        $x2    = 100;
        $y2    = 100;
        $color = '#000000';

        $this->gc->setLineColor('#000000');

        $width  = 500;
        $height = 500;

        $this->setExpectedImageSize($width, $height);

        $this->image->expects($this->atLeastOnce())
                    ->method('draw')
                    ->willReturn($this->drawer);

        $this->drawer->expects($this->once())
                     ->method('line')
                     ->with(new Point($x1, $height - $y1), new Point($x2, $height - $y2), (new RGB())->color($color, 0))
                     ->willReturn($this->drawer);

        $this->gc->drawLine($x1, $y1, $x2, $y2);
        $this->gc->commit();
    }

    private function setExpectedImageSize($width, $height, $image = null): void
    {
        $image = $image ?: $this->image;
        $box   = new Box($width, $height);
        $image->expects($this->any())
              ->method('getSize')
              ->willReturn($box);
    }

    /**
     * @test
     * @dataProvider drawPolygonProvider
     */
    public function drawPolygon(array $x, array $y, $fillType): void
    {
        $width             = $height = 500;
        $expectedFillColor = '#dddddd';
        $expectedLineColor = '#cccccc';

        $this->image->expects($this->atLeastOnce())
                    ->method('draw')
                    ->willReturn($this->drawer);
        $this->image
            ->method('palette')
            ->willReturn(new RGB());

        $this->setExpectedImageSize($width, $height);

        $expectedCoords = [];

        foreach ($y as $i => $coord) {
            $expectedCoords[] = new Point($x[$i], $height - $coord);
        }

        $expectedFill     = $fillType == GraphicsContext::SHAPE_DRAW_FILL;
        $expectedPolygons = [];

        if ($fillType > 0) {
            $expectedPolygons[] = [$expectedFillColor, true];
        }

        if ($fillType == 0 || $fillType == 2) {
            $expectedPolygons[] = [$expectedLineColor, false];
        }

        $callArguments   = [];
        $returnArguments = [];

        foreach ($expectedPolygons as $polygon) {
            [$expectedColor, $expectedFill] = $polygon;
            $callArguments[]   = [
                $expectedCoords, (new RGB())->color($expectedColor, 100), $expectedFill,
            ];
            $returnArguments[] = $this->returnValue($this->drawer);
        }

        $this->drawer->expects($this->exactly(\count($expectedPolygons)))
                     ->method('polygon')
                     ->withConsecutive(...$callArguments)
                     ->willReturnOnConsecutiveCalls(...$returnArguments);


        $this->gc->setFillColor($expectedFillColor);
        $this->gc->setLineColor($expectedLineColor);
        $this->gc->drawPolygon($x, $y, $fillType);

        $this->gc->commit();
    }

    public function drawPolygonProvider(): array
    {
        return [
            [
                [0, 50, 50, 0],
                [300, 300, 100, 100],
                GraphicsContext::SHAPE_DRAW_FILL,
            ],
            [
                [0, 50, 50, 0],
                [300, 300, 100, 100],
                GraphicsContext::SHAPE_DRAW_STROKE,
            ],
            [
                [0, 50, 50, 0],
                [300, 300, 100, 100],
                GraphicsContext::SHAPE_DRAW_FILL_AND_STROKE,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider alphaProvider
     */
    public function drawText($alpha, $expectedImagineAlpha): void
    {
        $text     = 'some text';
        $fontSize = 12;
        $color    = '#000000';

        $x = 0;
        $y = 100;

        $width = $height = 200;

        $font = $this->getMockBuilder(Font::class)
                     ->onlyMethods(['getWrappedFont'])
                     ->disableOriginalConstructor()
                     ->getMock();

        $imagineFont = $this->getMockBuilder(AbstractFont::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $font->expects($this->once())
             ->method('getWrappedFont')
             ->with((new RGB())->color($color, $expectedImagineAlpha), $fontSize)
             ->willReturn($imagineFont);

        $this->image->expects($this->once())
                    ->method('draw')
                    ->willReturn($this->drawer);

        $box = new Box($width, $height);
        $this->image->expects($this->any())
                    ->method('getSize')
                    ->willReturn($box);

        $expectedPosition = new Point($x, $height - $y - $fontSize);

        $this->drawer->expects($this->once())
                     ->method('text')
                     ->with($text, $imagineFont, $expectedPosition);

        $this->gc->setAlpha($alpha);
        $this->gc->setFont($font, $fontSize);
        $this->gc->setFillColor($color);
        $this->gc->drawText($text, $x, $y, 'utf-8');
        $this->gc->commit();
    }

    public function alphaProvider(): array
    {
        return [
            [1, 0],
            [0.2, 80],
        ];
    }

    /**
     * @test
     */
    public function setFont(): void
    {
        $normalFont = $this->getMockBuilder(AbstractFont::class)
                           ->disableOriginalConstructor()
                           ->getMock();

        $boldFont = clone $normalFont;

        $this->imagine->expects($this->exactly(2))
                      ->method('font')
                      ->withConsecutive(['normal', $this->anything(), $this->anything()], ['bold', $this->anything(), $this->anything()])
                      ->willReturnOnConsecutiveCalls($this->returnValue($normalFont), $this->returnValue($boldFont));

        $font = new Font([
                             Font::STYLE_NORMAL => 'normal',
                             Font::STYLE_BOLD   => 'bold',
                         ], $this->imagine);


        $this->drawer->expects($this->exactly(2))
                     ->method('text')
                     ->withConsecutive([$this->anything(), $normalFont, $this->anything()], [$this->anything(), $boldFont, $this->anything()]);

        $this->image
            ->method('getSize')
            ->willReturn(new Box(500, 500));
        $this->image
            ->method('draw')
            ->willReturn($this->drawer);

        $font->setStyle(Font::STYLE_NORMAL);
        $this->gc->setFillColor('#000000');
        $this->gc->setFont($font, 12);
        $this->gc->drawText('abc', 100, 100, 'utf-8');
        $font->setStyle(Font::STYLE_BOLD);
        $this->gc->setFont($font, 12);
        $this->gc->drawText('abc', 100, 100, 'utf-8');

        $this->gc->commit();
    }

    /**
     * @test
     */
    public function clipRectangle(): void
    {
        $width  = 100;
        $height = 200;

        $x1 = 25;
        $y1 = 175;
        $x2 = 75;
        $y2 = 25;

        $rectangleWidth  = $x2 - $x1;
        $rectangleHeight = $y1 - $y2;

        $this->setExpectedImageSize($width, $height);

        $rectangleImage = $this->createMock(ImageInterface::class);
        $this->setExpectedImageSize($rectangleWidth, $rectangleHeight, $rectangleImage);
        $this->imagine->expects($this->once())
                      ->method('create')
                      ->with(new Box($rectangleWidth, $rectangleHeight))
                      ->willReturn($rectangleImage);

        $this->gc->saveGS();
        $this->gc->clipRectangle($x1, $y1, $x2, $y2);
        $this->gc->commit();

        $this->image->expects($this->once())
                    ->method('paste')
                    ->with($rectangleImage, new Point($x1, $height - $y1));

        $this->gc->restoreGS();
        $this->gc->commit();
    }

    /**
     * @test
     */
    public function ignoreEmptyImage(): void
    {
        $image = EmptyImage::getInstance();

        $this->image->expects($this->never())
                    ->method('paste');

        $this->gc->drawImage($image, 50, 50, 100, 10);
        $this->gc->commit();
    }
}
