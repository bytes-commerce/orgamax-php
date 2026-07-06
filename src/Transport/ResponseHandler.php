<?php

declare(strict_types=1);

namespace BytesCommerce\Orgamax\Transport;

use BytesCommerce\Orgamax\Exception\OrgaMaxException;
use BytesCommerce\Orgamax\Exception\TransportException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final readonly class ResponseHandler
{
    public function __construct(
        private ClientInterface $httpClient,
    ) {
    }

    /**
     * @param bool $expectBody Whether the caller expects a JSON body in the
     *                         response. Used as a soft signal for endpoints
     *                         such as DELETE that return 204 No Content.
     *
     * @throws OrgaMaxException
     * @throws TransportException
     */
    public function send(RequestInterface $request, bool $expectBody = true): ResponseInterface
    {
        try {
            $response = $this->httpClient->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            throw new TransportException(
                sprintf('HTTP transport failed: %s', $e->getMessage()),
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

        return $response;
    }
}
