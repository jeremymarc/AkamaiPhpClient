<?php

namespace Akamai\Client\Exception;

class HttpException extends \RuntimeException implements AkamaiException
{
    /**
     * @var int
     */
    protected $statusCode;

    /**
     * @param int $statusCode
     * @param string $message
     * @param \Exception $previous
     * @param mixed $code
     */
    public function __construct($statusCode, $message = null, \Exception $previous = null, $code = 0)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return int
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }
}
