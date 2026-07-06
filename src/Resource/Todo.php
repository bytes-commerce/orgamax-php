<?php

declare(strict_types=1);

namespace BytesCommerce\Orgamax\Resource;

use BytesCommerce\Orgamax\Model\Common\ListResponse;
use BytesCommerce\Orgamax\Model\Common\Response;
use BytesCommerce\Orgamax\Transport\JsonCodec;
use BytesCommerce\Orgamax\Transport\RequestBuilder;
use BytesCommerce\Orgamax\Transport\ResponseHandler;
use Webmozart\Assert\Assert;

final readonly class Todo
{
    public function __construct(
        private RequestBuilder $requestBuilder,
        private ResponseHandler $responseHandler,
        private JsonCodec $jsonCodec,
    ) {
    }

    /**
     * `POST /todo/` — createTodo.
     *
     * @param array<string, mixed> $payload
     */
    public function create(array $payload): Response
    {
        $response = $this->responseHandler->send(
            $this->requestBuilder->buildPost('/todo/', $payload),
            expectBody: true,
        );

        return Response::fromArray($this->jsonCodec->decode($response));
    }

    /**
     * `POST /todo/{id}/message` — createTodoMessage.
     *
     * @param array<string, mixed> $payload
     */
    public function addMessage(string $id, array $payload): Response
    {
        Assert::stringNotEmpty($id, 'Todo id must not be empty.');

        $response = $this->responseHandler->send(
            $this->requestBuilder->buildPost('/todo/' . $id . '/message', $payload),
            expectBody: true,
        );

        return Response::fromArray($this->jsonCodec->decode($response));
    }

    /**
     * `DELETE /todo/{id}` — deleteToDo.
     */
    public function delete(string $id): void
    {
        Assert::stringNotEmpty($id, 'Todo id must not be empty.');

        $this->responseHandler->send(
            $this->requestBuilder->buildDelete('/todo/' . $id),
            expectBody: false,
        );
    }

    /**
     * `GET /todo/{id}` — getTodoMessages.
     */
    public function messages(string $id): Response
    {
        Assert::stringNotEmpty($id, 'Todo id must not be empty.');

        $response = $this->responseHandler->send(
            $this->requestBuilder->buildGet('/todo/' . $id, []),
            expectBody: true,
        );

        return Response::fromArray($this->jsonCodec->decode($response));
    }

    /**
     * `PUT /todo/{id}` — setDate.
     *
     * @param array<string, mixed> $payload
     */
    public function setDate(string $id, array $payload): Response
    {
        Assert::stringNotEmpty($id, 'Todo id must not be empty.');

        $response = $this->responseHandler->send(
            $this->requestBuilder->buildPut('/todo/' . $id, $payload),
            expectBody: true,
        );

        return Response::fromArray($this->jsonCodec->decode($response));
    }

    /**
     * `POST /todo/message/{id}` — deleteTodoMessage.
     *
     * The OpenAPI uses POST for message deletion (legacy). Pass the message id.
     *
     * @param array<string, mixed> $payload
     */
    public function deleteMessage(string $messageId, array $payload = []): void
    {
        Assert::stringNotEmpty($messageId, 'Message id must not be empty.');

        $this->responseHandler->send(
            $this->requestBuilder->buildPost('/todo/message/' . $messageId, $payload),
            expectBody: false,
        );
    }

    /**
     * `GET /todo` — getTodos.
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
            $this->requestBuilder->buildGet('/todo', $query),
            expectBody: true,
        );

        return ListResponse::fromArray($this->jsonCodec->decode($response));
    }

    /**
     * `PUT /todo/{id}/link` — linkToTodo.
     *
     * @param array<string, mixed> $payload
     */
    public function link(string $id, array $payload): Response
    {
        Assert::stringNotEmpty($id, 'Todo id must not be empty.');

        $response = $this->responseHandler->send(
            $this->requestBuilder->buildPut('/todo/' . $id . '/link', $payload),
            expectBody: true,
        );

        return Response::fromArray($this->jsonCodec->decode($response));
    }

    /**
     * `PUT /todo/{id}/unlink` — unlinkToTodo.
     *
     * @param array<string, mixed> $payload
     */
    public function unlink(string $id, array $payload): Response
    {
        Assert::stringNotEmpty($id, 'Todo id must not be empty.');

        $response = $this->responseHandler->send(
            $this->requestBuilder->buildPut('/todo/' . $id . '/unlink', $payload),
            expectBody: true,
        );

        return Response::fromArray($this->jsonCodec->decode($response));
    }

    /**
     * `PUT /todo/{id}/status` — setTodoStatus.
     *
     * @param array<string, mixed> $payload
     */
    public function setStatus(string $id, array $payload): Response
    {
        Assert::stringNotEmpty($id, 'Todo id must not be empty.');

        $response = $this->responseHandler->send(
            $this->requestBuilder->buildPut('/todo/' . $id . '/status', $payload),
            expectBody: true,
        );

        return Response::fromArray($this->jsonCodec->decode($response));
    }
}
