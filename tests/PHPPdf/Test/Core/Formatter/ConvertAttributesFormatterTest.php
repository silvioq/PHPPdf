<?php

declare(strict_types=1);

namespace PHPPdf\Test\Core\Formatter;

use PHPPdf\Core\PdfUnitConverter;

use PHPPdf\Core\Document;
use PHPPdf\Core\Node\Page;
use PHPPdf\Core\Node\Container;
use PHPPdf\Core\Formatter\ConvertAttributesFormatter;
use PHPPdf\PHPUnit\Framework\TestCase;

class ConvertAttributesFormatterTest extends TestCase
{
    private ConvertAttributesFormatter $formatter;
    private Document                   $document;

    public function setUp(): void
    {
        $this->formatter = new ConvertAttributesFormatter();

        $this->document = $this->createDocumentStub();
    }

    /**
     * @test
     */
    public function percentageConvert(): void
    {
        $page          = new Page();
        $unitConverter = new PdfUnitConverter();
        $node          = new Container(['width' => 200, 'height' => 100], $unitConverter);
        $child         = new Container(['width' => '70%', 'max-width' => '50%', 'max-height' => '70%', 'height' => '50%'], $unitConverter);

        $node->add($child);
        $page->add($node);

        $node->setHeight(100);
        $node->setWidth(200);

        $this->formatter->format($child, $this->document);

        $this->assertEquals(200 * 0.7, $child->getWidth());
        $this->assertEquals(100 * 0.5, $child->getHeight());
        $this->assertEquals(200 * 0.5, $child->getMaxWidth());
        $this->assertEquals(100 * 0.7, $child->getMaxHeight());
    }

    /**
     * @test
     * @dataProvider autoMarginConvertProvider
     */
    public function autoMarginConvert($nodeWidth, $parentWidth, $expectedMarginLeft, $expectedMarginRight): void
    {
        $node = new Container(['width' => $nodeWidth]);
        $node->setWidth($nodeWidth);
        $node->setMargin(0, 'auto');

        $mock = $this->getMockBuilder(Page::class)
                     ->enableOriginalConstructor()
                     ->onlyMethods(['getWidth', 'setWidth'])
                     ->getMock();
        $mock->expects($this->atLeastOnce())
             ->method('getWidth')
             ->willReturn($parentWidth);

        if ($nodeWidth > $parentWidth) {
            $mock->expects($this->once())
                 ->method('setWidth')
                 ->with($nodeWidth);
        }

        $mock->add($node);

        $this->formatter->format($node, $this->document);

        $this->assertEquals($expectedMarginLeft, $node->getMarginLeft());
        $this->assertEquals($expectedMarginRight, $node->getMarginRight());
    }

    public function autoMarginConvertProvider(): array
    {
        return [
            [100, 200, 50, 50],
            [200, 100, 0, 0], // if child is wider than parent, margins should be set as "0" and parent width should be set as child width
        ];
    }

    /**
     * @test
     * @dataProvider angleProvider
     */
    public function convertRotateAngleFronDegreesToRadians($angle, $expectedRadians): void
    {
        $node = new Container();
        $node->setAttribute('rotate', $angle);

        $this->formatter->format($node, $this->document);

        if ($angle === null) {
            $this->assertNull($node->getAttribute('rotate'));
        } else {
            $this->assertEqualsWithDelta($expectedRadians, $node->getAttribute('rotate'), 0.001, 'conversion from degrees to radians failure');
        }
    }

    public function angleProvider(): array
    {
        return [
            [0, 0],
            ['180deg', M_PI],
            [M_PI, M_PI],
            ['45deg', M_PI / 4],
        ];
    }

    /**
     * @test
     */
    public function convertColor(): void
    {
        $color  = 'color';
        $result = '#000000';

        $node = new Container();
        $node->setAttribute('color', $color);

        $document = $this->getMockBuilder(Document::class)
                         ->onlyMethods(['getColorFromPalette'])
                         ->disableOriginalConstructor()
                         ->getMock();

        $document->expects($this->once())
                 ->method('getColorFromPalette')
                 ->with($color)
                 ->willReturn($result);

        $this->formatter->format($node, $document);

        $this->assertEquals($result, $node->getAttribute('color'));
    }
}
