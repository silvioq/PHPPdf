<?php

declare(strict_types=1);

namespace PHPPdf\Test\Core\Formatter;

use PHPPdf\Core\Formatter\StandardDimensionFormatter;
use PHPPdf\PHPUnit\Framework\TestCase;
use PHPPdf\Test\Helper\NodeBuilder;
use PHPPdf\Test\Helper\NodeAssert;

class StandardDimensionFormatterTest extends TestCase
{
    private StandardDimensionFormatter $formatter;
    private \PHPPdf\Core\Document      $document;

    public function setUp(): void
    {
        $this->formatter = new StandardDimensionFormatter();
        $this->document  = $this->createDocumentStub();
    }

    /**
     * @test
     */
    public function givenExplictDimension_useGivenDimension(): void
    {
        $node = NodeBuilder::create()
                           ->attr('width', 120)
                           ->attr('height', 140)
                           ->getNode();

        $this->formatter->format($node, $this->document);

        NodeAssert::create($node)
                  ->width(120)
                  ->height(140);
    }

    /**
     * @test
     */
    public function givenNullWidthAndFloat_setZeroAsWidth(): void
    {
        $node = NodeBuilder::create()
                           ->attr('width', null)
                           ->attr('float', 'left')
                           ->getNode();

        $this->formatter->format($node, $this->document);

        NodeAssert::create($node)
                  ->width(0);
    }

    /**
     * @test
     * @dataProvider dimensionProvider
     */
    public function givenRealDimensionAndPaddings_useRealDimensionAndPaddingsToCalsulateDimension($realWidth, $realHeight, array $paddings): void
    {
        $node = NodeBuilder::create()
                           ->attr('width', random_int(1, 200))
                           ->attr('height', random_int(1, 200))
                           ->attr('real-width', $realWidth)
                           ->attr('real-height', $realHeight)
                           ->attrs($paddings)
                           ->getNode();

        $this->formatter->format($node, $this->document);

        NodeAssert::create($node)
                  ->width($realWidth + $paddings['padding-left'] + $paddings['padding-right'])
                  ->height($realHeight + $paddings['padding-top'] + $paddings['padding-bottom']);
    }

    public function dimensionProvider(): array
    {
        return [
            [200, 300, [
                'padding-left'   => 10,
                'padding-top'    => 11,
                'padding-right'  => 12,
                'padding-bottom' => 13,
            ]],
        ];
    }

    /**
     * @test
     */
    public function givenNodeAndParentWithDimensions_nodeWidthCantExceedParentWidth(): void
    {
        $node = NodeBuilder::create()
                           ->attr('width', 90)
                           ->attr('height', 90)
                           ->attr('padding', 10)
                           ->parent()
                           ->attr('width', 100)
                           ->attr('height', 100)
                           ->attr('padding', 2)
                           ->end()
                           ->getNode();

        $this->formatter->format($node, $this->document);

        NodeAssert::create($node)
                  ->height(90 + 2 * 10)
                  ->widthAsTheSameAsParentsWithoutPaddings();
    }

    /**
     * @test
     */
    public function givenMaxDimension_nodeCantExceedMaxDimension(): void
    {
        $node = NodeBuilder::create()
                           ->attr('width', 200)
                           ->attr('height', 200)
                           ->attr('max-width', 150)
                           ->attr('max-height', 120)
                           ->getNode();

        $this->formatter->format($node, $this->document);

        NodeAssert::create($node)
                  ->width(150)
                  ->height(120);
    }
}
