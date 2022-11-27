<?php

declare(strict_types=1);


namespace PHPPdf\Test\Core\Engine\Imagine\GraphicsContextTest;


use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use Imagine\Image\ImagineInterface;
use Imagine\Image\Palette\RGB;
use Imagine\Image\Point;
use PHPPdf\Core\Engine\Imagine\GraphicsContext;
use PHPPdf\PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Assert;

abstract class AbstractGraphicsContextTest extends TestCase
{
    public const GC_COLOR = '#ffffff';

    public const GC_WIDTH  = 300;
    public const GC_HEIGHT = 400;

    protected GraphicsContext $gc;

    protected ImagineInterface $imagine;

    protected ImageInterface $gcImage;

    protected function setUp(): void
    {
        $this->imagine = new Imagine();
        $this->gcImage = $this->imagine->create(new Box(self::GC_WIDTH, self::GC_HEIGHT), (new RGB())->color(self::GC_COLOR, 100));

        $this->gc = new GraphicsContext($this->imagine, $this->gcImage);
    }


    protected function assertImage(ImageInterface $image): AbstractGraphicsContextTest_ImageAssert
    {
        return new AbstractGraphicsContextTest_ImageAssert($image);
    }

    protected function assertDrewRectInLeftUpperCorner($width, $height, $color): void
    {
        $this->assertImage($this->gcImage)
             ->colorAt(1, 1, $color)
             ->colorAt($width - 2, $height - 2, $color)
             ->colorAt($width + 6, $height - 2, self::GC_COLOR)
             ->colorAt($width - 2, $height + 6, self::GC_COLOR);
    }
}

class AbstractGraphicsContextTest_ImageAssert
{
    private ImageInterface $image;

    public function __construct(ImageInterface $image)
    {
        $this->image = $image;
    }

    public function colorAt($x, $y, $expectedColor): static
    {
        $actualColor = (string) $this->image->getColorAt(new Point((int)$x, (int)$y));

        $actualRgb   = $this->rgb($actualColor);
        $expectedRgb = $this->rgb($expectedColor);

        for ($i = 0; $i < 3; $i++) {
            Assert::assertLessThanOrEqual(
                255 * 0.03,//3% precision
                abs($actualRgb[$i] - $expectedRgb[$i]),
                'expected color: '.$expectedColor.', but given: '.$actualColor
            );
        }

        return $this;
    }

    private function rgb($color): array
    {
        $color = str_replace('#', '', $color);

        return [
            hexdec($color[0].$color[1]),
            hexdec($color[2].$color[3]),
            hexdec($color[4].$color[5]),
        ];
    }
}
