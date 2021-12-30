<?php

declare(strict_types=1);

namespace PHPPdf\Test\Core\Formatter;

use PHPPdf\Core\Node\Node;
use PHPPdf\Core\Node\Container;
use PHPPdf\Core\Document;
use PHPPdf\Core\Point;
use PHPPdf\Core\Node\Text;
use PHPPdf\Core\Node\Paragraph;
use PHPPdf\Core\Formatter\ParagraphFormatter;
use PHPPdf\ObjectMother\NodeObjectMother;
use PHPPdf\PHPUnit\Framework\TestCase;

class ParagraphFormatterTest extends TestCase
{
    private NodeObjectMother $objectMother;

    private ParagraphFormatter $formatter;
    private Document           $document;

    protected function init(): void
    {
        $this->objectMother = new NodeObjectMother($this);
    }

    public function setUp(): void
    {
        $this->formatter = new ParagraphFormatter();
        $this->document  = $this->createDocumentStub();
    }

    /**
     * @test
     * @dataProvider dataProvider
     */
    public function calculateTextsPositions($x, $width, $height, array $fontSizes, array $wordsSizes, array $expectedPositions): void
    {
        $paragraph = $this->createParagraph($x, $height, $width, $height);
        $this->createTextNodesAndAddToParagraph($wordsSizes, $fontSizes, $paragraph);

        $this->formatter->format($paragraph, $this->document);

        foreach ($paragraph->getChildren() as $i => $textNode) {
            $this->assertPointEquals($expectedPositions[$i][0], $textNode->getFirstPoint(), sprintf('%%sfirst point of "%d" text is invalid', $i));
            $this->assertPointEquals($expectedPositions[$i][1], $textNode->getDiagonalPoint(), sprintf('%%sdiagonal point of "%d" text is invalid', $i));
        }
    }

    private function assertPointEquals($expectedPoint, $actualPoint, $message = '')
    {
        $this->assertEqualsWithDelta($expectedPoint[0], $actualPoint[0], 1, sprintf($message, 'coord x of '));
        $this->assertEqualsWithDelta($expectedPoint[1], $actualPoint[1], 1, sprintf($message, 'coord y of '));
    }

    public function dataProvider(): array
    {
        $lineHeightFor15 = $this->getLineHeight(15);
        $lineHeightFor12 = $this->getLineHeight(12);

        return [
            [
                2,
                25,
                200,
                [15, 12],
                [
                    [
                        ['some', 'another'],
                        [10, 12],
                    ],
                    [
                        ['some', 'another', 'anotherYet'],
                        [10, 12, 15],
                    ],
                ],
                [
                    //expected position for 1st text
                    [
                        //first point
                        [2, 200],
                        //diagonal point
                        [24, 200 - $lineHeightFor15],
                    ],
                    [
                        [2, 200 - $lineHeightFor15],
                        [24, 200 - ($lineHeightFor15 + 2 * $lineHeightFor12)],
                    ],
                ],
            ],
            [
                0,
                25,
                200,
                [15, 12],
                [
                    [
                        ['word'],
                        [10],
                    ],
                    [
                        ['some'],
                        [10],
                    ],
                ],
                [
                    [
                        [0, 200],
                        [10, 200 - $lineHeightFor15],
                    ],
                    [
                        [10, 200 - ($lineHeightFor15 - $lineHeightFor12)],
                        [20, 200 - ($lineHeightFor15 - $lineHeightFor12) - $lineHeightFor12],
                    ],
                ],
            ],
            [
                0,
                25,
                200,
                [12, 15],
                [
                    [
                        ['word'],
                        [10],
                    ],
                    [
                        ['some'],
                        [10],
                    ],
                ],
                [
                    [
                        [0, 200 - ($lineHeightFor15 - $lineHeightFor12)],
                        [10, 200 - ($lineHeightFor15 - $lineHeightFor12) - $lineHeightFor12],
                    ],
                    [
                        [10, 200],
                        [20, 200 - $lineHeightFor15],
                    ],
                ],
            ],
        ];
    }

    private function createParagraph($x, $y, $width, $height): Paragraph
    {
        $parent = new Container();
        $parent->setWidth($width);
        $paragraph = new Paragraph();
        $parent->add($paragraph);

        $boundary = $this->objectMother->getBoundaryStub($x, $y, $width, $height);
        $this->invokeMethod($paragraph, 'setBoundary', [$boundary]);

        return $paragraph;
    }

    private function createTextNodesAndAddToParagraph(array $wordsSizes, array $fontSizes, Paragraph $paragraph): void
    {
        foreach ($wordsSizes as $index => $wordsSizesForNode) {
            $this->createTextNode($wordsSizesForNode, $fontSizes[$index], $paragraph);
        }
    }

    private function createTextNode(array $wordsSizes, $fontSize, Paragraph $paragraph): Text
    {
        $textNode = new Text();

        [$words, $sizes] = $wordsSizes;
        $textNode->setWordsSizes($words, $sizes);
        $textNode->setFontSize($fontSize);

        $paragraph->add($textNode);

        return $textNode;
    }

    private function getLineHeight($fontSize): float
    {
        return $fontSize * 1.2;
    }

    /**
     * @test
     */
    public function useWidthOfAncestorIfParagraphParentsWidthIsNull(): void
    {
        $width       = 300;
        $grandparent = $this->objectMother->getNodeStub(0, 500, $width, 100);
        $paragraph   = $this->createParagraph(0, 500, 0, 100);

        $grandparent->add($paragraph->getParent());

        $wordsSizes = [10, 20, 30];
        $text       = $this->createTextNode([
                                                ['ab', 'cd', 'ef'],
                                                $wordsSizes,
                                            ], 12, $paragraph);

        $this->formatter->format($paragraph, $this->document);

        $this->assertCount(1, $paragraph->getLines());
    }
}
