<?php

declare(strict_types=1);

namespace PHPPdf\Test\Core\Formatter;

use PHPPdf\Core\Formatter\PageBreakingFormatter,
    PHPPdf\Core\Node\DynamicPage,
    PHPPdf\Core\Boundary,
    PHPPdf\Core\Document,
    PHPPdf\Core\Node\Container;
use PHPPdf\PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use PHPPdf\Core\Node\Page;

class PageBreakingFormatterTest extends TestCase
{
    private DynamicPage           $page;
    private PageBreakingFormatter $formatter;

    public function setUp(): void
    {
        $this->page      = new DynamicPage();
        $this->formatter = new PageBreakingFormatter();
    }

    /**
     * @test
     */
    public function pageOverflow()
    {
        $prototype  = $this->page->getPrototypePage();
        $container  = $this->getContainerStub([0, $prototype->getHeight()], [$prototype->getWidth(), 0]);
        $container2 = $this->getContainerStub([0, 0], [$prototype->getWidth(), -$prototype->getHeight()]);

        $this->page->add($container);
        $this->page->add($container2);

        $this->formatter->format($this->page, $this->createDocumentStub());

        $this->assertCount(2, $this->page->getPages());
    }

    /**
     * @test
     */
    public function breakingChildren()
    {
        $prototype = $this->page->getPrototypePage();

        $container  = $this->getContainerStub([0, $prototype->getHeight()], [$prototype->getWidth(), $prototype->getHeight() / 2]);
        $container2 = $this->getContainerStub([0, $prototype->getHeight() / 2], [$prototype->getWidth(), -$prototype->getHeight() / 2]);

        $this->page->add($container);
        $this->page->add($container2);

        $this->formatter->format($this->page, $this->createDocumentStub());

        $pages = $this->page->getPages();

        $this->assertCount(2, $pages);
        $this->assertCount(2, $pages[0]->getChildren());
        $this->assertCount(1, $pages[1]->getChildren());
    }

    private function getContainerStub($start, $end, array $methods = []): Container
    {
        $stub = new Container();
        $stub->setHeight($start[1] - $end[1]);
        $stub->getBoundary()->setNext($start[0], $start[1])
             ->setNext($end[0], $start[1])
             ->setNext($end[0], $end[1])
             ->setNext($start[0], $end[1])
             ->close();

        $boundary = new Boundary();
        $boundary->setNext($start[0], $start[1])
                 ->setNext($end[0], $start[1])
                 ->setNext($end[0], $end[1])
                 ->setNext($start[0], $end[1])
                 ->close();

        return $stub;
    }

    private function getContainerMock($start, $end, array $methods = []): MockObject|Container
    {
        $methods = array_merge(['getBoundary', 'getHeight'], $methods);
        $mock    = $this->createPartialMock(Container::class, $methods);

        $boundary = new Boundary();
        $boundary->setNext($start[0], $start[1])
                 ->setNext($end[0], $start[1])
                 ->setNext($end[0], $end[1])
                 ->setNext($start[0], $end[1])
                 ->close();

        $mock->expects($this->atLeastOnce())
             ->method('getBoundary')
             ->willReturn($boundary);

        $mock->expects($this->any())
             ->method('getHeight')
             ->willReturn($start[1] - $end[1]);

        return $mock;
    }


    /**
     * @test
     */
    public function multipleBreaking(): void
    {
        $prototype = $this->page->getPrototypePage();
        $height    = $prototype->getHeight();

        $container = $this->getContainerStub([0, $height], [100, -($height * 3)]);

        $this->page->add($container);

        $this->formatter->format($this->page, $this->createDocumentStub());

        $pages = $this->page->getPages();

        $this->assertCount(4, $pages);

        foreach ($pages as $page) {
            $children = $page->getChildren();
            $this->assertCount(1, $children);

            $child = current($children);
            $this->assertEquals([0, $height], $child->getStartDrawingPoint());
            $this->assertEquals([100, 0], $child->getEndDrawingPoint());
        }
    }

