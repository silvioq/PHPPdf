<?php

declare(strict_types=1);

namespace PHPPdf\Test\Core\Engine\ZF;

use PHPPdf\Core\Engine\EmptyImage;

use PHPPdf\Core\Engine\ZF\Engine;

use PHPPdf\Core\Engine\ZF\GraphicsContext;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ZendPdf\Color\Html;
use ZendPdf\Page;
use ZendPdf\PdfDocument;
use PHPPdf\Core\Engine\ZF\Image;
use ZendPdf\Resource\Image\AbstractImage;
use ZendPdf\Resource\Font\AbstractFont;
use PHPPdf\Core\Engine\ZF\Font;
use ZendPdf\Action\Uri;
use ZendPdf\Annotation\Link;
use ZendPdf\Destination\FitHorizontally;
use ZendPdf\Exception\RuntimeException;
use ZendPdf\Action\GoToAction;
use ZendPdf\Annotation\Text;

class GraphicsContextTest extends \PHPPdf\PHPUnit\Framework\TestCase
{
    const ENCODING = 'utf-8';

    protected function setUp(): void
    {
        if (!class_exists(PdfDocument::class, true)) {
            $this->fail('Zend Framework 2 library is missing. You have to download dependencies, for example by using "vendors.php" file.');
        }
    }

    /**
     * @test
     */
    public function clipRectangleWrapper()
    {
        $zendPageMock = $this->getMockBuilder(Page::class)
                             ->onlyMethods(['clipRectangle'])
                             ->disableOriginalConstructor()
                             ->disableOriginalClone()
                             ->getMock();

        $x1 = 0;
        $x2 = 100;
        $y1 = 0;
        $y2 = 100;

        $zendPageMock->expects($this->once())
                     ->method('clipRectangle')
                     ->with($x1, $y1, $x2, $y2);

        $gc = $this->createGc($this->getEngineMock(), $zendPageMock);

        $gc->clipRectangle($x1, $y1, $x2, $y2);
        $gc->commit();
    }

    private function createGc($engine, $page): GraphicsContext
    {
        return new GraphicsContext($engine, $page, self::ENCODING);
    }

    private function getEngineMock(array $methods = []): MockObject|Engine
    {
        return $this->getMockBuilder(Engine::class)
                    ->onlyMethods($methods)
                    ->getMock();
    }

    /**
     * @test
     */
    public function saveAndRestoreGSWrapper(): void
    {
        $zendPageMock = $this->getMockBuilder(Page::class)
                             ->disableOriginalConstructor()
                             ->onlyMethods(['saveGS', 'restoreGS'])
                             ->getMock();

        $zendPageMock->expects($this->once())
                     ->id('1')
                     ->method('saveGS');
        $zendPageMock->expects($this->once())
                     ->after('1')
                     ->method('restoreGS');

        $gc = $this->createGc($this->getEngineMock(), $zendPageMock);

        $gc->saveGS();
        $gc->restoreGS();
        $gc->commit();
    }

    /**
     * @test
     */
    public function drawImageWrapper(): void
    {
        $x1 = 0;
        $x2 = 100;
        $y1 = 0;
        $y2 = 100;

        $zendPageMock = $this->getMockBuilder(Page::class)
                             ->onlyMethods(['drawImage'])
                             ->disableOriginalConstructor()
                             ->getMock();
        $zendImage    = $this->getMockBuilder(AbstractImage::class)
                             ->disableOriginalClone()
                             ->getMock();

        $image = $this->getMockBuilder(Image::class)
                      ->onlyMethods(['getWrappedImage'])
                      ->disableOriginalConstructor()
                      ->getMock();
        $image->expects($this->once())
              ->method('getWrappedImage')
              ->willReturn($zendImage);

        $zendPageMock->expects($this->once())
                     ->method('drawImage')
                     ->with($zendImage, $x1, $y1, $x2, $y2);

        $gc = $this->createGc($this->getEngineMock(), $zendPageMock);

        $gc->drawImage($image, $x1, $y1, $x2, $y2);
        $gc->commit();
    }

