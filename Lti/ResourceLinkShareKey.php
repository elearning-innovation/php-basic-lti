<?php

declare(strict_types=1);

namespace Oscelot\Lti;

/**
 * Class to represent a tool consumer resource link share key
 */
class ResourceLinkShareKey
{
    /**
     * Maximum permitted life for a share key value.
     */
    public const MAX_SHARE_KEY_LIFE = 168;  // in hours (1 week)

    /**
     * Default life for a share key value.
     */
    public const DEFAULT_SHARE_KEY_LIFE = 24;  // in hours

    /**
     * Minimum length for a share key value.
     */
    public const MIN_SHARE_KEY_LENGTH = 5;

    /**
     * Maximum length for a share key value.
     */
    public const MAX_SHARE_KEY_LENGTH = 32;

    /**
     * Consumer key for resource link being shared.
     */
    public ?string $primary_consumer_key = null;

    /**
     * ID for resource link being shared.
     */
    public ?string $primary_resource_link_id = null;

    /**
     * Length of share key.
     */
    public mixed $length = null;

    /**
     * Life of share key.
     */
    public mixed $life = null;  // in hours

    /**
     * True if the sharing arrangement should be automatically approved when first used.
     */
    public bool $auto_approve = false;

    /**
     * Date/time when the share key expires.
     */
    public mixed $expires = null;

    /**
     * Share key value.
     */
    private ?string $id;

    /**
     * Data connector.
     */
    private mixed $data_connector;

    /**
     * @param ResourceLink $resource_link Resource_Link object.
     * @param ?string $id  Value of share key (optional, default is null).
     */
    public function __construct(ResourceLink $resource_link, ?string $id = null)
    {
        $this->initialise();
        $this->data_connector = $resource_link->getConsumer()?->getDataConnector();
        $this->id = $id;
        /**
         * @noinspection PhpDeprecationInspection
         * @noinspection PhpPossiblePolymorphicInvocationInspection
         */
        $this->primary_context_id = &$this->primary_resource_link_id;
        if (!empty($id)) {
            $this->load();
        } else {
            $this->primary_consumer_key = $resource_link->getKey();
            $this->primary_resource_link_id = $resource_link->getId();
        }
    }

    /**
     * Initialise the resource link share key.
     */
    public function initialise(): void
    {
        $this->primary_consumer_key = null;
        $this->primary_resource_link_id = null;
        $this->length = null;
        $this->life = null;
        $this->auto_approve = false;
        $this->expires = null;
    }

    /**
     * Save the resource link share key to the database.
     *
     * @return bool True if the share key was successfully saved.
     */
    public function save(): bool
    {
        if (empty($this->life)) {
            $this->life = self::DEFAULT_SHARE_KEY_LIFE;
        } else {
            $this->life = max(min($this->life, self::MAX_SHARE_KEY_LIFE), 0);
        }
        $this->expires = time() + ($this->life * 60 * 60);
        if (empty($this->id)) {
            if (empty($this->length) || !is_numeric($this->length)) {
                $this->length = self::MAX_SHARE_KEY_LENGTH;
            } else {
                $this->length = max(
                    min(
                        $this->length,
                        self::MAX_SHARE_KEY_LENGTH
                    ),
                    self::MIN_SHARE_KEY_LENGTH
                );
            }
            $this->id = AbstractDataConnector::getRandomString($this->length);
        }

        return $this->data_connector->Resource_Link_Share_Key_save($this);
    }

    /**
     * Delete the resource link share key from the database.
     *
     * @return bool True if the share key was successfully deleted
     */
    public function delete(): bool
    {
        return $this->data_connector->Resource_Link_Share_Key_delete($this);
    }

    /**
     * Get share key value.
     *
     * @return ?string Share key value
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * Load the resource link share key from the database.
     */
    private function load(): void
    {
        $this->initialise();
        $this->data_connector->Resource_Link_Share_Key_load($this);
        if (!is_null($this->id)) {
            $this->length = strlen($this->id);
        }
        if (!is_null($this->expires)) {
            $this->life = ($this->expires - time()) / 60 / 60;
        }
    }
}
