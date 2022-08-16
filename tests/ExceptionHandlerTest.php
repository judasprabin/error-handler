<?php

namespace Carsguide\Tests;

use Carsguide\Exceptions\ExceptionHandler;
use Carsguide\Exceptions\FailedJobException;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Mockery;
use Carsguide\Tests\TestCase;

class ExceptionHandlerTest extends TestCase
{
    public function setUp(): void
    {
        $container = Mockery::mock(Container::class);
        $this->handler = new ExceptionHandler($container);

        parent::setUp();
    }

    /**
     * @test
     * @group ExceptionHandler
     */
    public function shouldRenderJsonResponseForValidationException()
    {
        $mock = Mockery::mock(ValidationException::class)->makePartial();

        $mock->response = response()->json([
            'errorMsg' => 'validation fails',
        ], '422');

        $response = $this->handler->render(new Request(), $mock);

        $this->assertEquals('422', $response->getStatusCode());
    }

    /**
     * @test
     * @group ExceptionHandler
     */
    public function shouldRenderJsonResponseForModelNotFoundExceptionWithAppDebugTrue()
    {
        putenv('APP_DEBUG=' . true);

        $mock = Mockery::mock(ModelNotFoundException::class)->makePartial();

        $response = $this->handler->render(new Request(), $mock);

        $body = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('originalErrorMsg', $body);

        $this->assertEquals('404', $response->getStatusCode());
    }

    /**
     * @test
     * @group ExceptionHandler
     */
    public function shouldRenderJsonResponseForModelNotFoundExceptionWithAppDebugFalse()
    {
        putenv('APP_DEBUG=' . false);

        $mock = Mockery::mock(ModelNotFoundException::class)->makePartial();

        $response = $this->handler->render(new Request(), $mock);

        $body = json_decode($response->getContent(), true);

        $this->assertArrayNotHasKey('originalErrorMsg', $body);

        $this->assertEquals('404', $response->getStatusCode());
    }

    /**
     * @test
     * @group ExceptionHandler
     */
    public function shouldRenderJsonResponseForFailedJobExceptionWithAppDebugFalse()
    {
        putenv('APP_DEBUG=' . false);

        $response = $this->handler->render(new Request(), new FailedJobException('failed job', 0));

        $body = json_decode($response->getContent(), true);

        $this->assertArrayNotHasKey('stackTrace', $body);

        $this->assertEquals('500', $response->getStatusCode());
    }

    /**
     * @test
     * @group ExceptionHandler
     */
    public function shouldRenderJsonResponseForFailedJobExceptionWithAppDebugTrue()
    {
        putenv('APP_DEBUG=' . true);

        $response = $this->handler->render(new Request(), new FailedJobException('failed job', 0));

        $body = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('stackTrace', $body);

        $this->assertEquals('500', $response->getStatusCode());
    }
}
