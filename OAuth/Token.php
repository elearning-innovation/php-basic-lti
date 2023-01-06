<?php

declare(strict_types=1);

namespace Oscelot\OAuth;

class Token
{
    /**
     * key = the token
     * secret = the token secret
     */
    public function __construct(
        public string $key,
        public string $secret
    ) {
    }

    /**
     * To String
     *
     * Generates the basic string serialization of a token that a server
     * would respond to request_token and access_token calls with.
     */
    public function __toString()
    {
        return 'oauth_token='
            . OAuthUtil::urlencode_rfc3986($this->key)
            . '&oauth_token_secret='
            . OAuthUtil::urlencode_rfc3986($this->secret);
    }
}
