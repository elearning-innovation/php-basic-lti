<?php

declare(strict_types=1);

namespace Oscelot\Lti;

/**
 * Class to represent a tool consumer resource link share
 */
class ResourceLinkShare
{
    /**
     * Consumer key value.
     */
    public mixed $consumer_key = null;
    /**
     * Resource link ID value.
     */
    public mixed $resource_link_id = null;
    /**
     * Title of sharing context.
     */
    public ?string $title = null;
    /**
     * True if sharing request is to be automatically approved on first use.
     */
    public ?bool $approved = null;

    /**
     * Class constructor.
     */
    public function __construct()
    {
        /**
         * @noinspection PhpDeprecationInspection
         * @noinspection PhpPossiblePolymorphicInvocationInspection
         */
        $this->context_id = &$this->resource_link_id;
    }
}