    /**
     * @test
     */
    public function drawLineWrapper(): void
    {
        $x1 = 0;
        $x2 = 100;
        $y1 = 0;
        $y2 = 100;

        $zendPageMock = $this->getMockBuilder(Page::class)
                             ->disableOriginalConstructor()
                             ->onlyMethods(['drawLine'])
                             ->getMock();

        $zendPageMock->expects($this->once())
                     ->method('drawLine')
                     ->with($x1, $y1, $x2, $y2);

        $gc = $this->createGc($this->getEngineMock(), $zendPageMock);

        $gc->drawLine($x1, $y1, $x2, $y2);
        $gc->commit();
    }

    /**
     * @test
     */
    public function setFontWrapper(): void
    {
        $zendPageMock = $this->getMockBuilder(Page::class)
                             ->onlyMethods(['setFont'])
                             ->disableOriginalConstructor()
                             ->getMock();
        $zendFontMock = $this->getMockBuilder(AbstractFont::class)
                             ->disableOriginalClone()
                             ->getMock();

        $fontMock = $this->getMockBuilder(Font::class)
                         ->onlyMethods(['getCurrentWrappedFont'])
                         ->disableOriginalConstructor()
                         ->getMock();

        $fontMock->expects($this->once())
                 ->method('getCurrentWrappedFont')
                 ->willReturn($zendFontMock);
        $size = 12;

        $zendPageMock->expects($this->once())
                     ->method('setFont')
                     ->with($zendFontMock, $size);

        $gc = $this->createGc($this->getEngineMock(), $zendPageMock);

        $gc->setFont($fontMock, $size);
        $gc->commit();
    }

    /**
     * @test
     * @dataProvider colorSetters
     */
    public function setColorsWrapper($method): void
    {
        $zendPageMock = $this->getMockBuilder(Page::class)
                             ->onlyMethods([$method])
                             ->disableOriginalConstructor()
                             ->getMock();

        $color     = '#123456';
        $zendColor = Html::color($color);

        $zendPageMock->expects($this->once())
                     ->method($method)
                     ->with($zendColor);

        $gc = $this->createGc($this->getEngineMock(), $zendPageMock);

        $gc->$method($color);

        //don't delegate if not necessary
        $gc->$method($color);
        $gc->commit();
    }

    public function colorSetters(): array
    {
        return [
            ['setFillColor'],
            ['setLineColor'],
        ];
    }

    /**
     * @test
     */
    public function drawPolygonWrapper(): void
    {
        $x        = [0, 100, 50];
        $y        = [0, 100, 50];
        $drawType = 1;

        $zendPageMock = $this->getMockBuilder(Page::class)
                             ->disableOriginalConstructor()
                             ->onlyMethods(['drawPolygon'])
                             ->getMock();

        $zendPageMock->expects($this->once())
                     ->method('drawPolygon')
                     ->with($x, $y, $drawType);

        $gc = $this->createGc($this->getEngineMock(), $zendPageMock);

        $gc->drawPolygon($x, $y, $drawType);
        $gc->commit();
    }

    /**
     * @test
     */
    public function drawTextWrapper(): void
    {
        $x        = 10;
        $y        = 200;
        $text     = 'some text';
        $encoding = 'utf-8';

        $zendPageMock = $this->getMockBuilder(Page::class)
                             ->disableOriginalConstructor()
                             ->onlyMethods(['drawText'])
                             ->getMock();

        $zendPageMock->expects($this->once())
                     ->method('drawText')
                     ->with($text, $x, $y, $encoding);

        $gc = $this->createGc($this->getEngineMock(), $zendPageMock);

        $gc->drawText($text, $x, $y, $encoding);
        $gc->commit();
    }

    /**
     * @test
     */
    public function drawRoundedRectangleWrapper(): void
    {
        $x1       = 10;
        $y1       = 100;
        $x2       = 100;
        $y2       = 50;
        $radius   = 0.5;
        $fillType = 1;

        $zendPageMock =
            $this->getMockBuilder(Page::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['drawRoundedRectangle'])
                 ->getMock();
        $zendPageMock->expects($this->once())
                     ->method('drawRoundedRectangle')
                     ->with($x1, $y1, $x2, $y2, $radius, $fillType);

        $gc = $this->createGc($this->getEngineMock(), $zendPageMock);

        $gc->drawRoundedRectangle($x1, $y1, $x2, $y2, $radius, $fillType);
        $gc->commit();
    }

