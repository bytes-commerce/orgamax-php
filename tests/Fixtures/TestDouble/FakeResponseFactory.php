<?php

declare(strict_types=1);

namespace BytesCommerce\Orgamax\Tests\Fixtures\TestDouble;

use Override;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

final class FakeResponseFactory implements ResponseFactoryInterface
{
    #[Override]
    public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        return new FakeResponse($code, $reasonPhrase);
    }
}
