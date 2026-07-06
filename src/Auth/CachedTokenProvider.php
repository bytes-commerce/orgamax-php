<?php

declare(strict_types=1);

namespace BytesCommerce\Orgamax\Auth;

use DateTimeImmutable;
use Override;
use Psr\SimpleCache\CacheInterface;
use Webmozart\Assert\Assert;

final readonly class CachedTokenProvider implements OrgaMaxTokenProvider
{
    private string $cacheKey;

    public function __construct(
        private OrgaMaxAuthenticator $authenticator,
        private string $ownershipId,
        private CacheInterface $cache,
        private int $ttlSeconds,
    ) {
        Assert::stringNotEmpty($ownershipId, 'Ownership id must not be empty.');
        Assert::positiveInteger($ttlSeconds, 'TTL seconds must be positive.');
        $this->cacheKey = sprintf('orgamax.token.%s', $ownershipId);
    }

    public static function withDefaultTtl(
        OrgaMaxAuthenticator $authenticator,
        string $ownershipId,
        CacheInterface $cache,
    ): self {
        return new self($authenticator, $ownershipId, $cache, 86400);
    }

    #[Override]
    public function bearerToken(): string
    {
        $cached = $this->cache->get($this->cacheKey);
        if (
            is_array($cached)
            && array_key_exists('token', $cached)
            && is_string($cached['token'])
        ) {
            $rawExpiresAt = $cached['expires_at'] ?? '';
            if (is_string($rawExpiresAt) && $rawExpiresAt !== '') {
                $expiresAt = new DateTimeImmutable($rawExpiresAt);
                if ($expiresAt > new DateTimeImmutable()) {
                    return $cached['token'];
                }
            }
        }

        $token = $this->authenticator->exchangeOwnershipId($this->ownershipId);
        $this->cache->set(
            $this->cacheKey,
            [
                'token' => $token->bearerToken,
                'expires_at' => $token->expiresAt->format(\DATE_ATOM),
            ],
            $this->ttlSeconds,
        );

        return $token->bearerToken;
    }

    #[Override]
    public function invalidate(): void
    {
        $this->cache->delete($this->cacheKey);
    }
}
