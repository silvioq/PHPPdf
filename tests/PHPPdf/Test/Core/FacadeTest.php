<?php

namespace PHPPdf\Test\Core;

use PHPPdf\Core\ColorPalette;
use PHPPdf\Core\Configuration\LoaderImpl;
use PHPPdf\Core\Facade;
use PHPPdf\Core\FacadeConfiguration;

class FacadeTest extends \PHPPdf\PHPUnit\Framework\TestCase
{
    private $facade;

    private $loaderMock;
    private $documentParser;

    public function setUp(): void
    {
        $this->loaderMock = $this->getMockBuilder('PHPPdf\Core\Configuration\Loader')
                                 ->getMock();
        $this->documentParser = $this->createMock('PHPPdf\Core\Parser\DocumentParser');
        $this->stylesheetParser = $this->getMockBuilder('PHPPdf\Core\Parser\StylesheetParser')
                                       ->onlyMethods(array('parse'))
                                       ->disableOriginalConstructor()
                                       ->getMock();

        $document = $this->createDocumentStub();

        $this->facade = new Facade($this->loaderMock, $document, $this->documentParser, $this->stylesheetParser);
    }

    /**
     * @test
     */
    public function parsersMayByInjectedFromOutside()
    {
        $documentParser = $this->createMock('PHPPdf\Core\Parser\DocumentParser');
        $stylesheetParser = $this->createMock('PHPPdf\Core\Parser\StylesheetParser');

        $this->facade->setDocumentParser($documentParser);
        $this->facade->setStylesheetParser($stylesheetParser);

        $this->assertTrue($this->facade->getDocumentParser() === $documentParser);
        $this->assertTrue($this->facade->getStylesheetParser() === $stylesheetParser);
    }

    /**
     * @test
     */
    public function drawingProcess()
    {
        $xml = '<pdf></pdf>';
        $stylesheet = '<stylesheet></stylesheet>';
        $colorPaletteContent = '<colors></colors>';
        $colorPalette = array('color' => '#abcabc');
        $content = 'pdf content';

        $documentMock = $this->getMockBuilder('PHPPdf\Core\Document')
                             ->onlyMethods(array('draw', 'initialize', 'render', 'addFontDefinitions', 'setComplexAttributeFactory', 'setColorPalette'))
                             ->disableOriginalConstructor()
                             ->getMock();

        $stylesheetParserMock = $this->createPartialMock('PHPPdf\Core\Parser\StylesheetParser', array('parse'));
        $constraintMock = $this->createMock('PHPPdf\Core\Parser\StylesheetConstraint');
        $pageCollectionMock = $this->createPartialMock('PHPPdf\Core\Node\PageCollection', array());

        $colorPaletteParserMock = $this->createMock('PHPPdf\Parser\Parser');
        $colorPaletteParserMock->expects($this->once())
                               ->method('parse')
                               ->willReturn($colorPalette);
        $this->facade->setColorPaletteParser($colorPaletteParserMock);

        $nodeFactoryMock = $this->createMock('PHPPdf\Core\Node\NodeFactory');
        $complexAttributeFactoryMock = $this->createMock('PHPPdf\Core\ComplexAttribute\ComplexAttributeFactory');
        $fontDefinitionsStub = array('some-data');

        $this->loaderMock->expects($this->atLeastOnce())
                         ->method('createNodeFactory')
                         ->willReturn($nodeFactoryMock);
        $this->loaderMock->expects($this->atLeastOnce())
                         ->method('createComplexAttributeFactory')
                         ->willReturn($complexAttributeFactoryMock);
        $this->loaderMock->expects($this->atLeastOnce())
                         ->method('createFontRegistry')
                         ->willReturn($fontDefinitionsStub);

        $documentMock->expects($this->once())
                     ->method('setColorPalette')
                     ->with(new ColorPalette($colorPalette));

        $documentMock->expects($this->once())
                     ->method('addFontDefinitions')
                     ->with($fontDefinitionsStub);
        $documentMock->expects($this->once())
                     ->method('setComplexAttributeFactory')
                     ->with($complexAttributeFactoryMock);
        $this->documentParser->expects($this->once())
                             ->method('setComplexAttributeFactory')
                             ->with($complexAttributeFactoryMock);
        $this->documentParser->expects($this->once())
                             ->method('setNodeFactory')
                             ->with($nodeFactoryMock);

        $this->documentParser->expects($this->once())
                             ->method('parse')
                             ->with($this->equalTo($xml), $this->equalTo($constraintMock))
                             ->willReturn($pageCollectionMock);

        $this->stylesheetParser->expects($this->once())
                               ->method('parse')
                               ->with($this->equalTo($stylesheet))
                               ->willReturn($constraintMock);

        $documentMock->expects($this->once())
                ->method('draw')
                ->with($this->equalTo($pageCollectionMock));
        $documentMock->expects($this->once())
                ->method('render')
                ->willReturn($content);
        $documentMock->expects($this->once())
                ->method('initialize');

        $this->invokeMethod($this->facade, 'setDocument', array($documentMock));

        $result = $this->facade->render($xml, $stylesheet, $colorPaletteContent);

        $this->assertEquals($content, $result);
    }

    /**
     * @test
     * @dataProvider stylesheetCachingParametersProvider
     */
    public function dontCacheStylesheetConstraintByDefault($numberOfCacheMethodInvoking, $useCache)
    {
        $facade = new Facade(new LoaderImpl(), $this->createDocumentStub(), $this->documentParser, $this->stylesheetParser);

        $cache = $this->createPartialMock('PHPPdf\Cache\NullCache', array('test', 'save', 'load'));
        $cache->expects($this->exactly($numberOfCacheMethodInvoking))
              ->method('test')
              ->willReturn(false);
        $cache->expects($this->exactly(0))
              ->method('load');
        $cache->expects($this->exactly($numberOfCacheMethodInvoking))
              ->method('save');

        $this->documentParser->expects($this->once())
                             ->method('parse')
                             ->willReturn(new \PHPPdf\Core\Node\PageCollection());

        $this->stylesheetParser->expects($this->once())
                                 ->method('parse')
                                 ->willReturn(new \PHPPdf\Core\Parser\CachingStylesheetConstraint());


        $facade->setCache($cache);

        $facade->setUseCacheForStylesheetConstraint($useCache);

        $facade->render('<pdf></pdf>', '<stylesheet></stylesheet>');
    }

    public function stylesheetCachingParametersProvider()
    {
        return array(
            array(0, false),
            array(1, true),
        );
    }
}
