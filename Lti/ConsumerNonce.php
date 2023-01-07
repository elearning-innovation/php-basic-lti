<?php

declare(strict_types=1);

namespace Oscelot\Lti;

/**
 * Class to represent a tool consumer nonce
 */
class ConsumerNonce
{
    /**
     * Maximum age nonce values will be retained for (in minutes).
     */
    public const MAX_NONCE_AGE = 30;  // in minutes

    /**
     * Maximum size of a nonce value (in characters)
     */
    public const MAX_NONCE_LENGTH = 32;  // in characters

    /**
     * Date/time when the nonce value expires.
     */
    public int|null|float $expires = null;

    /**
     * ToolConsumer object to which this nonce applies.
     */
    private ?ToolConsumer $consumer;

    /**
     * Nonce value.
     */
    private ?string $value;

    /**
     * Class constructor.
     *
     * @param ToolConsumer $consumer Consumer object.
     * @param ?string $value Nonce value (optional, default is null).
     */
    public function __construct(ToolConsumer $consumer, ?string $value = null)
    {
        $this->consumer = $consumer;
        if (!is_null($value)) {
            if (strlen($value) > self::MAX_NONCE_LENGTH) {
                $v = base64_decode($value);
                if (($v !== false) && !preg_match('/[^\x20-\x7f]/', $v)) {
                    $value = $v;
                }
            }
            if (strlen($value) > self::MAX_NONCE_LENGTH) {
                $value = substr($value, 0, self::MAX_NONCE_LENGTH);
            }
        }
        $this->value = $value;
        $this->expires = time() + (self::MAX_NONCE_AGE * 60);
    }

    /**
     * Load a nonce value from the database.
     *
     * @return bool True if the nonce value was successfully loaded
     */
    public function load(): bool
    {
        return $this->consumer->getDataConnector()->Consumer_Nonce_load($this);
    }

    /**
     * Save a nonce value in the database.
     *
     * @return bool True if the nonce value was successfully saved
     */
    public function save(): bool
    {
        return $this->consumer->getDataConnector()->Consumer_Nonce_save($this);
    }

    /**
     * Get tool consumer.
     *
     * @return ?ToolConsumer Consumer for this nonce.
     */
    public function getConsumer(): ?ToolConsumer
    {
        return $this->consumer;
    }

    /**
     * Get tool consumer key.
     *
     * @return string Consumer key value
     */
    public function getKey(): string
    {
        return $this->consumer->getKey();
    }

    /**
     * Get outcome value.
     *
     * @return ?string Outcome value.
     */
    public function getValue(): ?string
    {
        return $this->value;
    }
}
