<?php

declare(strict_types=1);

namespace BytesCommerce\Orgamax\Tests\Fixtures\TestDouble;

use Override;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Minimal PSR-7 Response implementation for tests — no external PSR-7 library needed.
 *
 * @phpstan-ignore-next-line ergebnis.noConstructorParameterWithDefaultValue
 * @phpstan-ignore-next-line ergebnis.noParameterWithNullableTypeDeclaration
 */
final class FakeResponse implements ResponseInterface
{
    /**
     * @var array<string, string>
     */
    private array $headers = [];

    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        private int $statusCode,
        private string $reasonPhrase = '',
        array $headers = [],
        private string $body = '',
    ) {
        foreach ($headers as $name => $value) {
            $this->headers[strtolower((string) $name)] = $value;
        }
    }

    #[Override]
    public function withStatus(int $code, string $reasonPhrase = ''): ResponseInterface
    {
        $clone = clone $this;
        $clone->statusCode = $code;
        $clone->reasonPhrase = $reasonPhrase;

        return $clone;
    }

    #[Override]
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    #[Override]
    public function getReasonPhrase(): string
    {
        return $this->reasonPhrase;
    }

    #[Override]
    public function getBody(): StreamInterface
    {
        return new FakeStream($this->body);
    }

    #[Override]
    public function withBody(StreamInterface $body): MessageInterface
    {
        $clone = clone $this;
        $clone->body = (string) $body;

        return $clone;
    }

    #[Override]
    public function getProtocolVersion(): string
    {
        return '1.1';
    }

    #[Override]
    public function withProtocolVersion(string $version): MessageInterface
    {
        return clone $this;
    }

    /**
     * @return array<string, array<string>>
     */
    #[Override]
    public function getHeaders(): array
    {
        $out = [];
        foreach ($this->headers as $name => $value) {
            $out[$name] = [$value];
        }

        return $out;
    }

    #[Override]
    public function hasHeader(string $name): bool
    {
        return array_key_exists(strtolower($name), $this->headers);
    }

    /**
     * @return array<string>
     */
    #[Override]
    public function getHeader(string $name): array
    {
        $key = strtolower($name);
        if (! array_key_exists($key, $this->headers)) {
            return [];
        }
        $value = $this->headers[$key];

        return $value === '' ? [] : [$value];
    }

    #[Override]
    public function getHeaderLine(string $name): string
    {
        $key = strtolower($name);

        return array_key_exists($key, $this->headers) ? $this->headers[$key] : '';
    }

    #[Override]
    public function withHeader(string $name, $value): MessageInterface
    {
        $clone = clone $this;
        $clone->headers[strtolower($name)] = is_array($value) ? ($value[0] ?? '') : $value;

        return $clone;
    }

    #[Override]
    public function withAddedHeader(string $name, $value): MessageInterface
    {
        return $this->withHeader($name, $value);
    }

    #[Override]
    public function withoutHeader(string $name): MessageInterface
    {
        $clone = clone $this;
        unset($clone->headers[strtolower($name)]);

        return $clone;
    }
}
