<?php

declare(strict_types=1);

namespace PHPPdf\Test\Core\Formatter;


use PHPPdf\Core\Document;
use PHPPdf\Core\Formatter\ImageRatioFormatter;
use PHPPdf\PHPUnit\Framework\TestCase;
use PHPPdf\Test\Helper\NodeAssert;
use PHPPdf\Test\Helper\NodeBuilder;
use PHPPdf\Test\Helper\Image;

class ImageRatioFormatterTest extends TestCase
{
    private ImageRatioFormatter $formatter;
    private Document            $document;

    public function setUp(): void
    {
        $this->formatter = new ImageRatioFormatter();
        $this->document  = $this->createDocumentStub();
    }

    /**
     * @test
     * @dataProvider dataProvider
     */
    public function givenRatioDoesntMatchOriginalRatio_fixDimensionToFitOriginalRatio($originalWidth, $originalHeight, $currentWidth, $currentHeight, $expectedWidth, $expectedHeight): void
    {
        //given

        $image = NodeBuilder::create()
                            ->nodeClass(Image::class)
                            ->attr('original-width', $originalWidth)
                            ->attr('original-height', $originalHeight)
                            ->attr('width', $currentWidth)
                            ->attr('height', $currentHeight)
                            ->getNode();

        //when

        $this->formatter->format($image, $this->document);

        //then

        NodeAssert::create($image)
                  ->width($expectedWidth)
                  ->height($expectedHeight);
    }

    public function dataProvider(): array
    {
        return [
            [50, 100, 50, 50, 25, 50],
            [100, 50, 50, 50, 50, 25],
        ];
    }
}
