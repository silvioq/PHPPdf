<?php

declare(strict_types=1);

namespace PHPPdf\Test\Core\ComplexAttribute;

use PHPPdf\Core\ComplexAttribute\ComplexAttributeFactory;
use PHPPdf\Exception\InvalidArgumentException;
use PHPPdf\PHPUnit\Framework\TestCase;
use PHPPdf\Stub\ComplexAttribute\ComplexAttributeStub;

class ComplexAttributeFactoryTest extends TestCase
{
    private ComplexAttributeFactory $factory;

    public function setUp(): void
    {
        $this->factory = new ComplexAttributeFactory();
    }

    /**
     * @test
     */
    public function setDefinitionOfComplexAttribute(): void
    {
        $this->assertFalse($this->factory->hasDefinition('stub'));
        $this->factory->addDefinition('stub', 'ComplexAttributeStub');
        $this->assertTrue($this->factory->hasDefinition('stub'));
    }

    /**
     * @test
     */
    public function getParameterNames(): void
    {
        $this->factory->addDefinition('stub', ComplexAttributeStub::class);
        $parameters = $this->factory->getParameters('stub');

        $this->assertEquals(['color', 'someParameter'], $parameters);
    }

    /**
     * @test
     *
     */
    public function requiredParametersMustBePassed(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->factory->addDefinition('stub', ComplexAttributeStub::class);
        $this->factory->create('stub', []);
    }

    /**
     * @test
     * @dataProvider parameterNamesProvider
     */
    public function createUsingValidParameters($parameterName, $parameterValue, $propertyName): void
    {
        $this->factory->addDefinition('stub', ComplexAttributeStub::class);
        $complexAttribute = $this->factory->create('stub', ['color' => '#cccccc', $parameterName => $parameterValue]);

        $this->assertNotNull($complexAttribute);
        $this->assertInstanceOf(ComplexAttributeStub::class, $complexAttribute);
        $this->assertEquals($parameterValue, $this->getAttribute($complexAttribute, $propertyName));
    }

    public function parameterNamesProvider(): array
    {
        return [
            ['someParameter', 'some value', 'someParameter'],
            ['some-parameter', 'some value', 'someParameter'],
        ];
    }

    /**
     * @test
     *
     */
    public function throwExceptionIfPassedParameterDosntExist(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->factory->addDefinition('stub', ComplexAttributeStub::class);
        $this->factory->create('stub', ['color' => '#cccccc', 'unexisted-parameter' => 'value']);
    }

    /**
     * @test
     *
     */
    public function throwExceptionIfDefinitionDosntFound(): void
    {
        $this->expectException(\PHPPdf\Core\ComplexAttribute\Exception\DefinitionNotFoundException::class);
        $this->factory->create('stub');
    }

    /**
     * @test
     */
    public function unserializedFactoryIsCopyOfSerializedFactory(): void
    {
        $this->factory->addDefinition('stub1', ComplexAttributeStub::class);
        $this->factory->addDefinition('stub2', ComplexAttributeStub::class);

        $this->factory->create('stub1', ['color' => '#ffffff']);

        $unserializedFactory = unserialize(serialize($this->factory));

        $unserializedDefinitions = $this->invokeMethod($unserializedFactory, 'getDefinitions');
        $definitions             = $this->invokeMethod($this->factory, 'getDefinitions');

        $this->assertEquals($definitions, $unserializedDefinitions);
    }
}
