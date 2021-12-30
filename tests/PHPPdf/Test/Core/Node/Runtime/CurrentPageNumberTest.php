<?php

namespace PHPPdf\Test\Core\Node\Runtime;

use PHPPdf\Core\DrawingTaskHeap;

use PHPPdf\Core\DrawingTask;
use PHPPdf\Core\Node\Runtime\CurrentPageNumber,
    PHPPdf\Core\Document,
    PHPPdf\Core\Node\DynamicPage,
    PHPPdf\Core\Node\Page;
use PHPPdf\Core\Node\Paragraph\LinePart;
use PHPPdf\Core\Node\PageContext;

class CurrentPageNumberTest extends \PHPPdf\PHPUnit\Framework\TestCase
{
    /**
     * @var PHPPdf\Core\Node\Node
     */
    private $node;

    public function setUp(): void
    {
        $this->node = new CurrentPageNumber();
    }

    /**
     * @test
     */
    public function drawing()
    {
        $mock = $this->getPageMock();

        $this->node->setParent($mock);

        $tasks = new DrawingTaskHeap();
        $this->node->collectOrderedDrawingTasks($this->createDocumentStub(), $tasks);
        $this->assertEquals(0, count($tasks));
    }

    private function getPageMock()
    {
        $mock = $this->createPartialMock('PHPPdf\Core\Node\Page', ['markAsRuntimeNode']);
        $mock->expects($this->once())
             ->method('markAsRuntimeNode');

        return $mock;
    }

    /**
     * @test
     */
    public function cannotMergeComplexAttributes()
    {
        $this->node->mergeComplexAttributes('name', ['name' => 'value']);

        $this->assertEmpty($this->node->getComplexAttributes());
    }

    /**
     * @test
     */
    public function valueBeforeEvaluation()
    {
        $dummyText = $this->node->getAttribute('dummy-text');
        $text      = $this->node->getText();

        $this->assertNotEmpty($dummyText);
        $this->assertEquals($dummyText, $text);
    }

    /**
     * @test
     * @dataProvider offsetProvider
     */
    public function drawingAfterEvaluating($offset)
    {
        $pageMock    = $this->createPartialMock(Page::class, ['getContext']);
        $contextMock = $this->getMockBuilder(PageContext::class)
                            ->enableOriginalConstructor()
                            ->setConstructorArgs([5, new DynamicPage()])
                            ->getMock();


        $pageMock->expects($this->atLeastOnce())
                 ->method('getContext')
                 ->willReturn($contextMock);

        $pageNumber = 5;
        $contextMock->expects($this->atLeastOnce())
                    ->method('getPageNumber')
                    ->willReturn($pageNumber);

        $format = 'abc%s.';
        $this->node->setAttribute('format', $format);
        $this->node->setAttribute('offset', $offset);

        $this->node->setParent($pageMock);
        $linePart = $this->getMockBuilder(LinePart::class)
                         ->onlyMethods(['setWords', 'collectOrderedDrawingTasks'])
                         ->disableOriginalConstructor()
                         ->getMock();

        $expectedText = sprintf($format, $pageNumber + $offset);
        $linePart->expects($this->once())
                 ->id('1')
                 ->method('setWords')
                 ->with($expectedText);


        $document        = $this->createDocumentStub();
        $drawingTaskStub = new DrawingTask(function () { });
        $tasks           = new DrawingTaskHeap();

        $linePart->expects($this->once())
                 ->after('1')
                 ->method('collectOrderedDrawingTasks')
                 ->with($this->isInstanceOf(Document::class), $this->isInstanceOf(DrawingTaskHeap::class))
                 ->willReturnCallback(function () use ($tasks, $drawingTaskStub) {
                     $tasks->insert($drawingTaskStub);
                 });

        $this->node->addLinePart($linePart);

        $this->node->evaluate();

        $this->node->collectOrderedDrawingTasks($this->createDocumentStub(), $tasks);
        $this->assertCount(1, $tasks);
        $this->assertEquals($expectedText, $this->node->getText());
    }

    public function offsetProvider(): array
    {
        return [
            [0],
            [5],
        ];
    }

    /**
     * @test
     */
    public function settingPage(): void
    {
        $page = new Page();

        $this->node->setPage($page);

        $this->assertSame($page, $this->node->getPage());
    }

    /**
     * @test
     */
    public function afterCopyParentIsntDetached()
    {
        $page = new Page();

        $this->node->setParent($page);
        $copy = $this->node->copyAsRuntime();

        $this->assertTrue($copy->getParent() === $page);
    }
}
