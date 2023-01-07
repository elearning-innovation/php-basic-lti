<?php

declare(strict_types=1);

namespace Oscelot\Lti;

/**
 * Class to represent a tool consumer context share
 *
 * @deprecated Use ResourceLinkShare instead
 * @see ResourceLinkShare
 */
class ContextShare extends ResourceLinkShare
{
    /**
     * Context ID value.
     *
     * @deprecated Use ResourceLink_Share->resource_link_id instead
     * @see ResourceLink_Share::$resource_link_id
     */
    public mixed $context_id = null;

    /**
     * Class constructor.
     */
    public function __construct()
    {
        parent::__construct();
        /** @noinspection PhpDeprecationInspection */
        $this->context_id = &$this->resource_link_id;
    }
}
