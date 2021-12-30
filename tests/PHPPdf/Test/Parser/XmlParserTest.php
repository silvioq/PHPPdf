<?php

namespace PHPPdf\Test\Parser;

use PHPPdf\PHPUnit\Framework\TestCase;
use PHPPdf\Parser\XmlParser as AbstractXmlParser;

class XmlParserTest extends TestCase
{
    private $parser;
    
    public function setUp(): void
    {
        $this->parser = new XmlParser();
    }
    
    /**
     * @test
     *
     */
    public function parse_throwExceptionWhenFileNotExists()
    {
        $this->expectException(\PHPPdf\Parser\Exception\ParseException::class);
        $this->parser->parse(__DIR__.'/unexistedfile');
    }
    
    /**
     * @test
     *
     */
    public function parse_throwExceptionWhenFileIsNotValidXml()
    {
        $this->expectException(\PHPPdf\Parser\Exception\ParseException::class);
        $this->parser->parse(__FILE__);
    }
    
    /**
     * @test
     */
    public function parse_successWhenFileIsValidXml()
    {
        $this->assertEquals([], $this->parser->parse(__DIR__.'/../../Resources/sample.xml'));
    }
}

class XmlParser extends AbstractXmlParser
{
    const ROOT_TAG = 'root';
    
    protected function createRoot()
    {
        return array();
    }

    protected function parseElement(\XMLReader $reader)
    {
    }

    protected function parseEndElement(\XMLReader $reader)
    {
    }
}
