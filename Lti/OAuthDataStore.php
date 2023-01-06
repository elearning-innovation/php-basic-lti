<?php

declare(strict_types=1);

namespace Oscelot\Lti;

use Oscelot\OAuth\AbstractDataStore;
use Oscelot\OAuth\Consumer;
use Oscelot\OAuth\Token;

/**
 * Class to represent an OAuth datastore
 */
class OAuthDataStore extends AbstractDataStore
{
    /**
     * ToolProvider object.
     */
    private $tool_provider = null;

    /**
     * Class constructor.
     *
     * @param ToolProvider $tool_provider Tool_Provider object
     */
    public function __construct($tool_provider)
    {
        $this->tool_provider = $tool_provider;
    }

    /**
     * Create an OAuthConsumer object for the tool consumer.
     *
     * @param string $consumer_key Consumer key value
     *
     * @return Consumer OAuthConsumer object
     */
    function lookup_consumer($consumer_key)
    {
        return new Consumer(
            $this->tool_provider->consumer->getKey(),
            $this->tool_provider->consumer->secret
        );
    }

    /**
     * Create an OAuthToken object for the tool consumer.
     *
     * @param string $consumer   OAuthConsumer object
     * @param string $token_type Token type
     * @param string $token      Token value
     *
     * @return Token OAuthToken object
     */
    function lookup_token($consumer, $token_type, $token)
    {
        return new Token($consumer, "");
    }

    /**
     * Lookup nonce value for the tool consumer.
     *
     * @param Consumer $consumer  OAuthConsumer object
     * @param string        $token     Token value
     * @param string        $value     Nonce value
     * @param string        $timestamp Date/time of request
     *
     * @return boolean True if the nonce value already exists
     */
    function lookup_nonce($consumer, $token, $value, $timestamp)
    {
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
     * @param Consumer $consumer  OAuthConsumer object
     * @param string        $callback  Callback URL
     */
    function new_request_token($consumer, $callback = null): ?string
    {
        return null;
    }

    /**
     * Get new access token.
     *
     * @param string        $token     Token value
     * @param Consumer $consumer  OAuthConsumer object
     * @param string        $verifier  Verification code
     */
    function new_access_token($token, $consumer, $verifier = null): ?string
    {
        return null;
    }
}