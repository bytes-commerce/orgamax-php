<?php

declare(strict_types=1);

namespace BytesCommerce\Orgamax\Resource;

use BytesCommerce\Orgamax\Model\Common\Response;
use BytesCommerce\Orgamax\Transport\JsonCodec;
use BytesCommerce\Orgamax\Transport\RequestBuilder;
use BytesCommerce\Orgamax\Transport\ResponseHandler;

final readonly class Bookkeeping
{
    public function __construct(
        private RequestBuilder $requestBuilder,
        private ResponseHandler $responseHandler,
        private JsonCodec $jsonCodec,
    ) {
    }

    /**
     * `GET /bookkeeping/getchartofaccounts` — bookkeeping (chart of accounts).
     */
    public function chartOfAccounts(): Response
    {
        $response = $this->responseHandler->send(
            $this->requestBuilder->buildGet('/bookkeeping/getchartofaccounts', []),
            expectBody: true,
        );

        return Response::fromArray($this->jsonCodec->decode($response));
    }
}
