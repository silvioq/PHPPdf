<?php

namespace PHPPdf\Test\Core\Parser;

use PHPPdf\Core\Parser\StylesheetConstraint;

use PHPPdf\Core\Document;

use PHPPdf\Core\Parser\MarkdownDocumentParser;

use PHPPdf\PHPUnit\Framework\TestCase;

class MarkdownDocumentParserTest extends TestCase
{
    private $markdownParser;
    private $documentParser;
    private $markdownDocumentParser;
    
    public function setUp(): void
    {
        $this->markdownParser = $this->createMock('PHPPdf\Parser\Parser');
        $this->documentParser = $this->createMock('PHPPdf\Core\Parser\DocumentParser');
        
        $this->markdownDocumentParser = new MarkdownDocumentParser($this->documentParser, $this->markdownParser);
    }
    
    /**
     * @test
     * @dataProvider methodsProvider
     */
    public function delegateMethodInvocationsToInnerDocumentParser($method, $argument)
    {
        $this->documentParser->expects($this->once())
                             ->method($method)
                             ->with($argument);
        $this->markdownDocumentParser->$method($argument);
    }
    
    public function methodsProvider()
    {
        return array(
            array('setNodeFactory', $this->createMock('PHPPdf\Core\Node\NodeFactory')),
            array('setComplexAttributeFactory', $this->createMock('PHPPdf\Core\ComplexAttribute\ComplexAttributeFactory')),
            array('addListener', $this->createMock('PHPPdf\Core\Parser\DocumentParserListener')),
            array('setDocument', $this->createDocumentStub()),
        );
    }
    
    /**
     * @test
     */
    public function getNodeManagerInvokesTheSameMethodOfInnerDocumentParser()
    {
        $nodeManager = $this->createMock('PHPPdf\Core\Node\Manager');
        
        $this->documentParser->expects($this->once())
                             ->method('getNodeManager')
                             ->willReturn($nodeManager);
                             
        $this->assertEquals($nodeManager, $this->markdownDocumentParser->getNodeManager());
    }
    
    /**
     * @test
     */
    public function parseInvokesMarkdownParserAndInnerDocumentParser()
    {
        $markdown = 'some markdown';
        $markdownParserOutput = 'markdown parser output';
        $innerDocumentParserOutput = 'inner document parser output';
        
        $this->markdownParser->expects($this->once())
                             ->method('parse')
                             ->with($markdown)
                             ->willReturn($markdownParserOutput);

        $this->documentParser->expects($this->once())
                             ->method('parse')
                             ->with($this->stringContains($markdownParserOutput))
                             ->willReturn($innerDocumentParserOutput);
                             
        $this->assertEquals($innerDocumentParserOutput, $this->markdownDocumentParser->parse($markdown));
    }
    
    /**
     * @test
     */
    public function useFacadeToCreateStylesheetConstraint()
    {
        $stylesheetConstraint = new StylesheetConstraint();
        
        $facade = $this->getMockBuilder('PHPPdf\Core\Facade')
                       ->onlyMethods(array('retrieveStylesheetConstraint'))
                       ->disableOriginalConstructor()
                       ->getMock();
                       
        $this->markdownDocumentParser->setFacade($facade);
        
        $facade->expects($this->once())
               ->method('retrieveStylesheetConstraint')
               ->with($this->isInstanceOf('PHPPdf\DataSource\DataSource'))
               ->willReturn($stylesheetConstraint);
               
        $this->documentParser->expects($this->once())
                             ->method('parse')
                             ->with($this->isType('string'), $stylesheetConstraint);
                             
        $this->markdownDocumentParser->parse('markdown');
    }
}
