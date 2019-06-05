<?php

namespace Carsguide\Error;

use Carsguide\Error\Exceptions\FailedJobException;
use Carsguide\Error\Exceptions\SendGridException;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Queue\MaxAttemptsExceededException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Laravel\Lumen\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ErrorHandler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        AuthorizationException::class,
        HttpException::class,
        MaxAttemptsExceededException::class,
        ModelNotFoundException::class,
        ValidationException::class,
        FailedJobException::class,
        SendGridException::class,
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception $e
     * @return void
     */
    public function report(Exception $e)
    {
        parent::report($e);

        if ($e instanceof ValidationException) {
            Log::info('Validation of request failed', [
                'errorFieldKeys' => implode(array_keys($e->response->original), ','),
                'requestUri' => app(Request::class)->getRequestUri(),
            ]);

            return;
        }

        if ($e instanceof SendGridException) {
            Log::info('SendGrid api call failed', [
                'statusCode' => $e->getSendGridResponseStatusCode(),
                'body' => $e->getSendGridResponseBody(),
                'errorMsg' => $e->getMessage(),
                'stackTrace' => $e->getTrace()
            ]);
        }
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Exception $e
     * @return \Illuminate\Http\JsonResponse
     */
    public function render($request, Exception $e)
    {
        if ($e instanceof ValidationException) {
            return parent::render($request, $e);
        }

        if ($e instanceof SendGridException) {
            return $e->getResponse();
        }

        if (env('APP_DEBUG', false)) {
            return parent::render($request, $e);
        }

        if (!$e instanceof FlattenException) {
            $e = FlattenException::create($e);
        }

        switch ($e->getStatusCode()) {
            case 404:
                $msg = 'Sorry, the page you are looking for could not be found.';
                break;
            default:
                $msg = 'Whoops, looks like something went wrong.';
        }

        return response()->json(['errorMsg' => $msg], $e->getStatusCode());
    }
}