    /**
     * @test
     */
    public function setLineWidthWrapper(): void
    {
        $width = 2.1;

        $zendPageMock =
            $this->getMockBuilder(Page::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['setLineWidth'])
                 ->getMock();
        $zendPageMock->expects($this->once())
                     ->method('setLineWidth')
                     ->with($width);

        $gc = $this->createGc($this->getEngineMock(), $zendPageMock);

        $gc->setLineWidth($width);

        //don't delegate if not necessary
        $gc->setLineWidth($width);
        $gc->commit();
    }

    /**
     * @test
     * @dataProvider lineDashingPatternProvider
     */
    public function setLineDashingPatternWrapper($pattern, $expected): void
    {
        $zendPageMock =
            $this->getMockBuilder(Page::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['setLineDashingPattern'])
                 ->getMock();

        $zendPageMock->expects($this->once())
                     ->method('setLineDashingPattern')
                     ->with($expected);

        $gc = $this->createGc($this->getEngineMock(), $zendPageMock);

        $gc->setLineDashingPattern($pattern);

        //don't delegate if not necessary
        $gc->setLineDashingPattern($pattern);
        $gc->commit();
    }

    public function lineDashingPatternProvider(): array
    {
        return [
            [[0], [0]],
            [GraphicsContext::DASHING_PATTERN_SOLID, 0],
            [GraphicsContext::DASHING_PATTERN_DOTTED, [1, 2]],
        ];
    }

    /**
     * @test
     */
    public function cachingGraphicsState(): void
    {
        $color1 = '#123456';
        $color2 = '#654321';

        $zendPageMock =
            $this->getMockBuilder(Page::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['setLineDashingPattern', 'setLineWidth', 'setFillColor', 'setLineColor', 'saveGS', 'restoreGS'])
                 ->getMock();


        $zendPageMock->expects($this->once())
                     ->method('saveGS');
        $zendPageMock->expects($this->exactly(3))
                     ->method('setLineDashingPattern');
        $zendPageMock->expects($this->exactly(3))
                     ->method('setLineWidth');
        $zendPageMock->expects($this->exactly(3))
                     ->method('setFillColor');
        $zendPageMock->expects($this->exactly(3))
                     ->method('setLineColor');
        $zendPageMock->expects($this->once())
                     ->method('restoreGS');


        $gc = $this->createGc($this->getEngineMock(), $zendPageMock);

        $gc->saveGS();
        //second loop pass do not change internal gc state
        for ($i = 0; $i < 2; $i++) {
            $gc->setLineDashingPattern([1, 1]);
            $gc->setLineWidth(1);
            $gc->setFillColor($color1);
            $gc->setLineColor($color1);
        }

        $gc->restoreGS();

        //second loop pass do not change internal gc state
        for ($i = 0; $i < 2; $i++) {
            $gc->setLineDashingPattern([1, 1]);
            $gc->setLineWidth(1);
            $gc->setFillColor($color1);
            $gc->setLineColor($color1);
        }

        //overriding by new values
        $gc->setLineDashingPattern([1, 2]);
        $gc->setLineWidth(2);
        $gc->setFillColor($color2);
        $gc->setLineColor($color2);

        $gc->commit();
    }

    private function createColorMock($zendColor, array $components = null)
    {
        $color = $this->getMockBuilder('PHPPdf\Core\Engine\ZF\Color')
                      ->onlyMethods(['getWrappedColor', 'getComponents'])
                      ->disableOriginalConstructor()
                      ->getMock();

        $color
            ->method('getWrappedColor')
            ->willReturn($zendColor);

        if ($components !== null) {
            $color
                ->method('getComponents')
                ->willReturn($components);
        }

        return $color;
    }

