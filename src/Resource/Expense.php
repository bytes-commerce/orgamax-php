<?php

declare(strict_types=1);

namespace BytesCommerce\Orgamax\Resource;

use BytesCommerce\Orgamax\Model\Common\ListResponse;
use BytesCommerce\Orgamax\Model\Common\Response;
use BytesCommerce\Orgamax\Transport\JsonCodec;
use BytesCommerce\Orgamax\Transport\RequestBuilder;
use BytesCommerce\Orgamax\Transport\ResponseHandler;
use Webmozart\Assert\Assert;

final readonly class Expense
{
    public function __construct(
        private RequestBuilder $requestBuilder,
        private ResponseHandler $responseHandler,
        private JsonCodec $jsonCodec,
    ) {
    }

    /**
     * `POST /expense/` — createExpense.
     *
     * @param array<string, mixed> $payload
     */
    public function create(array $payload): Response
    {
        $response = $this->responseHandler->send(
            $this->requestBuilder->buildPost('/expense/', $payload),
            expectBody: true,
        );

        return Response::fromArray($this->jsonCodec->decode($response));
    }

    /**
     * `DELETE /expense/{id}` — deleteExpense.
     */
    public function delete(string $id): void
    {
        Assert::stringNotEmpty($id, 'Expense id must not be empty.');

        $this->responseHandler->send(
            $this->requestBuilder->buildDelete('/expense/' . $id),
            expectBody: false,
        );
    }

    /**
     * `GET /expense/{id}` — getExpense.
     */
    public function get(string $id): Response
    {
        Assert::stringNotEmpty($id, 'Expense id must not be empty.');

        $response = $this->responseHandler->send(
            $this->requestBuilder->buildGet('/expense/' . $id, []),
            expectBody: true,
        );

        return Response::fromArray($this->jsonCodec->decode($response));
    }

    /**
     * `PUT /expense/{id}` — updateExpense.
     *
     * @param array<string, mixed> $payload
     */
    public function update(string $id, array $payload): Response
    {
        Assert::stringNotEmpty($id, 'Expense id must not be empty.');

        $response = $this->responseHandler->send(
            $this->requestBuilder->buildPut('/expense/' . $id, $payload),
            expectBody: true,
        );

        return Response::fromArray($this->jsonCodec->decode($response));
    }

    /**
     * `DELETE /expense/receipt/{id}` — deleteExpenseReceipt.
     */
    public function deleteReceipt(string $receiptId): void
    {
        Assert::stringNotEmpty($receiptId, 'Receipt id must not be empty.');

        $this->responseHandler->send(
            $this->requestBuilder->buildDelete('/expense/receipt/' . $receiptId),
            expectBody: false,
        );
    }

    /**
     * `GET /expense` — getExpenses.
     */
    public function list(int $limit = 50, int $offset = 0, string $filter = ''): ListResponse
    {
        $query = [
            'limit' => $limit,
            'offset' => $offset,
        ];
        if ($filter !== '') {
            $query['filter'] = $filter;
        }

        $response = $this->responseHandler->send(
            $this->requestBuilder->buildGet('/expense', $query),
            expectBody: true,
        );

        return ListResponse::fromArray($this->jsonCodec->decode($response));
    }
}
