<?php

declare(strict_types=1);

namespace Oscelot\OAuth;

use Oscelot\Lti\ConsumerNonce;
use Oscelot\Lti\ToolConsumer;

abstract class AbstractDataStore
{
    abstract public function lookup_consumer(string $consumer_key);

    abstract public function lookup_token(
        ToolConsumer $consumer,
        string $token_type,
        string $token
    );

    abstract public function lookup_nonce(
        ToolConsumer $consumer,
        string $token,
        ConsumerNonce $nonce,
        string $timestamp
    );

    /**
     * @param ToolConsumer $consumer
     * @param              $callback
     * @return Token A new token attached to this consumer
     */
    abstract public function new_request_token(
        ToolConsumer $consumer,
        $callback = null
    ): Token;

    /**
     * @param Token        $token
     * @param ToolConsumer $consumer
     * @param              $verifier
     *
     * @return Token A new access token attached to this consumer for the user
     *               associated with this token if the request token is
     *               authorized should also invalidate the request token.
     */
    abstract public function new_access_token(
        Token $token,
        ToolConsumer $consumer,
        $verifier = null
    ): Token;
}
