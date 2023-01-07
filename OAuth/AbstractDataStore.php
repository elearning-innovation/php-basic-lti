<?php

declare(strict_types=1);

namespace Oscelot\OAuth;

// phpcs:disable PSR1.Methods.CamelCapsMethodName
abstract class AbstractDataStore
{
    abstract public function lookup_consumer(string $consumer_key);

    abstract public function lookup_token(
        Consumer $consumer,
        string $token_type,
        string $token
    );

    abstract public function lookup_nonce(
        Consumer $consumer,
        Token $token,
        ?string $value, // Nonce value
        string $timestamp
    );

    /**
     * @return ?Token A new token attached to this consumer
     */
    abstract public function new_request_token(
        Consumer $consumer,
        $callback = null
    ): ?Token;

    /**
     * @param null     $verifier
     * @return ?Token A new access token attached to this consumer for the user
     *               associated with this token if the request token is
     *               authorized should also invalidate the request token.
     */
    abstract public function new_access_token(
        Token $token,
        Consumer $consumer,
        $verifier = null
    ): ?Token;
}
