<?php

declare(strict_types=1);

namespace PHPPdf\Test\Core\Engine\Imagine;

use Imagine\Image\ImagineInterface;
use PHPPdf\PHPUnit\Framework\TestCase;
use PHPPdf\Core\Engine\Imagine\Font;
use PHPUnit\Framework\MockObject\MockObject;
use Imagine\Image\BoxInterface;
use Imagine\Image\FontInterface;

class FontTest extends TestCase
{
    private Font                        $font;
    private ImagineInterface|MockObject $imagine;
    private array                       $fontResources;

    public function setUp(): void
    {
        if (!interface_exists(ImagineInterface::class, true)) {
            $this->fail('Imagine library is missing. You have to download dependencies, for example by using "vendors.php" file.');
        }

        $this->imagine       = $this->createMock(ImagineInterface::class);
        $this->fontResources = [
            Font::STYLE_NORMAL      => TEST_RESOURCES_DIR.'/font-judson/normal.ttf',
            Font::STYLE_BOLD        => TEST_RESOURCES_DIR.'/font-judson/bold.ttf',
            Font::STYLE_ITALIC      => TEST_RESOURCES_DIR.'/font-judson/italic.ttf',
            Font::STYLE_BOLD_ITALIC => TEST_RESOURCES_DIR.'/font-judson/bold+italic.ttf',
        ];

        $this->font = new Font($this->fontResources, $this->imagine);
    }

    /**
     * @test
     */
    public function getWidthOfText(): void
    {
        $text     = 'some text';
        $fontSize = 12;

        $expectedWidth = 111;

        $box = $this->createMock(BoxInterface::class);
        $box->expects($this->once())
            ->method('getWidth')
            ->willReturn($expectedWidth);

        $font = $this->createMock(FontInterface::class);
        $font->expects($this->once())
             ->method('box')
             ->with($text)
             ->willReturn($box);

        $this->imagine->expects($this->once())
                      ->method('font')
                      ->with($this->fontResources[Font::STYLE_NORMAL], $fontSize, $this->anything())
                      ->willReturn($font);

        $width = $this->font->getWidthOfText($text, $fontSize);

        $this->assertEquals($expectedWidth, $width);
    }

    /**
     * @test
     * @dataProvider styleProvider
     */
    public function styleSwitching($style): void
    {
        $color    = '#000000';
        $fontSize = 13;

        $this->font->setStyle($style);

        $expectedFont = $this->createMock(FontInterface::class);

        $this->imagine->expects($this->once())
                      ->method('font')
                      ->with($this->fontResources[$style], $fontSize, $this->anything())
                      ->willReturn($expectedFont);

        $actualFont = $this->font->getWrappedFont($color, $fontSize);

        $this->assertEquals($expectedFont, $actualFont);
    }

    public function styleProvider(): array
    {
        return [
            [Font::STYLE_NORMAL],
            [Font::STYLE_BOLD],
        ];
    }
}
