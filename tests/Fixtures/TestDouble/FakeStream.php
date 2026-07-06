<?php

declare(strict_types=1);

namespace BytesCommerce\Orgamax\Tests\Fixtures\TestDouble;

use Override;
use Psr\Http\Message\StreamInterface;

/**
 * @phpstan-ignore-next-line ergebnis.noConstructorParameterWithDefaultValue
 * @phpstan-ignore-next-line ergebnis.noParameterWithNullableTypeDeclaration
 */
final readonly class FakeStream implements StreamInterface
{
    public function __construct(
        private string $body = '',
    ) {
    }

    #[Override]
    public function __toString(): string
    {
        return $this->body;
    }

    #[Override]
    public function close(): void
    {
    }

    #[Override]
    public function detach()
    {
        return null;
    }

    #[Override]
    public function getSize(): int
    {
        return strlen($this->body);
    }

    #[Override]
    public function tell(): int
    {
        return 0;
    }

    #[Override]
    public function eof(): bool
    {
        return true;
    }

    #[Override]
    public function isSeekable(): bool
    {
        return false;
    }

    #[Override]
    public function seek(int $offset, int $whence = \SEEK_SET): void
    {
    }

    #[Override]
    public function rewind(): void
    {
    }

    #[Override]
    public function isWritable(): bool
    {
        return false;
    }

    #[Override]
    public function write(string $string): int
    {
        return 0;
    }

    #[Override]
    public function isReadable(): bool
    {
        return true;
    }

    #[Override]
    public function read(int $length): string
    {
        return '';
    }

    #[Override]
    public function getContents(): string
    {
        return $this->body;
    }

    #[Override]
    public function getMetadata(?string $key = null)
    {
        return null;
    }
}
