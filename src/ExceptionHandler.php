<?php

namespace Carsguide\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Queue\MaxAttemptsExceededException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Exceptions\Handler as BaseExceptionHandler;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class ExceptionHandler extends BaseExceptionHandler
{
    /**
     * A list of the custom exception types that should not be reported.
     *
     * @var array
     */
    protected $customDontReport = [];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
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
     * Create a new exception handler instance.
     *
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @return void
     */
    public function __construct(Container $container)
    {
        $this->container = $container;

        $this->register();
    }

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param Throwable $e
     * @return void
     *
     * @throws Throwable
     */
    public function report(Throwable $e)
    {
        parent::report($e);

        if ($e instanceof ValidationException) {
            Log::error('Validation of request failed', [
                'errorFieldKeys' => implode(',', array_keys($e->response->original)),
                'requestUri' => app(Request::class)->getRequestUri(),
                'requestHttpReferer' => app(Request::class)->headers->get('referer'),
                'requestContent' => app(Request::class)->toArray(),
            ]);
        }
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param \Illuminate\Http\Request $request
     * @param Throwable $e
     * @return \Illuminate\Http\JsonResponse
     */
    public function render($request, Throwable $e)
    {

        if ($e instanceof ValidationException) {
            return parent::render($request, $e);
        }

        if ($e instanceof ModelNotFoundException) {
            if (env('APP_DEBUG', false)) {
                return response()->json([
                    'errorMsg' => 'Resource could not be found',
                    'originalErrorMsg' => $e->getMessage(),
                ], 404);
            }
            return response()->json([
                'errorMsg' => 'Resource could not be found',
            ], 404);
        }

        $throwable_exception = $e;
        if (!$e instanceof FlattenException) {
            $e = FlattenException::createFromThrowable($e);
        }

        $status_code = $e->getStatusCode();
        if (env('APP_DEBUG', false)) {
            return response()->json([
                'errorMsg' => $e->getMessage(),
                'stackTrace' => $e->getTrace()
            ], $status_code);
        }

        switch ($status_code) {
            case 404:
            case 405:
                $msg = 'Sorry, the page you are looking for could not be found.';
                break;

            case $status_code >= 500 && $status_code <= 599:
                if (extension_loaded('newrelic')) {
                    newrelic_notice_error($throwable_exception);
                }
                $msg = 'Whoops, looks like something went wrong.';
                break;

            default:
                $msg = 'Whoops, looks like something went wrong.';
        }

        return response()->json([
            'errorMsg' => $msg,
        ], $status_code);
    }
}