    /**
     * @test
     */
    public function attachUriAction(): void
    {
        $uri    = 'http://google.com';
        $coords = [0, 100, 200, 50];

        $zendPageMock = $this->getMockBuilder(Page::class)
                             ->onlyMethods(['attachAnnotation'])
                             ->disableOriginalConstructor()
                             ->getMock();

        $zendPageMock->expects($this->once())
                     ->method('attachAnnotation')
                     ->with($this->validateByCallback(function ($actual, TestCase $testCase) use ($uri, $coords) {
                         $testCase->assertAnnotationLinkWithRectangle($coords, $actual);

                         $action = $actual->getDestination();
                         $testCase->assertInstanceOf(Uri::class, $action);
                         $testCase->assertEquals($uri, $action->getUri());
                     }, $this));

        $gc = $this->createGc($this->getEngineMock(), $zendPageMock);

        $gc->uriAction($coords[0], $coords[1], $coords[2], $coords[3], $uri);
        $gc->commit();
    }

    public function assertAnnotationLinkWithRectangle(array $coords, $actual): void
    {
        $this->assertInstanceOf(Link::class, $actual);

        $boundary = $actual->getResource()->Rect;

        foreach ($coords as $i => $coord) {
            $this->assertEquals((string) $coord, $boundary->items[$i]->toString());
        }
    }

    /**
     * @test
     */
    public function attachGoToAction(): void
    {
        $zendPageMock = $this->getMockBuilder(Page::class)
                             ->onlyMethods(['attachAnnotation'])
                             ->disableOriginalConstructor()
                             ->getMock();

        $coords = [0, 100, 200, 50];
        $top    = 100;

        $pageStub = new Page('a4');
        $gcStub   = $this->createGc($this->getEngineMock(), $pageStub);

        $zendPageMock->expects($this->once())
                     ->method('attachAnnotation')
                     ->with($this->validateByCallback(function ($actual, TestCase $testCase) use ($top, $coords, $pageStub) {
                         $testCase->assertAnnotationLinkWithRectangle($coords, $actual);

                         $destination = $actual->getDestination();
                         $testCase->assertZendPageDestination($top, $pageStub, $destination);

                     }, $this));

        $gc = $this->createGc($this->getEngineMock(), $zendPageMock);

        $gc->goToAction($gcStub, $coords[0], $coords[1], $coords[2], $coords[3], $top);
        $gc->commit();
    }

    public function assertZendPageDestination($expectedTop, $expectedPage, $actualDestination): void
    {
        $this->assertInstanceOf(FitHorizontally::class, $actualDestination);

        $this->assertEquals($expectedTop, $actualDestination->getTopEdge());
        $this->assertSame($actualDestination->getResource()->items[0], $expectedPage->getPageDictionary());
    }

    /**
     * @test
     *
     * @dataProvider wrapZendExceptionsFromActionsProvider
     */
    public function wrapZendExceptionsFromActions($method, array $args): void
    {
        $this->expectException(\PHPPdf\Exception\RuntimeException::class);
        $zendPageMock = $this->getMockBuilder(Page::class)
                             ->onlyMethods(['attachAnnotation'])
                             ->disableOriginalConstructor()
                             ->getMock();

        $zendPageMock
            ->method('attachAnnotation')
            ->will($this->throwException($this->createMock(RuntimeException::class)));

        $gc = $this->createGc($this->getEngineMock(), $zendPageMock);

        call_user_func_array([$gc, $method], $args);
        $gc->commit();
    }

    public function wrapZendExceptionsFromActionsProvider(): array
    {
        return [
            [
                'goToAction', [$this->createGc($this->getEngineMock(), new Page('a4')), 0, 0, 0, 0, 10],
            ],
            [
                'uriAction', [0, 0, 0, 0, 'invalid-uri'],
            ],
        ];
    }

    /**
     * @test
     */
    public function attachSingleBookmark(): void
    {
        $pageStub   = new Page('a4');
        $identifier = 'some id';

        $top          = 100;
        $bookmarkName = 'some name';

        $engine = new Engine();
        $gc     = $this->createGc($engine, $pageStub);

        $gc->addBookmark($identifier, $bookmarkName, $top);
        $gc->commit();

        $zendPdf = $engine->getZendPdf();

        $this->assertCount(1, $zendPdf->outlines);

        $outline = $zendPdf->outlines[0];

        $this->assertOutline($bookmarkName, $pageStub, $top, $outline);
    }

