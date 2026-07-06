<?php

declare(strict_types=1);

namespace BytesCommerce\Orgamax\Resource;

use BytesCommerce\Orgamax\Model\Common\Response;
use BytesCommerce\Orgamax\Transport\JsonCodec;
use BytesCommerce\Orgamax\Transport\RequestBuilder;
use BytesCommerce\Orgamax\Transport\ResponseHandler;

final readonly class Setting
{
    public function __construct(
        private RequestBuilder $requestBuilder,
        private ResponseHandler $responseHandler,
        private JsonCodec $jsonCodec,
    ) {
    }

    /**
     * `GET /setting/account` — getAccountSetting.
     */
    public function account(): Response
    {
        $response = $this->responseHandler->send(
            $this->requestBuilder->buildGet('/setting/account', []),
            expectBody: true,
        );

        return Response::fromArray($this->jsonCodec->decode($response));
    }

    /**
     * `GET /setting/miscellaneous` — articleSettings.
     */
    public function miscellaneous(): Response
    {
        $response = $this->responseHandler->send(
            $this->requestBuilder->buildGet('/setting/miscellaneous', []),
            expectBody: true,
        );

        return Response::fromArray($this->jsonCodec->decode($response));
    }
}
