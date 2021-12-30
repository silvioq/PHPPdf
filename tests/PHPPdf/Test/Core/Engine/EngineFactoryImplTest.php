<?php

declare(strict_types=1);

namespace PHPPdf\Test\Core\Engine;

use Imagine\Exception\RuntimeException;
use PHPPdf\Core\Engine\EngineFactoryImpl;
use PHPPdf\Exception\DomainException;
use PHPPdf\PHPUnit\Framework\TestCase;
use PHPPdf\Core\Engine\ZF\Engine;

class EngineFactoryImplTest extends TestCase
{
    private EngineFactoryImpl $factory;

    public function setUp(): void
    {
        $this->factory = new EngineFactoryImpl();
    }

    /**
     * @test
     * @dataProvider validTypeProvider
     */
    public function engineCreationSuccess($type, $expectedClass): void
    {
        try {
            $engine = $this->factory->createEngine($type);

            $this->assertInstanceOf($expectedClass, $engine);
        } catch (RuntimeException $e) {
            $this->markTestSkipped('Exception from Imagine library, propably some graphics library is not installed');
        }
    }

    public function validTypeProvider(): array
    {
        return [
            [EngineFactoryImpl::TYPE_IMAGE, \PHPPdf\Core\Engine\Imagine\Engine::class],
            [EngineFactoryImpl::TYPE_PDF, Engine::class],
        ];
    }

    /**
     * @test
     * @dataProvider invalidTypeProvider
     *
     */
    public function engineCreationFailure($type): void
    {
        $this->expectException(DomainException::class);
        $this->factory->createEngine($type);
    }

    public function invalidTypeProvider(): array
    {
        return [
            ['some type'],
        ];
    }

    /**
     * @test
     * @dataProvider validImageTypeProvider
     */
    public function imageEngineCreationSuccess($type): void
    {
        try {
            $engine = $this->factory->createEngine(EngineFactoryImpl::TYPE_IMAGE, [
                EngineFactoryImpl::OPTION_ENGINE => $type,
            ]);

            $this->assertInstanceOf(\PHPPdf\Core\Engine\Imagine\Engine::class, $engine);
        } catch (RuntimeException $e) {
            $this->markTestSkipped('Exception from Imagine library, propably some graphics library is not installed');
        }
    }

    public function validImageTypeProvider(): array
    {
        return [
            [EngineFactoryImpl::ENGINE_GD],
            [EngineFactoryImpl::ENGINE_IMAGICK],
            [EngineFactoryImpl::ENGINE_GMAGICK],
        ];
    }

    /**
     * @test
     * @dataProvider invvalidImageTypeProvider
     *
     */
    public function imageEngineCreationFailure($type): void
    {
        $this->expectException(DomainException::class);
        $engine = $this->factory->createEngine(EngineFactoryImpl::TYPE_IMAGE, [
            EngineFactoryImpl::OPTION_ENGINE => $type,
        ]);
    }

    public function invvalidImageTypeProvider(): array
    {
        return [
            ['some'],
        ];
    }
}