    private function assertOutline($expectedName, $expectedPage, $expectedTop, $actualOutline): void
    {
        $this->assertEquals(iconv(self::ENCODING, 'UTF-16', $expectedName), $actualOutline->getTitle());

        $target = $actualOutline->getTarget();

        $this->assertInstanceOf(GoToAction::class, $target);
        $destination = $target->getDestination();

        $this->assertZendPageDestination($expectedTop, $expectedPage, $destination);
    }

    /**
     * @test
     */
    public function attachNestedBookmarks(): void
    {
        $pageStub = new Page('a4');

        $engine = new Engine();
        $gc     = $this->createGc($engine, $pageStub);

        //child bookmark can be added before parent
        $gc->addBookmark(2, '2', 10, 1);
        $gc->addBookmark(1, '1', 0, null);
        $gc->addBookmark(3, '3', 0, null);
        $gc->commit();

        $zendPdf = $engine->getZendPdf();

        $this->assertCount(2, $zendPdf->outlines);

        [$firstOutline , $secondOutline] = $zendPdf->outlines;

        $this->assertCount(1, $firstOutline->childOutlines);
        $this->assertCount(0, $secondOutline->childOutlines);

        $childOutline = $firstOutline->childOutlines[0];
        $this->assertOutline('2', $pageStub, 10, $childOutline);
    }

    /**
     * @test
     */
    public function attachStickyNote(): void
    {
        $zendPageMock = $this->getMockBuilder(Page::class)
                             ->onlyMethods(['attachAnnotation'])
                             ->disableOriginalConstructor()
                             ->getMock();
        $gc           = $this->createGc(new Engine(), $zendPageMock);

        $coords = [1, 2, 3, 4];
        $text   = 'text';

        $zendPageMock->expects($this->once())
                     ->method('attachAnnotation')
                     ->with($this->validateByCallback(function ($actual, TestCase $testCase) use ($text, $coords) {
                         $testCase->assertInstanceOf(Text::class, $actual);
                         $rect = $actual->getResource()->Rect;

                         foreach ($coords as $i => $coord) {
                             $testCase->assertEquals($coord, $rect->items[$i]->toPhp());
                         }
                         $actualText = $actual->getResource()->Contents->toString();
                         $testCase->assertEquals($text, $actual->getResource()->Contents->toPhp());
                     }, $this));

        $gc->attachStickyNote($coords[0], $coords[1], $coords[2], $coords[3], $text);
        $gc->commit();
    }

    /**
     * @test
     * @dataProvider alphaProvider
     */
    public function setAlpha($alpha, $expectCall): void
    {
        $zendPageMock = $this->getMockBuilder(Page::class)
                             ->onlyMethods(['setAlpha'])
                             ->disableOriginalConstructor()
                             ->getMock();

        $gc = $this->createGc(new Engine(), $zendPageMock);

        if ($expectCall) {
            $zendPageMock->expects($this->once())
                         ->method('setAlpha')
                         ->with($alpha);
        } else {
            $zendPageMock->expects($this->never())
                         ->method('setAlpha');
        }

        $gc->setAlpha($alpha);
        $gc->setAlpha($alpha);
        $gc->commit();
    }

    public function alphaProvider(): array
    {
        return [
            [0.5, true],
            [1, false],
        ];
    }

    public function throwExceptionIdParentOfBookmarkDosntExist(): void
    {
        $this->expectException(\PHPPdf\Exception\RuntimeException::class);
        $gc = $this->createGc(new Engine(), new Page('a4'));

        $gc->addBookmark('someId', 'some name', 100, 'unexistedParentId');
    }

    /**
     * @test
     */
    public function ignoreEmptyImage(): void
    {
        $zendPageMock = $this->getMockBuilder(Page::class)
                             ->onlyMethods(['drawImage'])
                             ->disableOriginalConstructor()
                             ->disableOriginalClone()
                             ->getMock();

        $image = EmptyImage::getInstance();

        $zendPageMock->expects($this->never())
                     ->method('drawImage');

        $gc = $this->createGc(new Engine(), $zendPageMock);

        $gc->drawImage($image, 50, 50, 100, 10);
        $gc->commit();
    }
}
