<?php

declare(strict_types=1);

namespace BytesCommerce\Orgamax\Tests\Fixtures\TestDouble;

use Override;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Throwable;

final class FakeHttpClient implements ClientInterface
{
    /**
     * @var list<RequestInterface>
     */
    public array $sentRequests = [];

    /**
     * @var list<ResponseInterface|Throwable>
     */
    private array $queue = [];

    public function enqueue(ResponseInterface|Throwable $responseOrThrowable): void
    {
        $this->queue[] = $responseOrThrowable;
    }

    #[Override]
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->sentRequests[] = $request;
        $next = array_shift($this->queue);
        if ($next === null) {
            throw new RuntimeException('FakeHttpClient queue is empty — call enqueue() first.');
        }
        if ($next instanceof Throwable) {
            if (! $next instanceof ClientExceptionInterface) {
                throw new RuntimeException(
                    sprintf('FakeHttpClient can only re-throw ClientExceptionInterface, got %s', $next::class),
                    0,
                    $next,
                );
            }
            throw $next;
        }

        return $next;
    }
}
