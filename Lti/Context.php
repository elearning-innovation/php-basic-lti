<?php

declare(strict_types=1);

namespace Oscelot\Lti;

/**
 * Class to represent a tool consumer context
 *
 * @deprecated Use ResourceLink instead
 * @see ResourceLink
 */
class Context extends ResourceLink
{
    /**
     * ID value for context being shared (if any).
     *
     * @deprecated Use primary_resource_link_id instead
     * @see ResourceLink::$primary_resource_link_id
     */
    public mixed $primary_context_id = null;

    /**
     * Class constructor.
     *
     * @param ?ToolConsumer $consumer Consumer key value
     * @param string $id       Resource link ID value
     */
    public function __construct(?ToolConsumer $consumer, string $id)
    {
        parent::__construct($consumer, $id);
        $this->primary_context_id = &$this->primary_resource_link_id;
    }
}
