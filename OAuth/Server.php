<?php

/** @noinspection InArrayMissUseInspection */

declare(strict_types=1);

namespace Oscelot\OAuth;

/**
 * Server
 *
 * phpcs:disable PSR1.Methods.CamelCapsMethodName
 */
class Server
{
    protected int $timestamp_threshold = 300; // in seconds, five minutes
    protected string $version             = '1.0';             // hi blaine
    protected array $signature_methods   = [];

    protected mixed $data_store;

    public function __construct(mixed $data_store)
    {
        $this->data_store = $data_store;
    }

    /**
     * Add signature method
     */
    public function add_signature_method($signature_method): void
    {
        $this->signature_methods[$signature_method->get_name()] =
            $signature_method;
    }

    /**
     * Fetch request token
     *
     * process a request_token request
     * returns the request token on success
     *
     * @throws OscelotOAuthException
     */
    public function fetch_request_token(&$request)
    {
        $this->get_version($request);

        $consumer = $this->get_consumer($request);

        // no token required for the initial token request
        $token = null;

        $this->check_signature($request, $consumer, $token);

        // Rev A change
        $callback = $request->get_parameter('oauth_callback');
        return $this->data_store->new_request_token($consumer, $callback);
    }

    /**
     * Fetch access token
     *
     * process an access_token request
     * returns the access token on success
     *
     * @throws OscelotOAuthException
     */
    public function fetch_access_token(&$request)
    {
        $this->get_version($request);

        $consumer = $this->get_consumer($request);

        // requires authorized request token
        $token = $this->get_token($request, $consumer, 'request');

        $this->check_signature($request, $consumer, $token);

        // Rev A change
        $verifier = $request->get_parameter('oauth_verifier');
        return $this->data_store->new_access_token($token, $consumer, $verifier);
    }

    /**
     * Verify request
     *
     * Verify an api call, checks all the parameters.
     *
     * @throws OscelotOAuthException
     */
    public function verify_request(&$request): array
    {
        $this->get_version($request);
        $consumer = $this->get_consumer($request);
        $token    = $this->get_token($request, $consumer, 'access');
        $this->check_signature($request, $consumer, $token);
        return [$consumer, $token];
    }

    /**
     * Get version
     *
     * Version 1.
     *
     * @throws OscelotOAuthException
     * @noinspection PhpParameterByRefIsNotUsedAsReferenceInspection
     */
    private function get_version(&$request): void
    {
        $version = $request->get_parameter('oauth_version');
        if (! $version) {
            // Service Providers MUST assume the protocol version to be 1.0 if
            // this parameter is not present. Chapter 7.0 ("Accessing Protected
            // Resources").
            $version = '1.0';
        }
        if ($version !== $this->version) {
            throw new OscelotOAuthException("OAuth version '$version' not supported");
        }
    }

    /**
     * Get signature method
     *
     * Figure out the signature with some defaults.
     *
     * @throws OscelotOAuthException
     */
    private function get_signature_method($request)
    {
        $signature_method = $request instanceof Request
            ? $request->get_parameter('oauth_signature_method')
            : null;

        if (! $signature_method) {
            /**
             * According to chapter 7 ("Accessing Protected Resources") the
             * signature-method parameter is required, and we can't just
             * fallback to PLAINTEXT.
             */
            throw new OscelotOAuthException(
                'No signature method parameter. This parameter is required'
            );
        }

        if (! in_array(
            $signature_method,
            array_keys($this->signature_methods)
        )) {
            throw new OscelotOAuthException(
                "Signature method '$signature_method' not supported "
                . 'try one of the following: '
                . implode(', ', array_keys($this->signature_methods))
            );
        }
        return $this->signature_methods[$signature_method];
    }

    /**
     * Get consumer
     *
     * Try to find the consumer for the provided request's consumer key.
     *
     * @throws OscelotOAuthException
     */
    private function get_consumer($request)
    {
        $consumer_key = $request instanceof Request
            ? $request->get_parameter('oauth_consumer_key')
            : null;

        if (! $consumer_key) {
            throw new OscelotOAuthException('Invalid consumer key');
        }

        $consumer = $this->data_store->lookup_consumer($consumer_key);
        if (! $consumer) {
            throw new OscelotOAuthException('Invalid consumer');
        }

        return $consumer;
    }

    /**
     * Get token
     *
     * Try to find the token for the provided request's token key.
     *
     * @throws OscelotOAuthException
     */
    private function get_token($request, $consumer, $token_type = 'access')
    {
        $token_field = $request instanceof Request
            ? $request->get_parameter('oauth_token')
            : null;

        $token = $this->data_store->lookup_token(
            $consumer,
            $token_type,
            $token_field
        );
        if (! $token) {
            throw new OscelotOAuthException(
                "Invalid $token_type token: $token_field"
            );
        }
        return $token;
    }

    /**
     * Check signature
     *
     * all-in-one function to check the signature on a request
     * should guess the signature method appropriately
     *
     * @throws OscelotOAuthException
     */
    private function check_signature($request, $consumer, $token): void
    {
        // this should probably be in a different method
        $timestamp = $request instanceof Request
            ? $request->get_parameter('oauth_timestamp')
            : null;
        $nonce     = $request instanceof Request
            ? $request->get_parameter('oauth_nonce')
            : null;

        $this->check_timestamp($timestamp);
        $this->check_nonce($consumer, $token, $nonce, $timestamp);

        $signature_method = $this->get_signature_method($request);

        $signature = $request->get_parameter('oauth_signature');
        $valid_sig = $signature_method->check_signature(
            $request,
            $consumer,
            $token,
            $signature
        );
    }

    /**
     * Check timestamp
     *
     * Check that the timestamp is new enough.
     *
     * @throws OscelotOAuthException
     */
    private function check_timestamp($timestamp): void
    {
        if (! $timestamp) {
            throw new OscelotOAuthException(
                'Missing timestamp parameter. The parameter is required'
            );
        }

        // Verify that timestamp is recent-ish.
        $now = time();
        if (abs($now - $timestamp) > $this->timestamp_threshold) {
            throw new OscelotOAuthException(
                "Expired timestamp, yours $timestamp, ours $now"
            );
        }
    }

    /**
     * Check nonce
     *
     * Check that the nonce is not repeated.
     *
     * @throws OscelotOAuthException
     */
    private function check_nonce($consumer, $token, $nonce, $timestamp): void
    {
        if (! $nonce) {
            throw new OscelotOAuthException(
                'Missing nonce parameter. The parameter is required'
            );
        }

        // Verify that the nonce is unique-ish.
        $found = $this->data_store->lookup_nonce(
            $consumer,
            $token,
            $nonce,
            $timestamp
        );
        if ($found) {
            throw new OscelotOAuthException("Nonce already used: $nonce");
        }
    }
}
