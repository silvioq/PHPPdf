<?php

declare(strict_types=1);

namespace PHPPdf\Test\Core\Formatter;

use PHPPdf\Core\Document;
use PHPPdf\ObjectMother\NodeObjectMother;
use PHPPdf\Core\Node\Page;
use PHPPdf\Core\Formatter\ElasticPageFormatter;
use PHPPdf\PHPUnit\Framework\TestCase;

class ElasticPageFormatterTest extends TestCase
{
    private ElasticPageFormatter  $formatter;
    private Document $document;
    private NodeObjectMother      $nodeObjectMother;

    public function setUp(): void
    {
        $this->formatter = new ElasticPageFormatter();
        $this->document  = $this->createDocumentStub();

        $this->nodeObjectMother = new NodeObjectMother($this);
    }

    /**
     * @test
     * @dataProvider correctHeightOfPageProvider
     */
    public function correctHeightOfPage($originalHeight, array $childrenDiagonalYCoord): void
    {
        $page = new Page(['page-size' => '500:'.$originalHeight]);

        foreach ($childrenDiagonalYCoord as $yCoord) {
            $node = $this->nodeObjectMother->getNodeStub(0, $page->getHeight(), 100, $page->getHeight() - $yCoord);
            $page->add($node);
        }

        $minYCoord = $childrenDiagonalYCoord ? min($childrenDiagonalYCoord) : $originalHeight;

        $this->formatter->format($page, $this->document);

        $expectedHeight = $originalHeight - $minYCoord;
        $translation    = $originalHeight - $expectedHeight;

        $this->assertEquals($expectedHeight, $page->getRealHeight());

        foreach ($page->getChildren() as $i => $child) {
            $expectedDiagonalYCoord = $childrenDiagonalYCoord[$i] - $translation;

            $actualDiagonalYCoord = $child->getDiagonalPoint()->getY();

            $this->assertEquals($expectedDiagonalYCoord, $actualDiagonalYCoord);
        }
    }

    public function correctHeightOfPageProvider(): array
    {
        return [
            [500, [300, 400]],
            [500, [-300, -200]],
            [500, []],
        ];
    }
}
