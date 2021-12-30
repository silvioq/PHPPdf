<?php

namespace PHPPdf\Test\Core\Parser;

use PHPPdf\Core\Parser\ColorPaletteParser;

use PHPPdf\PHPUnit\Framework\TestCase;

class ColorPaletteParserTest extends TestCase
{
    private $parser;
    
    public function setUp(): void
    {
        $this->parser = new ColorPaletteParser();
    }
    
    /**
     * @test
     */
    public function parseXml()
    {
        $xml = <<<XML
<colors>
<color name="blue" hex="#0000ff" />
<color name="green" hex="#00ff00" />
</colors>
XML;

        $colors = $this->parser->parse($xml);
        
        $expectedColors = array(
            'blue' => '#0000ff',
            'green' => '#00ff00',
        );

        $this->assertEquals($expectedColors, $colors);
    }
    
    /**
     * @test
     * @dataProvider invalidXmlProvider
     *
     */
    public function throwExceptionIfRequiredAttributesAreMissing($xml)
    {
        $this->expectException(\PHPPdf\Parser\Exception\ParseException::class);
        $this->parser->parse($xml);
    }
    
    public function invalidXmlProvider()
    {
        return array(
            array('<colors><color name="fsad" /></colors>'),
            array('<colors><color hex="fsad" /></colors>'),
        );
    }
}