    /**
     * @test
     */
    public function multipleBreakingWithManyNodesPerPage(): void
    {
        $prototype      = $this->page->getPrototypePage();
        $originalHeight = $height = $prototype->getHeight();

        $heightOfNode = (int) ($originalHeight * 5 / 32);

        $mocks = [];
        for ($i = 0; $i < 32; $i++, $height -= $heightOfNode) {
            $this->page->add($this->getContainerStub([0, $height], [100, $height - $heightOfNode]));
        }

        $this->formatter->format($this->page, $this->createDocumentStub());

        $pages = $this->page->getPages();
        $this->assertCount(5, $pages);
    }


    /**
     * @test
     */
    public function pageShouldBeBreakIfBreakAttributeIsUsed(): void
    {
        $prototype = $this->getMockBuilder(Page::class)
                          ->enableOriginalConstructor()
                          ->onlyMethods(['copy'])
                          ->getMock();
        $prototype->expects($this->exactly(2))
                  ->method('copy')
                  ->willReturn($prototype);

        $this->invokeMethod($this->page, 'setPrototypePage', [$prototype]);

        $container = $this->getContainerMock([0, 700], [40, 600], ['getAttribute', 'breakAt']);
        $container->expects($this->atLeastOnce())
                  ->method('getAttribute')
                  ->with('break')
                  ->willReturn(false);

        $this->page->add($container);

        $container = $this->getContainerMock([0, 600], [0, 600], ['getAttribute', 'breakAt']);
        $container->expects($this->atLeastOnce())
                  ->method('getAttribute')
                  ->with('break')
                  ->willReturn(true);

        $this->page->add($container);

        $this->formatter->format($this->page, $this->createDocumentStub());
    }

    /**
     * @test
     *
     * @todo przerobić ten test, aby dotyczył nodeu który się podzielił na dwie strony, tylko że pomiędzy pierwszą częścią nodeu a końcem strony jest "luka" (np. tabela)
     */
    public function nextSiblingOfNotBreakableNodeMustBeDirectlyAfterThisNodeIfPageBreakOccurs(): void
    {
        $this->markTestIncomplete();

        $diagonalPoint = Point::getInstance(100, 10);

        $prototype = $this->getMockBuilder(Page::class)
                          ->enableOriginalConstructor()
                          ->onlyMethods(['copy', 'getHeight', 'getDiagonalPoint'])
                          ->getMock();
        $this->page->setMarginBottom(10);
        $prototype->expects($this->exactly(1))
                  ->method('copy')
                  ->willReturn($prototype);

        $prototype->expects($this->atLeastOnce())
                  ->method('getHeight')
                  ->willReturn(100);

        $prototype->expects($this->atLeastOnce())
                  ->method('getDiagonalPoint')
                  ->willReturn($diagonalPoint);

        $this->invokeMethod($this->page, 'setPrototypePage', [$prototype]);

        $notBrokenContainer = $this->getContainerMock([0, 100], [50, 30], ['breakAt']);
        $notBrokenContainer->expects($this->never())
                           ->method('breakAt');

        $this->page->add($notBrokenContainer);

        $brokenContainer = $this->getContainerMock([0, 30], [50, -10], ['breakAt']);
        $brokenContainer->expects($this->once())
                        ->method('breakAt')
                        ->willReturn(null);

        $this->page->add($brokenContainer);

        $nextSiblingOfBrokenContainer = $this->getContainerMock([0, -10], [50, -20], ['breakAt']);
        $nextSiblingOfBrokenContainer->expects($this->never())
                                     ->method('breakAt');

        $this->page->add($nextSiblingOfBrokenContainer);

        $this->page->collectOrderedDrawingTasks($this->createDocumentStub());

        $this->assertEquals($brokenContainer->getDiagonalPoint()->getY(), $nextSiblingOfBrokenContainer->getFirstPoint()->getY());
    }
}
