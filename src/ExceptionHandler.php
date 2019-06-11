<?php

namespace Carsguide\Exceptions;

use Carsguide\Exceptions\FailedJobException;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Queue\MaxAttemptsExceededException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Laravel\Lumen\Exceptions\Handler as BaseExceptionHandler;
use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ExceptionHandler extends BaseExceptionHandler
{
    /**
     * A list of the custom exception types that should not be reported.
     *
     * @var array
     */
    protected $customDontReport = [];

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
    ];

    /**
     * Create a new exception instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->dontReport = array_merge($this->dontReport, $this->customDontReport);
    }

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
            Log::error('Validation of request failed', [
                'errorFieldKeys' => implode(array_keys($e->response->original), ','),
                'requestUri' => app(Request::class)->getRequestUri(),
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

        if ($e instanceof ModelNotFoundException) {
            return response()->json([
                'errorMsg' => 'Resource could not be found',
                'originalErrorMsg' => $e->getMessage(),
            ], 404);
        }

        if (env('APP_DEBUG', false)) {
            return response()->json([
                'errorMsg' => $e->getMessage(),
                'stackTrace' => $e->getTrace()
            ], $e->getCode());
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

        return response()->json([
            'errorMsg' => $msg,
        ], $e->getStatusCode());
    }
}
