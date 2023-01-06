<?php

declare(strict_types=1);

namespace Oscelot\Lti;

/**
 * Class to represent a tool consumer
 */
class ToolConsumer
{
    /**
     * Local name of tool consumer.
     */
    public ?string $name = null;

    /**
     * Shared secret.
     */
    public ?string $secret = null;

    /**
     * LTI version (as reported by last tool consumer connection).
     */
    public ?string $lti_version = null;

    /**
     * Name of tool consumer (as reported by last tool consumer connection).
     */
    public ?string $consumer_name = null;

    /**
     * Tool consumer version (as reported by last tool consumer connection).
     */
    public ?string $consumer_version = null;

    /**
     * Tool consumer GUID (as reported by first tool consumer connection).
     */
    public ?string $consumer_guid = null;

    /**
     * Optional CSS path (as reported by last tool consumer connection).
     */
    public ?string $css_path = null;

    /**
     * True if the tool consumer instance is protected by matching the
     * consumer_guid value in incoming requests.
     */
    public bool $protected = false;

    /**
     * True if the tool consumer instance is enabled to accept incoming
     * connection requests.
     */
    public bool $enabled = false;

    /**
     * Date/time from which the the tool consumer instance is enabled to accept
     * incoming connection requests.
     */
    public ?string $enable_from = null;

    /**
     * Date/time until which the tool consumer instance is enabled to accept
     * incoming connection requests.
     */
    public ?string $enable_until = null;

    /**
     * Date of last connection from this tool consumer.
     */
    public ?string $last_access = null;

    /**
     * Default scope to use when generating an Id value for a user.
     */
    public int $id_scope = ToolProvider::ID_SCOPE_ID_ONLY;

    /**
     * Default email address (or email domain) to use when no email address is
     * provided for a user.
     */
    public string $defaultEmail = '';

    /**
     * Date/time when the object was created.
     */
    public ?string $created = null;

    /**
     * Date/time when the object was last updated.
     */
    public ?string $updated = null;

    /**
     * Consumer key value.
     */
    private ?string $key = null;

    /**
     * Data connector object or string.
     */
    private ?AbstractDataConnector $data_connector;

    /**
     * Class constructor.
     *
     * @param ?string $key Consumer key.
     * @param string $data_connector String containing table name prefix, or
     *                              database connection object, or array
     *                              containing one or both values (optional,
     *                              default is MySQL with an empty table name
     *                              prefix).
     * @param bool $autoEnable      true if the tool consumers is to be enabled
     *                              automatically (optional, default is false).
     */
    public function __construct(
        ?string $key = null,
        string $data_connector = '',
        bool $autoEnable = false
    ) {
        $this->data_connector = AbstractDataConnector::getDataConnector($data_connector);
        if (!empty($key)) {
            $this->load($key, $autoEnable);
        } else {
            $this->secret = AbstractDataConnector::getRandomString(32);
        }
    }

    /**
     * Initialise the tool consumer.
     */
    public function initialise(): void
    {
        $this->key = null;
        $this->name = null;
        $this->secret = null;
        $this->lti_version = null;
        $this->consumer_name = null;
        $this->consumer_version = null;
        $this->consumer_guid = null;
        $this->css_path = null;
        $this->protected = false;
        $this->enabled = false;
        $this->enable_from = null;
        $this->enable_until = null;
        $this->last_access = null;
        $this->id_scope = ToolProvider::ID_SCOPE_ID_ONLY;
        $this->defaultEmail = '';
        $this->created = null;
        $this->updated = null;
    }

    /**
     * Save the tool consumer to the database.
     *
     * @return bool True if the object was successfully saved.
     */
    public function save(): bool
    {
        return $this->data_connector->Tool_Consumer_save($this);
    }

    /**
     * Delete the tool consumer from the database.
     *
     * @return bool True if the object was successfully deleted.
     */
    public function delete(): bool
    {
        return $this->data_connector->Tool_Consumer_delete($this);
    }

    /**
     * Get the tool consumer key.
     *
     * @return ?string Consumer key value.
     */
    public function getKey(): ?string
    {
        return $this->key;
    }

    /**
     * Get the data connector.
     *
     * @return AbstractDataConnector Data connector object or string.
     */
    public function getDataConnector(): AbstractDataConnector
    {
        return $this->data_connector;
    }

    /**
     * Is the consumer key available to accept launch requests?
     *
     * @return bool True if the consumer key is enabled and within any date
     *              constraints.
     */
    public function getIsAvailable(): bool
    {
        $ok = $this->enabled;

        $now = time();
        if ($ok && !is_null($this->enable_from)) {
            $ok = $this->enable_from <= $now;
        }
        if ($ok && !is_null($this->enable_until)) {
            $ok = $this->enable_until > $now;
        }

        return $ok;
    }

    /**
     * Load the tool consumer from the database.
     *
     * @param string $key      The consumer key value.
     * @param bool $autoEnable True if the consumer should be enabled
     *                         (optional, default if false).
     * @return void True if the consumer was successfully loaded.
     */
    private function load(string $key, bool $autoEnable = false): void
    {
        $this->initialise();
        $this->key = $key;
        $ok = $this->data_connector->Tool_Consumer_load($this);
        if (!$ok) {
            $this->enabled = $autoEnable;
        }
    }
}
