<?php

namespace PedroQuezado\Code\Braspress;

use Exception;

/**
 * Exception class for handling errors specific to the Braspress client.
 */
class BraspressClienteException extends Exception
{
    /**
     * @var int|null The HTTP status code associated with the exception, if applicable.
     */
    protected $httpCode;

    /**
     * @var mixed|null The response body associated with the exception, if applicable.
     */
    protected $response;

    /**
     * BraspressClienteException constructor.
     *
     * @param string $message The exception message.
     * @param int|null $httpCode The HTTP status code, if applicable.
     * @param mixed|null $response The response body, if applicable.
     * @param int $code The internal exception code.
     * @param Exception|null $previous The previous exception used for exception chaining.
     */
    public function __construct($message = "", $httpCode = null, $response = null, $code = 0, Exception $previous = null)
    {
        $this->httpCode = $httpCode;
        $this->response = $response;
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get the HTTP status code associated with the exception.
     *
     * @return int|null The HTTP status code, or null if not set.
     */
    public function getHttpCode()
    {
        return $this->httpCode;
    }

    /**
     * Get the response body associated with the exception.
     *
     * @return mixed|null The response body, or null if not set.
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Convert the exception to a string representation.
     *
     * This method provides a detailed string that includes the class name, message,
     * HTTP code, and response body (if available).
     *
     * @return string The string representation of the exception.
     */
    public function __toString()
    {
        $responseDetails = is_array($this->response) ? json_encode($this->response) : $this->response;
        return __CLASS__ . ": [{$this->code}]: {$this->message}\nHTTP Code: {$this->httpCode}\nResponse: {$responseDetails}\n";
    }
}
