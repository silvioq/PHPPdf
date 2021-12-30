<?php

declare(strict_types=1);

namespace PHPPdf\Test\Core\Engine\ZF;

use PHPPdf\Core\Engine\Font;

use PHPPdf\Core\Engine\ZF\Engine;
use PHPPdf\Exception\InvalidResourceException;
use PHPPdf\PHPUnit\Framework\TestCase;
use ZendPdf\PdfDocument;
use PHPPdf\Core\Engine\ZF\Image;
use PHPPdf\Core\Engine\ZF\GraphicsContext;
use PHPPdf\Core\UnitConverter;

class EngineTest extends TestCase
{
    private Engine      $engine;
    private PdfDocument $zendPdf;

    public function setUp(): void
    {
        if (!class_exists(PdfDocument::class, true)) {
            $this->fail('Zend Framework 2 library is missing. You have to download dependencies, for example by using "vendors.php" file.');
        }

        $this->zendPdf = new PdfDocument();
        $this->engine  = new Engine($this->zendPdf);
    }

    /**
     * @test
     */
    public function createImage(): void
    {
        $image = $this->engine->createImage(TEST_RESOURCES_DIR.'/domek.png');

        $this->assertInstanceOf(Image::class, $image);
    }

    /**
     * @test
     * @dataProvider fontProvider
     */
    public function createFont($fontData): void
    {
        $font = $this->engine->createFont($fontData);

        $this->assertInstanceOf(\PHPPdf\Core\Engine\ZF\Font::class, $font);

        foreach ($fontData as $style => $data) {
            $this->assertTrue($font->hasStyle($style));
        }
    }

    public function fontProvider(): array
    {
        $resourcesDir = TEST_RESOURCES_DIR.'/resources';

        return [
            [
                [
                    Font::STYLE_NORMAL => $resourcesDir.'/font-judson/normal.ttf',
                    Font::STYLE_BOLD   => $resourcesDir.'/font-judson/bold.ttf',
                ],
            ],
            [
                [
                    Font::STYLE_NORMAL => 'courier',
                    Font::STYLE_BOLD   => 'courier-bold',
                ],
            ],
        ];
    }

    /**
     * @test
     */
    public function createGraphicsContext(): void
    {
        $size = '1:1';

        $gc = $this->engine->createGraphicsContext($size, 'utf-8');

        $this->assertInstanceOf(GraphicsContext::class, $gc);

        $this->assertEquals([], $this->zendPdf->pages);

        $this->engine->attachGraphicsContext($gc);

        $this->assertEquals([$gc->getPage()], $this->zendPdf->pages);
    }

    /**
     * @test
     */
    public function delegateRenderingToZendPdf(): void
    {
        $content = '123';

        $zendPdf = $this->getMockBuilder(PdfDocument::class)
                        ->onlyMethods(['render'])
                        ->getMock();

        $zendPdf->expects($this->once())
                ->method('render')
                ->willReturn($content);

        $engine = new Engine($zendPdf);

        $this->assertEquals($content, $engine->render());
    }

    /**
     * @test
     */
    public function successfullEngineLoading(): void
    {
        $file = TEST_RESOURCES_DIR.'/test.pdf';

        $engine = new Engine();

        $loadedEngine = $engine->loadEngine($file, 'utf-8');

        $this->assertNotSame($loadedEngine, $engine);
        $this->assertInstanceOf(Engine::class, $loadedEngine);
        $this->assertCount(2, $loadedEngine->getAttachedGraphicsContexts());
    }

    /**
     * @test
     *
     */
    public function throwExceptionIfFileIsInvalidWhileEngineLoading(): void
    {
        $this->expectException(InvalidResourceException::class);
        $file = 'some/invalid/filename.pdf';

        $engine = new Engine();

        $engine->loadEngine($file, 'utf-8');
    }

    /**
     * @test
     * @dataProvider metadataProvider
     */
    public function setMetadataValues($name, $value, $shouldBeSet, $expectedValue = null): void
    {
        $zendPdf = new PdfDocument();
        $engine  = new Engine($zendPdf);

        $engine->setMetadataValue($name, $value);

        if ($shouldBeSet) {
            $this->assertEquals($expectedValue, $zendPdf->properties[$name]);
        } else {
            $this->assertFalse(isset($zendPdf->properties[$name]));
        }
    }

    public function metadataProvider(): array
    {
        return [
            ['Trapped', 'true', true, true],
            ['Trapped', 'false', true, false],
            ['Trapped', true, true, true],
            ['Trapped', 'null', true, null],
            ['Author', 'Author', true, 'Author'],
            ['InvalidProperty', 'value', false],
        ];
    }

    /**
     * @test
     */
    public function delegateConvertUnitInvocationToConverter(): void
    {
        $converter = $this->createMock(UnitConverter::class);

        $engine = new Engine(null, $converter);

        $value      = 123;
        $unit       = 'abc';
        $percentage = '100%';
        $result     = 321;

        $converter->expects($this->once())
                  ->id('1')
                  ->method('convertUnit')
                  ->with($value, $unit)
                  ->willReturn($result);

        $converter->expects($this->once())
                  ->after('1')
                  ->method('convertPercentageValue')
                  ->with($percentage, $value)
                  ->willReturn($result);

        $this->assertEquals($result, $engine->convertUnit($value, $unit));
        $this->assertEquals($result, $engine->convertPercentageValue($percentage, $value));
    }
}
