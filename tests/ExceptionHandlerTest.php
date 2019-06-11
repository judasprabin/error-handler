<?php

namespace Carsguide\Tests;

use Carsguide\Exceptions\ExceptionHandler;
use Carsguide\Exceptions\FailedJobException;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;
use Mockery;
use Carsguide\Tests\TestCase;

class ExceptionHandlerTest extends TestCase
{
    public function setUp()
    {
        $this->handler = new ExceptionHandler();

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
    public function shouldRenderJsonResponseForModelNotFoundException()
    {
        $mock = Mockery::mock(ModelNotFoundException::class)->makePartial();

        $response = $this->handler->render(new Request(), $mock);

        $this->assertEquals('404', $response->getStatusCode());
    }

    /**
     * @test
     * @group ExceptionHandler
     */
    public function shouldRenderJsonResponseForFailedJobExceptionWithAppDebugFalse()
    {
        putenv('APP_DEBUG=' . false);

        $response = $this->handler->render(new Request(), new FailedJobException('failed job', 400));

        $this->assertEquals('500', $response->getStatusCode());
    }

    /**
     * @test
     * @group ExceptionHandler
     */
    public function shouldRenderJsonResponseForFailedJobExceptionWithAppDebugTrue()
    {
        putenv('APP_DEBUG=' . true);

        $response = $this->handler->render(new Request(), new FailedJobException('failed job', 400));

        $this->assertEquals('400', $response->getStatusCode());
    }
}
