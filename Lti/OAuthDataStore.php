<?php

declare(strict_types=1);

namespace Oscelot\Lti;

use Oscelot\OAuth\AbstractDataStore;
use Oscelot\OAuth\Consumer;
use Oscelot\OAuth\Token;

/**
 * Class to represent an OAuth datastore
 *
 * phpcs:disable PSR1.Methods.CamelCapsMethodName
 */
class OAuthDataStore extends AbstractDataStore
{
    /**
     * ToolProvider object.
     */
    private ?ToolProvider $tool_provider;

    public function __construct(ToolProvider $tool_provider)
    {
        $this->tool_provider = $tool_provider;
    }

    /**
     * Create an OAuthConsumer object for the tool consumer.
     *
     * @param string $consumer_key Consumer key value.
     * @return Consumer OAuthConsumer object.
     */
    public function lookup_consumer(string $consumer_key): Consumer
    {
        return new Consumer(
            $this->tool_provider->consumer->getKey(),
            $this->tool_provider->consumer->secret
        );
    }

    /**
     * Create an OAuthToken object for the tool consumer.
     *
     * @param Consumer $consumer   OAuthConsumer object.
     * @param string   $token_type Token type.
     * @param ?string   $token      Token value.
     * @return Token OAuthToken object.
     */
    public function lookup_token(
        Consumer $consumer,
        string $token_type,
        ?string $token
    ): Token {
        return new Token($consumer, "");
    }

    /**
     * Lookup nonce value for the tool consumer.
     *
     * @param Consumer $consumer  OAuthConsumer object.
     * @param Token   $token     Token value.
     * @param ?string   $value     Nonce value.
     * @param string   $timestamp Date/time of request.
     * @return bool True if the nonce value already exists.
     */
    public function lookup_nonce(
        Consumer $consumer,
        Token $token,
        ?string $value,
        string $timestamp
    ): bool {
        $nonce = new ConsumerNonce($this->tool_provider->consumer, $value);
        $ok = !$nonce->load();
        if ($ok) {
            $ok = $nonce->save();
        }
        if (!$ok) {
            $this->tool_provider->reason = 'Invalid nonce.';
        }

        return !$ok;
    }

    /**
     * Get new request token.
     *
     * @param Consumer $consumer OAuthConsumer object.
     * @param ?string  $callback Callback URL.
     */
    public function new_request_token(
        Consumer $consumer,
        $callback = null
    ): ?Token {
        return null;
    }

    /**
     * Get new access token.
     *
     * @param string|Token $token    Token value.
     * @param Consumer     $consumer OAuthConsumer object.
     * @param null         $verifier Verification code.
     */
    public function new_access_token(
        string|Token $token,
        Consumer $consumer,
        $verifier = null
    ): ?Token {
        return null;
    }
}
