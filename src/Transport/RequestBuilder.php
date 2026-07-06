<?php

declare(strict_types=1);

namespace BytesCommerce\Orgamax\Transport;

use BytesCommerce\Orgamax\Auth\OrgaMaxTokenProvider;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

final readonly class RequestBuilder
{
    public function __construct(
        private RequestFactoryInterface $requestFactory,
        private StreamFactoryInterface $streamFactory,
        private OrgaMaxTokenProvider $tokenProvider,
        private string $baseUrl,
    ) {
    }

    public function baseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * @param array<string, scalar|null> $query
     */
    public function buildGet(string $path, array $query): RequestInterface
    {
        $request = $this->requestFactory
            ->createRequest('GET', $this->urlWithQuery($path, $query))
            ->withHeader('Accept', 'application/json');

        return $this->withBearer($request);
    }

    /**
     * @param array<string, mixed> $body
     */
    public function buildPost(string $path, array $body): RequestInterface
    {
        $request = $this->requestFactory
            ->createRequest('POST', $this->baseUrl . $path)
            ->withHeader('Accept', 'application/json')
            ->withHeader('Content-Type', 'application/json');

        $json = (string) json_encode($body, \JSON_THROW_ON_ERROR);
        $request = $request->withBody($this->streamFactory->createStream($json));

        return $this->withBearer($request);
    }

    /**
     * @param array<string, mixed> $body
     */
    public function buildPut(string $path, array $body): RequestInterface
    {
        $request = $this->requestFactory
            ->createRequest('PUT', $this->baseUrl . $path)
            ->withHeader('Accept', 'application/json')
            ->withHeader('Content-Type', 'application/json');

        $json = (string) json_encode($body, \JSON_THROW_ON_ERROR);
        $request = $request->withBody($this->streamFactory->createStream($json));

        return $this->withBearer($request);
    }

    public function buildDelete(string $path): RequestInterface
    {
        $request = $this->requestFactory
            ->createRequest('DELETE', $this->baseUrl . $path)
            ->withHeader('Accept', 'application/json');

        return $this->withBearer($request);
    }

    /**
     * @param array<string, scalar|null> $query
     */
    public function buildDeleteWithBody(string $path, array $query): RequestInterface
    {
        $request = $this->requestFactory
            ->createRequest('DELETE', $this->urlWithQuery($path, $query))
            ->withHeader('Accept', 'application/json');

        return $this->withBearer($request);
    }

    private function withBearer(RequestInterface $request): RequestInterface
    {
        $token = $this->tokenProvider->bearerToken();

        return $request->withHeader('Authorization', 'Bearer ' . $token);
    }

    /**
     * @param array<string, scalar|null> $query
     */
    private function urlWithQuery(string $path, array $query): string
    {
        $url = $this->baseUrl . $path;
        if ($query === []) {
            return $url;
        }

        $filtered = [];
        foreach ($query as $key => $value) {
            if ($value === null) {
                continue;
            }
            $filtered[(string) $key] = is_bool($value) ? ($value ? 'true' : 'false') : (string) $value;
        }

        if ($filtered === []) {
            return $url;
        }

        return $url . '?' . http_build_query($filtered, '', '&', \PHP_QUERY_RFC3986);
    }
}
