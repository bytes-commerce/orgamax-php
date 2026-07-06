<?php

declare(strict_types=1);

namespace BytesCommerce\Orgamax\Resource;

use BytesCommerce\Orgamax\Model\Common\BinaryResponse;
use BytesCommerce\Orgamax\Model\Common\ListResponse;
use BytesCommerce\Orgamax\Model\Common\Response;
use BytesCommerce\Orgamax\Transport\JsonCodec;
use BytesCommerce\Orgamax\Transport\RequestBuilder;
use BytesCommerce\Orgamax\Transport\ResponseHandler;
use Webmozart\Assert\Assert;

final readonly class File
{
    public function __construct(
        private RequestBuilder $requestBuilder,
        private ResponseHandler $responseHandler,
        private JsonCodec $jsonCodec,
    ) {
    }

    /**
     * `GET /file/{id}/analyze` — analyzeFile (kick off OCR / analysis).
     */
    public function analyze(string $id): Response
    {
        Assert::stringNotEmpty($id, 'File id must not be empty.');

        $response = $this->responseHandler->send(
            $this->requestBuilder->buildGet('/file/' . $id . '/analyze', []),
            expectBody: true,
        );

        return Response::fromArray($this->jsonCodec->decode($response));
    }

    /**
     * `DELETE /file/{id}` — deleteFile.
     */
    public function delete(string $id): void
    {
        Assert::stringNotEmpty($id, 'File id must not be empty.');

        $this->responseHandler->send(
            $this->requestBuilder->buildDelete('/file/' . $id),
            expectBody: false,
        );
    }

    /**
     * `GET /file/{id}` — getDocument (downloads the binary file).
     */
    public function download(string $id): BinaryResponse
    {
        Assert::stringNotEmpty($id, 'File id must not be empty.');

        $response = $this->responseHandler->send(
            $this->requestBuilder->buildGet('/file/' . $id, []),
            expectBody: false,
        );

        return BinaryResponse::fromPsrResponse($response);
    }

    /**
     * `PUT /file/{id}/meta` — editFile metadata.
     *
     * @param array<string, mixed> $payload
     */
    public function updateMeta(string $id, array $payload): Response
    {
        Assert::stringNotEmpty($id, 'File id must not be empty.');

        $response = $this->responseHandler->send(
            $this->requestBuilder->buildPut('/file/' . $id . '/meta', $payload),
            expectBody: true,
        );

        return Response::fromArray($this->jsonCodec->decode($response));
    }

    /**
     * `GET /file/{id}/meta` — fetchFileInfo.
     */
    public function info(string $id): Response
    {
        Assert::stringNotEmpty($id, 'File id must not be empty.');

        $response = $this->responseHandler->send(
            $this->requestBuilder->buildGet('/file/' . $id . '/meta', []),
            expectBody: true,
        );

        return Response::fromArray($this->jsonCodec->decode($response));
    }

    /**
     * `GET /file` — getFiles.
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
            $this->requestBuilder->buildGet('/file', $query),
            expectBody: true,
        );

        return ListResponse::fromArray($this->jsonCodec->decode($response));
    }

    /**
     * `POST /file/upload` — uploadFile.
     *
     * @param array<string, mixed> $payload
     */
    public function upload(array $payload): Response
    {
        $response = $this->responseHandler->send(
            $this->requestBuilder->buildPost('/file/upload', $payload),
            expectBody: true,
        );

        return Response::fromArray($this->jsonCodec->decode($response));
    }
}
