<?php

declare(strict_types=1);

namespace PHPPdf\Test\Core\Formatter;

use PHPPdf\Core\Formatter\ImageConvertAttributesFormatter;
use PHPPdf\Core\Document,
    PHPPdf\Core\Node\Image,
    PHPPdf\Core\Node\Page,
    PHPPdf\Core\Node\Container;
use PHPPdf\PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class ImageConvertAttributesFormatterTest extends TestCase
{
    private ImageConvertAttributesFormatter $formatter;
    private Document|MockObject             $document;

    public function setUp(): void
    {
        $this->formatter = new ImageConvertAttributesFormatter();
        $this->document  = $this->getMockBuilder(Document::class)
                                ->onlyMethods(['createImage'])
                                ->disableOriginalConstructor()
                                ->getMock();
    }

    /**
     * @test
     */
    public function drawingFromBeginingOfThePage(): void
    {
        $page = new Page();

        $imageHeight = 100;
        $imageWidth  = 50;

        $imageResource = $this->createImageResourceMock($imageWidth, $imageHeight);
        $imagePath     = 'some/path';

        $image = new Image([
                               'src' => $imagePath,
                           ]);
        $this->document->expects($this->atLeastOnce())
                       ->method('createImage')
                       ->with($imagePath)
                       ->willReturn($imageResource);

        $page->add($image);

        $this->formatter->format($image, $this->document);

        $this->assertEquals($imageWidth, $image->getWidth());
        $this->assertEquals($imageHeight, $image->getHeight());
    }

    private function createImageResourceMock($width, $height): MockObject|\PHPPdf\Core\Engine\Image
    {
        $imageResource = $this->getMockBuilder(\PHPPdf\Core\Engine\Image::class)
                              ->onlyMethods(['getOriginalHeight', 'getOriginalWidth'])
                              ->getMock();
        $imageResource->expects($this->atLeastOnce())
                      ->method('getOriginalHeight')
                      ->willReturn($height);
        $imageResource->expects($this->atLeastOnce())
                      ->method('getOriginalWidth')
                      ->willReturn($width);

        return $imageResource;
    }

    /**
     * @test
     */
    public function drawingInSmallerContainer(): void
    {
        $page = new Page();

        $height    = 100;
        $width     = 120;
        $imagePath = 'image/path';

        $imageResource = $this->createImageResourceMock($width, $height);

        $this->document->expects($this->atLeastOnce())
                       ->method('createImage')
                       ->with($imagePath)
                       ->willReturn($imageResource);

        $image = new Image([
                               'src' => $imagePath,
                           ]);

        $container = new Container([
                                       'width'  => (int) ($width * 0.7),
                                       'height' => (int) ($height * 0.5),
                                   ]);

        $container->add($image);

        $this->formatter->format($image, $this->document);

        $this->assertEquals($container->getHeight(), $image->getHeight());
        $this->assertTrue($container->getWidth() > $image->getWidth());
    }

    /**
     * @test
     * @dataProvider sizeProvider
     */
    public function calculateSecondSize($width, $height): void
    {
        $page = new Page();

        $imageWidth  = 100;
        $imageHeight = 120;
        $imagePath   = 'image/path';

        $imageResource = $this->createImageResourceMock($imageWidth, $imageHeight);

        $this->document->expects($this->atLeastOnce())
                       ->method('createImage')
                       ->with($imagePath)
                       ->willReturn($imageResource);


        $image = new Image([
                               'src'    => $imagePath,
                               'width'  => $width,
                               'height' => $height,
                           ]);
        $page->add($image);

        $this->formatter->format($image, $this->document);

        $ratio = $imageWidth / $imageHeight;

        if (!$height) {
            $ratio = 1 / $ratio;
        }

        $excepted = $ratio * ($width ?: $height);

        $this->assertEquals($excepted, $width ? $image->getHeight() : $image->getWidth());
    }

    public function sizeProvider(): array
    {
        return [
            [100, null],
            [null, 100],
        ];
    }
}
