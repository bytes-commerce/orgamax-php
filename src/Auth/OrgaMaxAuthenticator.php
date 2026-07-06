<?php

declare(strict_types=1);

namespace BytesCommerce\Orgamax\Auth;

use BytesCommerce\Orgamax\Exception\AuthenticationException;
use BytesCommerce\Orgamax\Exception\NoPreviousThrowable;
use BytesCommerce\Orgamax\Exception\OrgaMaxException;
use BytesCommerce\Orgamax\Exception\TransportException;
use DateTimeImmutable;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Webmozart\Assert\Assert;

final readonly class OrgaMaxAuthenticator
{
    public function __construct(
        private string $apiKey,
        private string $apiSecret,
        private ClientInterface $httpClient,
        private RequestFactoryInterface $requestFactory,
        private StreamFactoryInterface $streamFactory,
        private string $baseUrl,
    ) {
        Assert::stringNotEmpty($apiKey, 'API key must not be empty.');
        Assert::stringNotEmpty($apiSecret, 'API secret must not be empty.');
    }

    public function exchangeOwnershipId(string $ownershipId): Token
    {
        Assert::stringNotEmpty($ownershipId, 'Ownership id must not be empty.');

        $json = (string) json_encode([
            'ownershipId' => $ownershipId,
        ], \JSON_THROW_ON_ERROR);
        $request = $this->requestFactory
            ->createRequest('POST', $this->baseUrl . '/auth/token')
            ->withHeader('Accept', 'application/json')
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Authorization', 'Basic ' . base64_encode($this->apiKey . ':' . $this->apiSecret))
            ->withBody($this->streamFactory->createStream($json));

        try {
            $response = $this->httpClient->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            throw new TransportException(
                sprintf('OrgaMax auth request failed: %s', $e->getMessage()),
                0,
                [],
                [],
                $e,
            );
        }

        $status = $response->getStatusCode();
        if ($status < 200 || $status >= 300) {
            throw OrgaMaxException::fromResponse($response);
        }

        $payload = json_decode((string) $response->getBody(), true);
        if (! is_array($payload)) {
            throw new AuthenticationException('Auth response did not contain a token.', $status, [], [], new NoPreviousThrowable());
        }
        $rawToken = $payload['token'] ?? null;
        if (! is_string($rawToken) || $rawToken === '') {
            throw new AuthenticationException('Auth response did not contain a token.', $status, [], [], new NoPreviousThrowable());
        }

        return new Token(
            bearerToken: $rawToken,
            expiresAt: new DateTimeImmutable('+24 hours'),
        );
    }
}
