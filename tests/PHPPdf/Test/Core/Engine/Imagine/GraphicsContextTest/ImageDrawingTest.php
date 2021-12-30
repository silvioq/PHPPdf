<?php

declare(strict_types=1);

namespace PHPPdf\Test\Core\Engine\Imagine\GraphicsContextTest;

use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use Imagine\Image\ImagineInterface;
use Imagine\Image\Palette\Color\ColorInterface;
use Imagine\Image\Palette\RGB;
use Imagine\Image\Point;
use PHPPdf\Core\Engine\Imagine\GraphicsContext;
use PHPPdf\Core\Engine\Imagine\Image;
use PHPPdf\PHPUnit\Framework\TestCase;

class ImageDrawingTest extends AbstractGraphicsContextTest
{
    private const WIDTH  = 50;
    private const HEIGHT = 100;

    /**
     * @test
     * @dataProvider scaleProvider
     */
    public function givenImage_drawItInLeftUpperCorner($scale): void
    {
        //given

        $color1 = '#000000';
        $color2 = '#222222';

        $image = $this->imageWithSizeAndTwoVerticalColors(self::WIDTH, self::HEIGHT, $color1, $color2)->get();

        $scaledWidth  = self::WIDTH * $scale;
        $scaledHeight = self::HEIGHT * $scale;

        //when

        $this->drawInLeftUpperCorner($image, $scaledWidth, $scaledHeight);

        //then

        $this->assertDrewRectWithTwoVerticalColorsInLeftUpperCorner($color1, $color2, $scaledWidth, $scaledHeight);
    }

    /**
     * @test
     * @dataProvider scaleProvider
     */
    public function givenImage_drawItInBottomRightCorner($scale): void
    {
        //given

        $color1 = '#000000';
        $color2 = '#222222';

        $image = $this->imageWithSizeAndTwoVerticalColors(self::WIDTH, self::HEIGHT, $color1, $color2)->get();

        $scaledWidth  = self::WIDTH * $scale;
        $scaledHeight = self::HEIGHT * $scale;

        //when

        $this->drawInRightBottomCorner($image, $scaledWidth, $scaledHeight);

        //then

        $this->assertDrewRectWithTwoVerticalColorsInRightBottomCorner($color1, $color2, $scaledWidth, $scaledHeight);
    }

    /**
     * @test
     * @dataProvider scaleProvider
     */
    public function givenImage_drawItPartiallyOutsideOfRightBottomCorner($scale): void
    {
        //given

        $color1 = '#000000';
        $color2 = '#222222';
        $color3 = '#333333';

        $image = $this->imageWithVerticallyColoredRectangleInLeftUpperQuater(
            self::WIDTH, self::HEIGHT, $color1, $color2, $color3
        )->get();

        $scaledWidth  = self::WIDTH * $scale;
        $scaledHeight = self::HEIGHT * $scale;

        //when

        $this->drawPartiallyOutsideOfRightBottomCorner($image, $scaledWidth, $scaledHeight);

        //then

        $this->assertDrewRectWithTwoVerticalColorsInRightBottomCorner($color2, $color3, $scaledWidth / 2, $scaledHeight / 2);
    }

    /**
     * @test
     * @dataProvider scaleProvider
     */
    public function givenImage_drawItPartiallyOutsideOfLeftUpperCorner($scale): void
    {
        //given

        $color1 = '#000000';
        $color2 = '#222222';
        $color3 = '#333333';

        $image = $this->imageWithVerticallyColoredRectangleInRightBottomQuater(
            self::WIDTH, self::HEIGHT, $color1, $color2, $color3
        )->get();

        $scaledWidth  = self::WIDTH * $scale;
        $scaledHeight = self::HEIGHT * $scale;

        //when

        $this->drawPartiallyOutsideOfLeftUpperCorner($image, $scaledWidth, $scaledHeight);

        //then

        $this->assertDrewRectWithTwoVerticalColorsInLeftUpperCorner($color2, $color3, $scaledWidth / 2, $scaledHeight / 2);
    }

    public function scaleProvider(): array
    {
        return [
            [1],
            [0.5],
            [2],
        ];
    }

    private function assertDrewRectWithTwoVerticalColorsInLeftUpperCorner($color1, $color2, $width = self::WIDTH, $height = self::HEIGHT): void
    {
        $this->assertImage($this->gcImage)
             ->colorAt(1, 1, $color1)
             ->colorAt($width - 2, $height - 2, $color2)
             ->colorAt($width - 2, $height / 2 + 3, $color2)
             ->colorAt($width - 2, $height / 2 - 3, $color1)
             ->colorAt(1, $height + 2, self::GC_COLOR)
             ->colorAt($width + 2, 1, self::GC_COLOR);
    }

