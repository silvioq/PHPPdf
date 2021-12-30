<?php

declare(strict_types=1);

namespace PHPPdf\Test\Core\Formatter;

use PHPPdf\Core\Document,
    PHPPdf\Core\Point,
    PHPPdf\Core\Node\Node,
    PHPPdf\Core\Node\Container,
    PHPPdf\Core\Node\Page,
    PHPPdf\Core\Formatter\StandardPositionFormatter;
use PHPPdf\PHPUnit\Framework\TestCase;

class StandardPositionFormatterTest extends TestCase
{
    private StandardPositionFormatter $formatter;

    public function setUp(): void
    {
        $this->formatter = new StandardPositionFormatter();
    }

    /**
     * @test
     */
    public function nodeWithAutoMarginPositioning(): void
    {
        $node = new Container(['width' => 100, 'height' => 100]);
        $node->hadAutoMargins(true);
        $node->makeAttributesSnapshot();
        $node->setWidth(110);

        $child = new Container(['width' => 50, 'height' => 50]);
        $node->add($child);
        $page = new Page();
        $page->add($node);

        $node->getBoundary()->setNext($page->getFirstPoint());
        $child->getBoundary()->setNext($page->getFirstPoint());

        foreach ([$node, $child] as $g) {
            $this->formatter->format($g, $this->createDocumentStub());
        }

        $nodeBoundary  = $node->getBoundary();
        $childBoundary = $child->getBoundary();
        $pageBoundary  = $page->getBoundary();


        $this->assertEquals($pageBoundary[0]->translate(-5, 0), $nodeBoundary[0]);
        $this->assertEquals($pageBoundary[0]->translate(105, 0), $nodeBoundary[1]);
        $this->assertEquals($pageBoundary[0]->translate(105, 100), $nodeBoundary[2]);
        $this->assertEquals($pageBoundary[0]->translate(-5, 100), $nodeBoundary[3]);
    }
}
