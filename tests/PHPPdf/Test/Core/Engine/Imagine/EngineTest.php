<?php

declare(strict_types=1);

namespace PHPPdf\Test\Core\Engine\Imagine;

use Imagine\Exception\InvalidArgumentException;
use Imagine\Exception\RuntimeException;
use PHPPdf\Core\Engine\Imagine\Font;

use Imagine\Image\Box;
use PHPPdf\Core\Engine\Imagine\Engine;
use PHPPdf\Exception\InvalidResourceException;
use PHPPdf\PHPUnit\Framework\TestCase;
use Imagine\Image\ImagineInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Imagine\Image\ImageInterface;
use PHPPdf\Core\Engine\Imagine\GraphicsContext;
use PHPPdf\Core\Engine\Imagine\Image;
use Imagine\Image\FontInterface;

class EngineTest extends TestCase
{
    private Engine                      $engine;
    private ImagineInterface|MockObject $imagine;

    public function setUp(): void
    {
        $this->imagine = $this->createMock(ImagineInterface::class);
        $this->engine  = new Engine($this->imagine, 'png');
    }

    /**
     * @test
     */
    public function createGraphicsContext(): void
    {
        $image = $this->createMock(ImageInterface::class);

        $size = '100:200';

        [$width, $height] = explode(':', $size);

        $box = new Box($width, $height);

        $this->imagine->expects($this->once())
                      ->method('create')
                      ->with($box)
                      ->willReturn($image);

        $image->expects($this->atLeastOnce())
              ->method('getSize')
              ->willReturn($box);

        $gc = $this->engine->createGraphicsContext($size, 'utf-8');

        $this->assertInstanceOf(GraphicsContext::class, $gc);

        $this->assertEquals($width, $gc->getWidth());
        $this->assertEquals($height, $gc->getHeight());
    }

    /**
     * @test
     */
    public function createImage(): void
    {
        $imagineImage = $this->createMock(ImageInterface::class);

        $path = 'some/image/path';

        $this->imagine->expects($this->once())
                      ->method('open')
                      ->with($path)
                      ->willReturn($imagineImage);

        $image = $this->engine->createImage($path);

        $this->assertInstanceOf(Image::class, $image);
        $this->assertEquals($imagineImage, $image->getWrappedImage());
    }

    /**
     * @test
     *
     */
    public function wrapExceptionOnImageCreationFailure(): void
    {
        $this->expectException(InvalidResourceException::class);
        $path = 'path';

        $this->imagine->expects($this->once())
                      ->method('open')
                      ->with($path)
                      ->will($this->throwException(new InvalidArgumentException()));

        $this->engine->createImage($path);
    }

    /**
     * @test
     */
    public function createFont()
    {
        $fontData = [
            Font::STYLE_NORMAL => TEST_RESOURCES_DIR.'/font-judson/normal.ttf',
        ];

        $size        = 123;
        $imagineFont = $this->createMock(FontInterface::class);

        $this->imagine->expects($this->once())
                      ->method('font')
                      ->with($fontData[Font::STYLE_NORMAL], $size, $this->anything())
                      ->willReturn($imagineFont);

        $font = $this->engine->createFont($fontData);

        $this->assertInstanceOf(Font::class, $font);
        $this->assertEquals($imagineFont, $font->getWrappedFont('#000000', $size));
    }

    /**
     * @test
     */
    public function render(): void
    {
        $expectedContents = [
            'some content',
            '1234',
            'fasdfsdaf',
        ];

        $this->setGraphicsContextsWithRenderExceptation($expectedContents);

        $actualContents = $this->engine->render();

        $this->assertEquals($expectedContents, $actualContents);
    }

    private function setGraphicsContextsWithRenderExceptation(array $expectedContents): void
    {
        $gcs = [];

        foreach ($expectedContents as $content) {
            $gc = $this->getMockBuilder(GraphicsContext::class)
                       ->onlyMethods(['render', 'commit'])
                       ->disableOriginalConstructor()
                       ->getMock();

            $gc->expects($this->once())
               ->method('commit')
               ->id('1');
            $gc->expects($this->once())
               ->method('render')
               ->after('1')
               ->willReturn($content);

            $this->engine->attachGraphicsContext($gc);
        }
    }

    /**
     * @test
     */
    public function loadEngineSuccess(): void
    {
        $path = 'some.png';

        $image = $this->createMock(ImageInterface::class);

        $this->imagine->expects($this->once())
                      ->method('open')
                      ->with($path)
                      ->willReturn($image);

        $engine = $this->engine->loadEngine($path, 'utf-8');

        $this->assertCount(1, $engine->getAttachedGraphicsContexts());
    }

    /**
     * @test
     *
     */
    public function loadEngineFailure(): void
    {
        $this->expectException(InvalidResourceException::class);
        $path = 'some.png';

        $this->imagine->expects($this->once())
                      ->method('open')
                      ->with($path)
                      ->will($this->throwException(new RuntimeException()));

        $this->engine->loadEngine($path, 'utf-8');
    }
}