    private function assertDrewRectWithTwoVerticalColorsInRightBottomCorner($color1, $color2, $width = self::WIDTH, $height = self::HEIGHT): void
    {
        $this->assertImage($this->gcImage)
             ->colorAt(1, 1, self::GC_COLOR)
             ->colorAt(self::GC_WIDTH - $width + 2, self::GC_HEIGHT - $height + 2, $color1)
             ->colorAt(self::GC_WIDTH - 2, self::GC_HEIGHT - 2, $color2)
             ->colorAt(self::GC_WIDTH - $width + 2, self::GC_HEIGHT - $height / 2 + 2, $color2)
             ->colorAt(self::GC_WIDTH - $height - 3, self::GC_HEIGHT - $height + 3, self::GC_COLOR)
             ->colorAt(self::GC_WIDTH - $height + 3, self::GC_HEIGHT - $height - 3, self::GC_COLOR);
    }

    private function imageWithSizeAndColor($width, $height, $color): ImageDrawingTest_ImageBuilder
    {
        return $this->image()
                    ->withSize($width, $height)
                    ->withColor($color);
    }

    private function image(): ImageDrawingTest_ImageBuilder
    {
        return new ImageDrawingTest_ImageBuilder($this->imagine);
    }

    private function imageWithSizeAndTwoVerticalColors($width, $height, $color1, $color2): ImageDrawingTest_ImageBuilder
    {
        return $this->image()
                    ->withSize($width, $height)
                    ->withColor($color1)
                    ->withColorRect($color2, 0, $height / 2, $width, $height);
    }

    private function imageWithVerticallyColoredRectangleInRightBottomQuater($width, $height, $color1, $color2, $color3): ImageDrawingTest_ImageBuilder
    {
        return $this->imageWithSizeAndColor($width, $height, $color1)
                    ->withColorRect($color2, $width / 2, $height / 2, $width / 2, $height / 4)
                    ->withColorRect($color3, $width / 2, $height / 4 * 3, $width / 2, $height / 4);
    }

    private function imageWithVerticallyColoredRectangleInLeftUpperQuater($width, $height, $color1, $color2, $color3): ImageDrawingTest_ImageBuilder
    {
        return $this->imageWithSizeAndColor($width, $height, $color1)
                    ->withColorRect($color2, 0, 0, $width / 2, $height / 4)
                    ->withColorRect($color3, 0, $height / 4, $width / 2, $height / 4);
    }

    private function drawInLeftUpperCorner($image, $width = self::WIDTH, $height = self::HEIGHT): void
    {
        $this->drawAt($image, 0, 0, $width, $height);
    }

    private function drawInRightBottomCorner($image, $width = self::WIDTH, $height = self::HEIGHT): void
    {
        $this->drawAt(
            $image,
            self::GC_WIDTH - $width,
            self::GC_HEIGHT - $height,
            $width,
            $height
        );
    }


    private function drawAt($image, $x, $y, $width, $height): void
    {
        //coordinate system of PHPPdf is different than Imagine,
        //in Imagine left upper corner has (0,0) coordinates
        //in PHPPdf left upper corner has (0,height) coordinates
        //in Imagine first point is left upper point
        //in PHPPdf first point is left bottom point

        $this->gc->drawImage(
            $image,
            $x,
            $this->gcImage->getSize()->getHeight() - $y - $height,
            $x + $width,
            $this->gcImage->getSize()->getHeight() - $y
        );

        $this->gc->commit();
    }

    private function drawPartiallyOutsideOfLeftUpperCorner($image, $width = self::WIDTH, $height = self::HEIGHT): void
    {
        $this->drawAt($image, -$width / 2, -$height / 2, $width, $height);
    }

    private function drawPartiallyOutsideOfRightBottomCorner($image, $width = self::WIDTH, $height = self::HEIGHT): void
    {
        $this->drawAt(
            $image,
            self::GC_WIDTH - $width / 2,
            self::GC_HEIGHT - $height / 2,
            $width,
            $height
        );
    }
}

class ImageDrawingTest_ImageBuilder
{
    private ?Box             $size       = null;
    private ?ColorInterface  $color      = null;
    private ImagineInterface $imagine;
    private array            $colorRects = [];

    public function __construct(ImagineInterface $imagine)
    {
        $this->imagine = $imagine;
    }


    public function withSize($width, $height): static
    {
        $this->size = new Box($width, $height);

        return $this;
    }

    public function withColor($color): static
    {
        $this->color = (new RGB())->color($color);

        return $this;
    }

    public function withColorRect($color, $x, $y, $width, $height): static
    {
        $this->colorRects[] = [(new RGB())->color($color), new Point($x, $y), new Box($width, $height)];

        return $this;
    }

    public function get(): Image
    {
        $image = $this->imagine->create($this->size, $this->color);

        foreach ($this->colorRects as $colorRect) {
            /**
             * @var Point $startPoint
             * @var Box   $box
             */
            [$color, $startPoint, $box] = $colorRect;

            $image->draw()->polygon([
                                        $startPoint,
                                        new Point($startPoint->getX(), $startPoint->getY() + $box->getHeight()),
                                        new Point($startPoint->getX() + $box->getWidth(), $startPoint->getY() + $box->getHeight()),
                                        new Point($startPoint->getX() + $box->getWidth(), $startPoint->getY()),
                                    ], $color, true);
        }

        return new Image($image, $this->imagine);
    }
}

