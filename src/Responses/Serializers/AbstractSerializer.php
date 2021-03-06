<?php

namespace Instagram\SDK\Responses\Serializers;

use Instagram\SDK\DTO\Envelope;
use Instagram\SDK\DTO\Interfaces\ResponseMessageInterface;
use Instagram\SDK\Responses\Exceptions\ApiResponseException;
use Instagram\SDK\Responses\Interfaces\SerializerInterface;
use Instagram\SDK\Responses\Serializers\Interfaces\OnDecodeInterface;
use Instagram\SDK\Responses\Traits\ErrorTypeMethodsTrait;
use Psr\Http\Message\ResponseInterface as HttpResponseInterface;

abstract class AbstractSerializer implements SerializerInterface
{

    use ErrorTypeMethodsTrait;

    /**
     * @var string The successful status
     */
    protected const STATUS_SUCCESS = 200;

    /**
     * @var string The error status
     */
    protected const STATUS_ERROR = 400;

    /**
     * Decodes the response message.
     *
     * @param HttpResponseInterface $response
     * @return ResponseMessageInterface
     * @throws ApiResponseException
     * @throws \Exception
     */
    public function decode(HttpResponseInterface $response): ResponseMessageInterface
    {
        if (!$this->isValidHttpResponse($response)) {
            throw new ApiResponseException(new Envelope((string)$response->getBody()));
        }

        // Compose a new message instance
        $message = $this->message();
        $message->mapFromJson((string)$response->getBody());

        // Check whether we retrieved a valid response
        if (!$this->isValidResponse($message)) {
            throw $this->toException($message->getErrorType(), $message);
        }

        $this->finalize($message);

        return $message;
    }

    /**
     * Returns the message implementation.
     *
     * @return Envelope
     */
    abstract protected function message(): ?Envelope;

    /**
     * Check whether we retrieved a successful HTTP response, false otherwise.
     *
     * @param HttpResponseInterface $response
     * @return bool
     */
    protected function isValidHttpResponse(HttpResponseInterface $response): bool
    {
        return $response->getStatusCode() === static::STATUS_SUCCESS ||
            $response->getStatusCode() === static::STATUS_ERROR;
    }

    /**
     * Returns true if we retrieved a valid response, false otherwise.
     *
     * @param Envelope $envelope
     * @return bool
     */
    protected function isValidResponse(Envelope $envelope)
    {
        return $envelope->isSuccess();
    }

    /**
     * The finalize method.
     *
     * @param Envelope $message
     */
    protected function finalize(Envelope $message)
    {
        // Check whether the listener is implemented
        if ($this instanceof OnDecodeInterface) {
            $this->onDecode($message);
        }
    }
}
