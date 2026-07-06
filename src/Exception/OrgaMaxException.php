<?php

declare(strict_types=1);

namespace BytesCommerce\Orgamax\Exception;

use JsonException;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Throwable;

abstract class OrgaMaxException extends RuntimeException
{
    private readonly int $httpStatusCode;

    /**
     * @param array<string, mixed>  $responseBody
     * @param array<string, string> $responseHeaders
     */
    public function __construct(
        string $message,
        int $httpStatusCode,
        private readonly array $responseBody,
        private readonly array $responseHeaders,
        Throwable $previous,
    ) {
        parent::__construct($message, $httpStatusCode, $previous);
        $this->httpStatusCode = $httpStatusCode;
    }

    final public static function fromResponse(ResponseInterface $response): self
    {
        return self::buildFromResponse($response, self::noPreviousThrowable());
    }

    final public static function fromResponseWithThrowable(ResponseInterface $response, Throwable $previous): self
    {
        return self::buildFromResponse($response, $previous);
    }

    final public function httpStatusCode(): int
    {
        return $this->httpStatusCode;
    }

    /**
     * @return array<string, mixed>
     */
    final public function responseBody(): array
    {
        return $this->responseBody;
    }

    /**
     * @return array<string, string>
     */
    final public function responseHeaders(): array
    {
        return $this->responseHeaders;
    }

    private static function buildFromResponse(ResponseInterface $response, Throwable $previous): self
    {
        $body = (string) $response->getBody();
        $decoded = self::safeDecode($body);
        $headers = self::normaliseHeaders($response->getHeaders());
        $status = $response->getStatusCode();
        $message = self::extractMessage($decoded);
        if ($message === '') {
            $message = sprintf('OrgaMax API returned HTTP %d', $status);
        }

        return match (true) {
            $status === 401 => new AuthenticationException($message, $status, $decoded, $headers, $previous),
            $status === 404 => new NotFoundException($message, $status, $decoded, $headers, $previous),
            $status === 422 => new ValidationException($message, $status, $decoded, $headers, $previous),
            $status === 429 => new RateLimitException($message, $status, $decoded, $headers, $previous),
            $status >= 500 => new ServerException($message, $status, $decoded, $headers, $previous),
            default => new OrgaMaxGenericException($message, $status, $decoded, $headers, $previous),
        };
    }

    private static function noPreviousThrowable(): Throwable
    {
        return new NoPreviousThrowable();
    }

    /**
     * @return array<string, mixed>
     */
    private static function safeDecode(string $body): array
    {
        if ($body === '') {
            return [];
        }

        try {
            /** @var mixed $decoded */
            $decoded = json_decode($body, true, 32, \JSON_THROW_ON_ERROR);
            if (! is_array($decoded)) {
                return [];
            }

            return self::stringifyKeysRecursive($decoded);
        } catch (JsonException) {
            return [];
        }
    }

    /**
     * @param array<int|string, mixed> $value
     *
     * @return array<string, mixed>
     */
    private static function stringifyKeysRecursive(array $value): array
    {
        $out = [];
        foreach ($value as $key => $v) {
            $stringKey = is_string($key) ? $key : (string) $key;
            $out[$stringKey] = is_array($v) ? self::stringifyKeysRecursive($v) : $v;
        }

        return $out;
    }

    /**
     * @param array<int|string, array<int, string>> $headers
     *
     * @return array<string, string>
     */
    private static function normaliseHeaders(array $headers): array
    {
        $out = [];
        foreach ($headers as $name => $values) {
            $out[(string) $name] = $values[0] ?? '';
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $body
     */
    private static function extractMessage(array $body): string
    {
        foreach (['message', 'error', 'title', 'detail'] as $key) {
            if (array_key_exists($key, $body) && is_string($body[$key]) && $body[$key] !== '') {
                return $body[$key];
            }
        }

        return '';
    }
}
