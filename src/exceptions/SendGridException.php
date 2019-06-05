<?php

namespace Carsguide\Error\Exceptions;

use Exception;

class SendGridException extends Exception
{
    /**
     *  The status code to use for the response.
     */
    const RESPONSE_STATUS_CODE = 400;

    /**
     * The recommended response to send to the client.
     *
     * @var \Illuminate\Http\JsonResponse
     */
    protected $response;

    /**
     * SendGrid api response status code to use for logging.
     *
     * @var int
     */
    protected $statusCode;

    /**
     * SendGrid api response body to use for logging.
     *
     * @var string
     */
    protected $body;

    /**
     * Create a new exception instance.
     *
     * @param string $errorMsg
     * @param int $statusCode
     * @param string $body
     * @return void
     */
    public function __construct($errorMsg, $statusCode, $body)
    {
        parent::__construct('SendGrid api call failed.');

        $this->statusCode = $statusCode;

        $this->body = $body;

        $this->response = response()->json(['errorMsg' => $errorMsg], self::RESPONSE_STATUS_CODE);
    }

    /**
     * Get sendgrid api response status code.
     *
     * @return integer
     */
    public function getSendGridResponseStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * Get sendgrid api response body.
     *
     * @return string
     */
    public function getSendGridResponseBody()
    {
        return $this->body;
    }

    /**
     * Get the response instance.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getResponse()
    {
        return $this->response;
    }
}
