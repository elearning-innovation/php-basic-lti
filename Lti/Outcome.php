<?php

declare(strict_types=1);

namespace Oscelot\Lti;

/**
 * Class to represent an outcome
 */
class Outcome
{
    /**
     * Language value.
     */
    public ?string $language = null;

    /**
     * Outcome status value.
     */
    public ?string $status = null;

    /**
     * Outcome date value.
     */
    public ?string $date = null;

    /**
     * Outcome type value.
     */
    public ?string $type = null;

    /**
     * Outcome data source value.
     */
    public ?string $data_source = null;

    /**
     * Result sourcedid.
     *
     * @deprecated Use User object instead.
     */
    private ?string $sourcedid;

    /**
     * Class constructor.
     *
     * @param ?string        $sourcedid Result sourcedid value for the user/resource
     *                                  link (optional, default is to use associated
     *                                  User object).
     * @param int|float|null $value     Outcome value (optional, default is none).
     */
    public function __construct(
        ?string $sourcedid = null,
        private int|float|null $value = null
    ) {
        /** @noinspection PhpDeprecationInspection */
        $this->sourcedid = $sourcedid;
        $this->language = 'en-US';
        $this->date = gmdate('Y-m-d\TH:i:s\Z', time());
        $this->type = 'decimal';
    }

    /**
     * Get the result sourcedid value.
     *
     * @deprecated Use User object instead.
     * @return ?string Result sourcedid value.
     */
    public function getSourcedid(): ?string
    {
        /** @noinspection PhpDeprecationInspection */
        return $this->sourcedid;
    }

    /**
     * Get the outcome value.
     *
     * @return int|float|null Outcome value.
     */
    public function getValue(): int|float|null
    {
        return $this->value;
    }

    /**
     * Set the outcome value.
     *
     * @param int|float|null $value Outcome value.
     */
    public function setValue(int|float|null $value): void
    {
        $this->value = $value;
    }
}
