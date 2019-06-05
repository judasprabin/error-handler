<?php

namespace Carsguide\Tests;

use Carsguide\Error\ErrorHandler;
use Carsguide\Error\Exceptions\FailedJobException;
use Carsguide\Error\Exceptions\SendGridException;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Exception;
use Carsguide\Tests\TestCase;

class ErrorHandlerTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
    }

    /**
     * @test
     * @group ErrorHandler
     */
    public function reportShouldLogSendGridException()
    {
        $this->handler = new ErrorHandler();

        Log::shouldReceive('info')->atLeast();

        $this->handler->report(new SendGridException('sendgrid exceptions', 400, ''));
    }

    /**
     * @test
     * @group ErrorHandler
     */
    public function shouldrRenderResponseForSendGridException()
    {
        $this->handler = new ErrorHandler();

        $response = $this->handler->render(new Request(), new SendGridException('sendgrid exceptions', 400, 'body'));

        $this->assertEquals(SendGridException::RESPONSE_STATUS_CODE, $response->getStatusCode());
    }
}
