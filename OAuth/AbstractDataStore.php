<?php

declare(strict_types=1);

namespace Oscelot\OAuth;

use Oscelot\Lti\ConsumerNonce;
use Oscelot\Lti\ToolConsumer;

class AbstractDataStore
{
    function lookup_consumer(string $consumer_key)
    {
        // implement me
    }

    function lookup_token(ToolConsumer $consumer, string $token_type, string $token)
    {
        // implement me
    }

    function lookup_nonce(
        ToolConsumer $consumer,
        string $token,
        ConsumerNonce $nonce,
        string $timestamp
    ) {
        // implement me
    }

    function new_request_token(ToolConsumer $consumer, $callback = null): Token
    {
        // return a new token attached to this consumer
    }

    function new_access_token(Token $token, ToolConsumer $consumer, $verifier = null)
    {
        // return a new access token attached to this consumer
        // for the user associated with this token if the request token
        // is authorized
        // should also invalidate the request token
    }
}
