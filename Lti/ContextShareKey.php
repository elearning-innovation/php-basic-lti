<?php

declare(strict_types=1);

namespace Oscelot\Lti;

/**
 * Class to represent a tool consumer context share key
 *
 * @deprecated Use ResourceLinkShareKey instead
 * @see ResourceLinkShareKey
 */
class ContextShareKey extends ResourceLinkShareKey
{
    /**
     * ID for context being shared.
     *
     * @deprecated Use ResourceLinkShareKey->primary_resource_link_id instead
     * @see ResourceLinkShareKey::$primary_resource_link_id
     */
    public mixed $primary_context_id = null;

    /**
     * Class constructor.
     *
     * @param ResourceLink $resource_link Resource_Link object
     * @param string       $id            Value of share key (optional, default is null)
     */
    public function __construct(ResourceLink $resource_link, $id = null)
    {
        parent::__construct($resource_link, $id);
        /** @noinspection PhpDeprecationInspection */
        $this->primary_context_id = &$this->primary_resource_link_id;
    }
}
