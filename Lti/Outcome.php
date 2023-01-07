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
     * @deprecated Use User object instead
     */
    private ?string $sourcedid;

    /**
     * Outcome value.
     */
    private int|float|null $value;

    /**
     * Class constructor.
     *
     * @param ?string $sourcedid Result sourcedid value for the user/resource
     *                           link (optional, default is to use associated
     *                           User object).
     * @param ?string $value     Outcome value (optional, default is none).
     */
    public function __construct(
        ?string $sourcedid = null,
        ?string $value = null
    ) {
        $this->sourcedid = $sourcedid;
        $this->value = $value;
        $this->language = 'en-US';
        $this->date = gmdate('Y-m-d\TH:i:s\Z', time());
        $this->type = 'decimal';
    }

    /**
     * Get the result sourcedid value.
     *
     * @deprecated Use User object instead
     *
     * @return ?string Result sourcedid value
     */
    public function getSourcedid(): ?string
    {
        return $this->sourcedid;
    }

    /**
     * Get the outcome value.
     *
     * @return ?string Outcome value
     */
    public function getValue(): ?string
    {
        return $this->value;
    }

    /**
     * Set the outcome value.
     *
     * @param int|float $value Outcome value.
     */
    public function setValue(int|float $value): void
    {
        $this->value = $value;
    }
}
