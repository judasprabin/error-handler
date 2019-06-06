<?php

namespace Carsguide\Tests;

use Carsguide\Error\ErrorHandler;
use Carsguide\Error\Exceptions\FailedJobException;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;
use Mockery;
use Carsguide\Tests\TestCase;

class ErrorHandlerTest extends TestCase
{
    public function setUp()
    {
        $this->handler = new ErrorHandler();

        parent::setUp();
    }

    /**
     * @test
     * @group ErrorHandler
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
     * @group ErrorHandler
     */
    public function shouldRenderJsonResponseForModelNotFoundException()
    {
        $mock = Mockery::mock(ModelNotFoundException::class)->makePartial();

        $response = $this->handler->render(new Request(), $mock);

        $this->assertEquals('404', $response->getStatusCode());
    }

    /**
     * @test
     * @group ErrorHandler
     */
    public function shouldRenderJsonResponseForFailedJobExceptionWithAppDebugFalse()
    {
        putenv('APP_DEBUG=' . false);

        $response = $this->handler->render(new Request(), new FailedJobException('failed job', 400));

        $this->assertEquals('500', $response->getStatusCode());
    }

    /**
     * @test
     * @group ErrorHandler
     */
    public function shouldRenderJsonResponseForFailedJobExceptionWithAppDebugTrue()
    {
        putenv('APP_DEBUG=' . true);

        $response = $this->handler->render(new Request(), new FailedJobException('failed job', 400));

        $this->assertEquals('400', $response->getStatusCode());
    }
}
