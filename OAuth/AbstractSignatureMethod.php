<?php

namespace Oscelot\OAuth;

/**
 * A class for implementing a Signature Method
 * See section 9 ("Signing Requests") in the spec
 *
 * phpcs:disable PSR1.Methods.CamelCapsMethodName
 */
abstract class AbstractSignatureMethod
{
    /**
     * Needs to return the name of the Signature Method (ie HMAC-SHA1)
     * @return string
     */
    abstract public function get_name(): string;

    /**
     * Build up the signature
     * NOTE: The output of this function MUST NOT be urlencoded.
     * the encoding is handled in OAuthRequest when the final
     * request is serialized
     *
     * @param Request  $request
     * @param Consumer $consumer
     * @param Token    $token
     *
     * @return string
     */
    abstract public function build_signature(
        Request $request,
        Consumer $consumer,
        Token $token
    ): string;

    /**
     * Verifies that a given signature is correct
     *
     * @param Request  $request
     * @param Consumer $consumer
     * @param Token    $token
     * @param string   $signature
     *
     * @return bool
     */
    public function check_signature(
        Request $request,
        Consumer $consumer,
        Token $token,
        string $signature
    ): bool {
        $built = $this->build_signature($request, $consumer, $token);

        // Check for zero length, although unlikely here
        if (strlen($built) === 0 || strlen($signature) === 0) {
            return false;
        }

        if (strlen($built) !== strlen($signature)) {
            return false;
        }

        // Avoid a timing leak with a (hopefully) time insensitive compare
        $result = 0;
        for ($i = 0; $i < strlen($signature); $i++) {
            $result |= ord($built[$i]) ^ ord($signature[$i]);
        }

        return $result === 0;
    }
}
