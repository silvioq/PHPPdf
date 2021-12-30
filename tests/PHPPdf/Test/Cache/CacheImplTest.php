<?php

namespace PHPPdf\Test\Cache;

use Laminas\Cache\Exception\InvalidArgumentException;
use Laminas\Cache\Storage\StorageInterface;
use PHPPdf\Cache\CacheImpl;
use PHPPdf\Exception\RuntimeException;
use PHPPdf\PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class CacheImplTest extends TestCase
{
    private MockObject $engineMock;

    public function setUp(): void
    {
        $this->engineMock = $this->getCacheEngineMock();
    }

    /**
     * @test
     *
     */
    public function throwExceptionIfPassedCacheEngineIsUnavailable(): void
    {
        $this->expectException(RuntimeException::class);
        new CacheImpl('Unexisted-cache-engine');
    }

    /**
     * @test
     * @dataProvider provideCacheOperations
     */
    public function delegateOperationsToCacheEngine($method, $adapterMethod, array $args, $returnValue, $expectedArgs = null, $expectedReturnValue = null, $cacheOptions = [])
    {
        $expectedArgs        = $expectedArgs ?: $args;
        $expectedReturnValue = $expectedReturnValue ?? $returnValue;

        $matcher = $this->engineMock->expects($this->once())
                                    ->method($adapterMethod)
                                    ->willReturn($expectedReturnValue);
        call_user_func_array([$matcher, 'with'], $expectedArgs);

        $cache = new CacheImpl($this->engineMock, $cacheOptions);

        $this->assertEquals($returnValue, call_user_func_array([$cache, $method], $args));
    }

    public function provideCacheOperations(): array
    {
        return [
            ['load', 'getItem', ['id'], 'value', null, null, ['automatic_serialization' => false]],
            ['load', 'getItem', ['id'], 'value', null, serialize('value'), ['automatic_serialization' => true]],
            ['load', 'getItem', ['id'], 'value', null, serialize('value')],
            ['test', 'hasItem', ['id'], true],
            ['save', 'setItem', ['value', 'id'], true, ['id', 'value'], null, ['automatic_serialization' => false]],
            ['save', 'setItem', ['value', 'id'], true, ['id', serialize('value')], null, ['automatic_serialization' => true]],
            ['save', 'setItem', ['value', 'id'], true, ['id', serialize('value')], null],
            ['remove', 'removeItem', ['id'], true],
        ];
    }

    private function getCacheEngineMock()
    {
        $mock = $this->createMock(StorageInterface::class);

        return $mock;
    }

    /**
     * @test
     *
     * @dataProvider provideCacheOperations
     */
    public function wrapCacheEngineExceptions($operation, $adapterMethod, array $args)
    {
        $this->expectException(RuntimeException::class);
        $e = new InvalidArgumentException();

        $this->engineMock->expects($this->once())
                         ->method($adapterMethod)
                         ->will($this->throwException($e));

        $cache = new CacheImpl($this->engineMock);

        call_user_func_array([$cache, $operation], $args);
    }
}
