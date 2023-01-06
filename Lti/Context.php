<?php

namespace Oscelot\Lti;

/**
 * Class to represent a tool consumer context
 *
 * @deprecated Use ResourceLink instead
 * @see ResourceLink
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @version 2.3.04
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class Context extends ResourceLink
{
    /**
     * ID value for context being shared (if any).
     *
     * @deprecated Use primary_resource_link_id instead
     * @see ResourceLink::$primary_resource_link_id
     */
    public $primary_context_id = null;

    /**
     * Class constructor.
     *
     * @param string $consumer Consumer key value
     * @param string $id       Resource link ID value
     */
    public function __construct($consumer, $id)
    {
        parent::__construct($consumer, $id);
        $this->primary_context_id = &$this->primary_resource_link_id;
    }
}
