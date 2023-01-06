<?php

declare(strict_types=1);

namespace Oscelot\OAuth;

class Consumer
{
    public function __construct(
        public string $key,
        public string $secret,
        public ?string $callbackUrl = null
    ) {
    }

    public function __toString()
    {
        return "OAuthConsumer[key=$this->key,secret=$this->secret]";
    }
}
