<?php

/**
 * @noinspection PhpMultipleClassDeclarationsInspection
 */

declare(strict_types=1);

namespace Oscelot\Lti;

use JsonException;
use PDO;

// phpcs:disable PSR1.Methods.CamelCapsMethodName
// phpcs:disable Generic.Files.LineLength.TooLong
class DataConnectorPdo extends AbstractDataConnector
{
    public function __construct(
        private readonly PDO $db,
        private readonly string $dbTableNamePrefix = ''
    ) {
    }

    /**
     * Load the tool consumer from the database
     */
    public function Tool_Consumer_load(ToolConsumer $consumer): bool
    {
        $sql = 'SELECT name, secret, lti_version, consumer_name,
           consumer_version, consumer_guid, css_path, protected, enabled,
           enable_from, enable_until, last_access, created, updated ' .
           'FROM ' .$this->dbTableNamePrefix . AbstractDataConnector::CONSUMER_TABLE_NAME . ' ' .
           'WHERE consumer_key = :key';
        $query = $this->db->prepare($sql);
        $key = $consumer->getKey();
        $query->bindValue('key', $key, PDO::PARAM_STR);
        $ok = $query->execute();

        if ($ok) {
            $row = $query->fetch();
            $ok = ($row !== false);
        }

        if ($ok) {
            $consumer->name = $row['name'];
            $consumer->secret = $row['secret'];

            $consumer->lti_version = $row['lti_version'];
            $consumer->consumer_name = $row['consumer_name'];
            $consumer->consumer_version = $row['consumer_version'];
            $consumer->consumer_guid = $row['consumer_guid'];
            $consumer->css_path = $row['css_path'];
            $consumer->protected = ($row['protected'] == 1);
            $consumer->enabled = ($row['enabled'] == 1);
            $consumer->enable_from = null;
            if (!is_null($row['enable_from'])) {
                $consumer->enable_from = strtotime($row['enable_from']);
            }
            $consumer->enable_until = null;
            if (!is_null($row['enable_until'])) {
                $consumer->enable_until = strtotime($row['enable_until']);
            }
            $consumer->last_access = null;
            if (!is_null($row['last_access'])) {
                $consumer->last_access = strtotime($row['last_access']);
            }
            $consumer->created = strtotime($row['created']);
            $consumer->updated = strtotime($row['updated']);
        }

        return $ok;
    }

    /**
     * Save the tool consumer to the database
     */
    public function Tool_Consumer_save(ToolConsumer $consumer): bool
    {
        if ($consumer->protected) {
            $protected = 1;
        } else {
            $protected = 0;
        }
        if ($consumer->enabled) {
            $enabled = 1;
        } else {
            $enabled = 0;
        }
        $time = time();
        $now = date('Y-m-d H:i:s', $time);
        $from = null;
        if (!is_null($consumer->enable_from)) {
            $from = date('Y-m-d H:i:s', $consumer->enable_from);
        }
        $until = null;
        if (!is_null($consumer->enable_until)) {
            $until = date('Y-m-d H:i:s', $consumer->enable_until);
        }
        $last = null;
        if (!is_null($consumer->last_access)) {
            $last = date('Y-m-d', $consumer->last_access);
        }
        $key = $consumer->getKey();
        if (is_null($consumer->created)) {
            $sql = 'INSERT INTO ' . $this->dbTableNamePrefix . AbstractDataConnector::CONSUMER_TABLE_NAME . ' ' .
             '(consumer_key, name, secret, lti_version, consumer_name, consumer_version, consumer_guid, css_path, protected, enabled, enable_from, enable_until, last_access, created, updated) ' .
             'VALUES (:key, :name, :secret, :lti_version, :consumer_name, :consumer_version, :consumer_guid, :css_path, ' .
             ':protected, :enabled, :enable_from, :enable_until, :last_access, :created, :updated)';
            $query = $this->db->prepare($sql);
            $query->bindValue('key', $key, PDO::PARAM_STR);
            $query->bindValue('name', $consumer->name, PDO::PARAM_STR);
            $query->bindValue('secret', $consumer->secret, PDO::PARAM_STR);
            $query->bindValue('lti_version', $consumer->lti_version, PDO::PARAM_STR);
            $query->bindValue('consumer_name', $consumer->consumer_name, PDO::PARAM_STR);
            $query->bindValue('consumer_version', $consumer->consumer_version, PDO::PARAM_STR);
            $query->bindValue('consumer_guid', $consumer->consumer_guid, PDO::PARAM_STR);
            $query->bindValue('css_path', $consumer->css_path, PDO::PARAM_STR);
            $query->bindValue('protected', $protected, PDO::PARAM_INT);
            $query->bindValue('enabled', $enabled, PDO::PARAM_INT);
            $query->bindValue('enable_from', $from, PDO::PARAM_STR);
            $query->bindValue('enable_until', $until, PDO::PARAM_STR);
            $query->bindValue('last_access', $last, PDO::PARAM_STR);
            $query->bindValue('created', $now, PDO::PARAM_STR);
            $query->bindValue('updated', $now, PDO::PARAM_STR);
        } else {
            $sql = 'UPDATE ' . $this->dbTableNamePrefix . AbstractDataConnector::CONSUMER_TABLE_NAME . ' ' .
             'SET name = :name, secret = :secret, lti_version = :lti_version, ' .
             'consumer_name = :consumer_name, consumer_version = :consumer_version, consumer_guid = :consumer_guid, css_path = :css_path, ' .
             'protected = :protected, enabled = :enabled, enable_from = :enable_from, enable_until = :enable_until, last_access = :last_access, updated = :updated ' .
             'WHERE consumer_key = :key';
            $query = $this->db->prepare($sql);
            $query->bindValue('key', $key, PDO::PARAM_STR);
            $query->bindValue('name', $consumer->name, PDO::PARAM_STR);
            $query->bindValue('secret', $consumer->secret, PDO::PARAM_STR);
            $query->bindValue('lti_version', $consumer->lti_version, PDO::PARAM_STR);
            $query->bindValue('consumer_name', $consumer->consumer_name, PDO::PARAM_STR);
            $query->bindValue('consumer_version', $consumer->consumer_version, PDO::PARAM_STR);
            $query->bindValue('consumer_guid', $consumer->consumer_guid, PDO::PARAM_STR);
            $query->bindValue('css_path', $consumer->css_path, PDO::PARAM_STR);
            $query->bindValue('protected', $protected, PDO::PARAM_INT);
            $query->bindValue('enabled', $enabled, PDO::PARAM_INT);
            $query->bindValue('enable_from', $from, PDO::PARAM_STR);
            $query->bindValue('enable_until', $until, PDO::PARAM_STR);
            $query->bindValue('last_access', $last, PDO::PARAM_STR);
            $query->bindValue('updated', $now, PDO::PARAM_STR);
        }
        $ok = $query->execute();
        if ($ok) {
            if (is_null($consumer->created)) {
                $consumer->created = $time;
            }
            $consumer->updated = $time;
        }

        return $ok;
    }

    /**
     * Delete the tool consumer from the database
     */
    public function Tool_Consumer_delete(ToolConsumer $consumer): bool
    {
        $key = $consumer->getKey();

        // Delete any nonce values for this consumer
        $sql = 'DELETE FROM ' . $this->dbTableNamePrefix . AbstractDataConnector::NONCE_TABLE_NAME . ' WHERE consumer_key = :key';
        $query = $this->db->prepare($sql);
        $query->bindValue('key', $key, PDO::PARAM_STR);
        $query->execute();

        // Delete any outstanding share keys for resource links for this consumer
        $sql = 'DELETE FROM ' . $this->dbTableNamePrefix . AbstractDataConnector::RESOURCE_LINK_SHARE_KEY_TABLE_NAME . ' WHERE primary_consumer_key = :key';
        $query = $this->db->prepare($sql);
        $query->bindValue('key', $key, PDO::PARAM_STR);
        $query->execute();

        // Delete any users in resource links for this consumer
        $sql = 'DELETE FROM ' . $this->dbTableNamePrefix . AbstractDataConnector::USER_TABLE_NAME . ' WHERE consumer_key = :key';
        $query = $this->db->prepare($sql);
        $query->bindValue('key', $key, PDO::PARAM_STR);
        $query->execute();

        // Update any resource links for which this consumer is acting as a primary resource link
        $sql = 'UPDATE ' . $this->dbTableNamePrefix . AbstractDataConnector::RESOURCE_LINK_TABLE_NAME . ' ' .
           'SET primary_consumer_key = NULL AND primary_context_id = NULL ' .
           'WHERE primary_consumer_key = :key';
        $query = $this->db->prepare($sql);
        $query->bindValue('key', $key, PDO::PARAM_STR);
        $query->execute();

        // Delete any resource links for this consumer
        $sql = 'DELETE FROM ' . $this->dbTableNamePrefix . AbstractDataConnector::RESOURCE_LINK_TABLE_NAME . ' WHERE consumer_key = :key';
        $query = $this->db->prepare($sql);
        $query->bindValue('key', $key, PDO::PARAM_STR);
        $query->execute();

        // Delete consumer
        $sql = 'DELETE FROM ' . $this->dbTableNamePrefix . AbstractDataConnector::CONSUMER_TABLE_NAME . ' WHERE consumer_key = :key';
        $query = $this->db->prepare($sql);
        $query->bindValue('key', $key, PDO::PARAM_STR);
        $ok = $query->execute();

        if ($ok) {
            $consumer->initialise();
        }

        return $ok;
    }

    /**
     * Load all tool consumers from the database
     *
     * @return ToolConsumer[]
     */
    public function Tool_Consumer_list(): array
    {
        $consumers = array();

        $sql = 'SELECT consumer_key, name, secret, lti_version, consumer_name, consumer_version, consumer_guid, css_path, ' .
           'protected, enabled, enable_from, enable_until, last_access, created, updated ' .
           "FROM {$this->dbTableNamePrefix}" . AbstractDataConnector::CONSUMER_TABLE_NAME . ' ' .
           'ORDER BY name';
        $query = $this->db->prepare($sql);
        $ok = ($query !== false);

        if ($ok) {
            $ok = $query->execute();
        }
        if ($ok) {
            while ($row = $query->fetch()) {
                $consumer = new ToolConsumer($row['consumer_key'], $this);
                $consumer->name = $row['name'];
                $consumer->secret = $row['secret'];

                $consumer->lti_version = $row['lti_version'];
                $consumer->consumer_name = $row['consumer_name'];
                $consumer->consumer_version = $row['consumer_version'];
                $consumer->consumer_guid = $row['consumer_guid'];
                $consumer->css_path = $row['css_path'];
                $consumer->protected = ($row['protected'] == 1);
                $consumer->enabled = ($row['enabled'] == 1);
                $consumer->enable_from = null;
                if (!is_null($row['enable_from'])) {
                    $consumer->enable_from = strtotime($row['enable_from']);
                }
                $consumer->enable_until = null;
                if (!is_null($row['enable_until'])) {
                    $consumer->enable_until = strtotime($row['enable_until']);
                }
                $consumer->last_access = null;
                if (!is_null($row['last_access'])) {
                    $consumer->last_access = strtotime($row['last_access']);
                }
                $consumer->created = strtotime($row['created']);
                $consumer->updated = strtotime($row['updated']);
                $consumers[] = $consumer;
            }
        }

        return $consumers;
    }

    /**
     * Load the resource link from the database
     *
     * @throws JsonException
     */
    public function Resource_Link_load($resource_link): bool
    {
        $key = $resource_link->getKey();
        $id = $resource_link->getId();
        $sql = 'SELECT consumer_key, context_id, lti_context_id, lti_resource_id, title, settings, ' .
           'primary_consumer_key, primary_context_id, share_approved, created, updated ' .
           'FROM ' .$this->dbTableNamePrefix . AbstractDataConnector::RESOURCE_LINK_TABLE_NAME . ' ' .
           'WHERE (consumer_key = :key) AND (context_id = :id)';
        $query = $this->db->prepare($sql);
        $query->bindValue('key', $key, PDO::PARAM_STR);
        $query->bindValue('id', $id, PDO::PARAM_STR);
        $ok = $query->execute();
        if ($ok) {
            $row = $query->fetch();
            $ok = ($row !== false);
        }

        if ($ok) {
            $resource_link->lti_context_id = $row['lti_context_id'];
            $resource_link->ResourceLink_id = $row['lti_resource_id'];
            $resource_link->title = $row['title'];
            $resource_link->settings = json_decode(
                $row['settings'],
                true,
                512,
                JSON_THROW_ON_ERROR
            );
            if (!is_array($resource_link->settings)) {
                $resource_link->settings = unserialize($row['settings']);  // check for old serialized setting
            }
            if (!is_array($resource_link->settings)) {
                $resource_link->settings = array();
            }
            $resource_link->primary_consumer_key = $row['primary_consumer_key'];
            $resource_link->primary_resource_link_id = $row['primary_context_id'];
            $resource_link->share_approved = (is_null($row['share_approved'])) ? null : ($row['share_approved'] == 1);
            $resource_link->created = strtotime($row['created']);
            $resource_link->updated = strtotime($row['updated']);
        }

        return $ok;
    }

    /**
     * Save the resource link to the database
     */
    public function Resource_Link_save($resource_link): bool
    {
        $time = time();
        $now = date('Y-m-d H:i:s', $time);
        $settingsValue = json_encode($resource_link->settings);
        $key = $resource_link->getKey();
        $id = $resource_link->getId();
        if (is_null($resource_link->created)) {
            $sql = 'INSERT INTO ' . $this->dbTableNamePrefix . AbstractDataConnector::RESOURCE_LINK_TABLE_NAME . ' ' .
             '(consumer_key, context_id, lti_context_id, lti_resource_id, title, settings, ' .
             'primary_consumer_key, primary_context_id, share_approved, created, updated) ' .
             'VALUES (:key, :id, :lti_context_id, :lti_resource_id, :title, :settings, ' .
             ':primary_consumer_key, :primary_context_id, :share_approved, :created, :updated)';
            $query = $this->db->prepare($sql);
            $query->bindValue('key', $key, PDO::PARAM_STR);
            $query->bindValue('id', $id, PDO::PARAM_STR);
            $query->bindValue('lti_context_id', $resource_link->lti_context_id, PDO::PARAM_STR);
            $query->bindValue('lti_resource_id', $resource_link->lti_resource_id, PDO::PARAM_STR);
            $query->bindValue('title', $resource_link->title, PDO::PARAM_STR);
            $query->bindValue('settings', $settingsValue, PDO::PARAM_STR);
            $query->bindValue('primary_consumer_key', $resource_link->primary_consumer_key, PDO::PARAM_STR);
            $query->bindValue('primary_context_id', $resource_link->primary_resource_link_id, PDO::PARAM_STR);
            $query->bindValue('share_approved', $resource_link->share_approved, PDO::PARAM_INT);
            $query->bindValue('created', $now, PDO::PARAM_STR);
            $query->bindValue('updated', $now, PDO::PARAM_STR);
        } else {
            $sql = 'UPDATE ' . $this->dbTableNamePrefix . AbstractDataConnector::RESOURCE_LINK_TABLE_NAME . ' ' .
             'SET lti_context_id = :lti_context_id, lti_resource_id = :lti_resource_id, title = :title, settings = :settings, ' .
             'primary_consumer_key = :primary_consumer_key, primary_context_id = :primary_context_id, share_approved = :share_approved, updated = :updated ' .
             'WHERE (consumer_key = :key) AND (context_id = :id)';
            $query = $this->db->prepare($sql);
            $query->bindValue('key', $key, PDO::PARAM_STR);
            $query->bindValue('id', $id, PDO::PARAM_STR);
            $query->bindValue('lti_context_id', $resource_link->lti_context_id, PDO::PARAM_STR);
            $query->bindValue('lti_resource_id', $resource_link->lti_resource_id, PDO::PARAM_STR);
            $query->bindValue('title', $resource_link->title, PDO::PARAM_STR);
            $query->bindValue('settings', $settingsValue, PDO::PARAM_STR);
            $query->bindValue('primary_consumer_key', $resource_link->primary_consumer_key, PDO::PARAM_STR);
            $query->bindValue('primary_context_id', $resource_link->primary_resource_link_id, PDO::PARAM_STR);
            $query->bindValue('share_approved', $resource_link->share_approved, PDO::PARAM_INT);
            $query->bindValue('updated', $now, PDO::PARAM_STR);
        }
        $ok = $query->execute();
        if ($ok) {
            if (is_null($resource_link->created)) {
                $resource_link->created = $time;
            }
            $resource_link->updated = $time;
        }

        return $ok;
    }

    /**
     * Delete the resource link from the database
     */
    public function Resource_Link_delete($resource_link): bool
    {
        $key = $resource_link->getKey();
        $id = $resource_link->getId();

        // Delete any outstanding share keys for resource links for this consumer
        $sql = 'DELETE FROM ' . $this->dbTableNamePrefix
            . AbstractDataConnector::RESOURCE_LINK_SHARE_KEY_TABLE_NAME . ' ' .
           'WHERE (primary_consumer_key = :key) AND (primary_context_id = :id)';
        $query = $this->db->prepare($sql);
        $query->bindValue('key', $key, PDO::PARAM_STR);
        $query->bindValue('id', $id, PDO::PARAM_STR);
        $ok = $query->execute();

        // Delete users
        if ($ok) {
            $sql = 'DELETE FROM ' . $this->dbTableNamePrefix
                . AbstractDataConnector::USER_TABLE_NAME . ' ' .
             'WHERE (consumer_key = :key) AND (context_id = :id)';
            $query = $this->db->prepare($sql);
            $query->bindValue('key', $key, PDO::PARAM_STR);
            $query->bindValue('id', $id, PDO::PARAM_STR);
            $ok = $query->execute();
        }

        // Update any resource links for which this is the primary resource link
        if ($ok) {
            $sql = 'UPDATE ' . $this->dbTableNamePrefix
                . AbstractDataConnector::RESOURCE_LINK_TABLE_NAME . ' ' .
             'SET primary_consumer_key = NULL AND primary_context_id = NULL ' .
             'WHERE (consumer_key = :key) AND (context_id = :id)';
            $query = $this->db->prepare($sql);
            $query->bindValue('key', $key, PDO::PARAM_STR);
            $query->bindValue('id', $id, PDO::PARAM_STR);
            $ok = $query->execute();
        }

        // Delete resource link
        if ($ok) {
            $sql = 'DELETE FROM ' . $this->dbTableNamePrefix
                . AbstractDataConnector::RESOURCE_LINK_TABLE_NAME . ' '
                . 'WHERE (consumer_key = :key) AND (context_id = :id)';
            $query = $this->db->prepare($sql);
            $query->bindValue('key', $key, PDO::PARAM_STR);
            $query->bindValue('id', $id, PDO::PARAM_STR);
            $ok = $query->execute();
        }

        if ($ok) {
            $resource_link->initialise();
        }

        return $ok;
    }

    /**
     * Obtain an array of User objects for users with a result sourcedId. The
     * array may include users from other resource links which are sharing this
     * resource link. It may also be optionally indexed by the user ID of a
     * specified scope.
     */
    public function Resource_Link_getUserResultSourcedIDs(
        $resource_link,
        bool $local_only,
        int $id_scope
    ): array {
        $users = array();

        if ($local_only) {
            $sql = 'SELECT u.consumer_key, u.context_id, u.user_id, u.lti_result_sourcedid ' .
             'FROM ' . $this->dbTableNamePrefix
                . AbstractDataConnector::USER_TABLE_NAME . ' AS u ' .
             'INNER JOIN '
                . $this->dbTableNamePrefix
                . AbstractDataConnector::RESOURCE_LINK_TABLE_NAME . ' AS c ' .
             'ON u.consumer_key = c.consumer_key AND u.context_id = c.context_id ' .
             'WHERE (c.consumer_key = :key) AND (c.context_id = :id) AND
             (c.primary_consumer_key IS NULL) AND (c.primary_context_id IS NULL)';
        } else {
            $sql = 'SELECT u.consumer_key, u.context_id, u.user_id,
                u.lti_result_sourcedid ' .
                'FROM ' . $this->dbTableNamePrefix . AbstractDataConnector::USER_TABLE_NAME . ' AS u ' .
                'INNER JOIN ' . $this->dbTableNamePrefix . AbstractDataConnector::RESOURCE_LINK_TABLE_NAME . ' AS c ' .
                'ON u.consumer_key = c.consumer_key AND u.context_id = c.context_id ' .
                'WHERE ((c.consumer_key = :key) AND (c.context_id = :id) AND
                (c.primary_consumer_key IS NULL) AND (c.primary_context_id IS NULL)) OR ' .
                '((c.primary_consumer_key = :key) AND (c.primary_context_id = :id)
                AND (share_approved = 1))';
        }
        $key = $resource_link->getKey();
        $id = $resource_link->getId();
        $query = $this->db->prepare($sql);
        $query->bindValue('key', $key, PDO::PARAM_STR);
        $query->bindValue('id', $id, PDO::PARAM_STR);
        if ($query->execute()) {
            while ($row = $query->fetch()) {
                $user = new User($resource_link, $row['user_id']);
                $user->consumer_key = $row['consumer_key'];
                $user->context_id = $row['context_id'];
                $user->lti_result_sourcedid = $row['lti_result_sourcedid'];
                if (is_null($id_scope)) {
                    $users[] = $user;
                } else {
                    $users[$user->getId($id_scope)] = $user;
                }
            }
        }

        return $users;
    }

    /**
     * Get an array of ResourceLink_Share objects for each resource link which
     * is sharing this resource link.
     */
    public function Resource_Link_getShares($resource_link): array
    {
        $shares = array();

        $key = $resource_link->getKey();
        $id = $resource_link->getId();
        $sql = 'SELECT consumer_key, context_id, title, share_approved ' .
           'FROM ' . $this->dbTableNamePrefix . AbstractDataConnector::RESOURCE_LINK_TABLE_NAME . ' ' .
           'WHERE (primary_consumer_key = :key) AND (primary_context_id = :id) ' .
           'ORDER BY consumer_key';
        $query = $this->db->prepare($sql);
        $query->bindValue('key', $key, PDO::PARAM_STR);
        $query->bindValue('id', $id, PDO::PARAM_STR);
        if ($query->execute()) {
            while ($row = $query->fetch()) {
                $share = new ResourceLinkShare();
                $share->consumer_key = $row['consumer_key'];
                $share->resource_link_id = $row['context_id'];
                $share->title = $row['title'];
                $share->approved = ($row['share_approved'] == 1);
                $shares[] = $share;
            }
        }

        return $shares;
    }

    /**
     * Load the consumer nonce from the database
     */
    public function Consumer_Nonce_load(ConsumerNonce $nonce): bool
    {
        // Delete any expired nonce values
        $now = date('Y-m-d H:i:s', time());
        $sql = 'DELETE FROM '
            . $this->dbTableNamePrefix
            . AbstractDataConnector::NONCE_TABLE_NAME
            . ' WHERE expires <= :now';
        $query = $this->db->prepare($sql);
        $query->bindValue('now', $now, PDO::PARAM_STR);
        $query->execute();

        // Load the nonce
        $key = $nonce->getKey();
        $value = $nonce->getValue();
        $sql = 'SELECT value AS T FROM '
            . $this->dbTableNamePrefix
            . AbstractDataConnector::NONCE_TABLE_NAME
            . ' WHERE (consumer_key = :key) AND (value = :value)';
        $query = $this->db->prepare($sql);
        $query->bindValue('key', $key, PDO::PARAM_STR);
        $query->bindValue('value', $value, PDO::PARAM_STR);
        $ok = $query->execute();
        if ($ok) {
            $row = $query->fetch();
            if ($row === false) {
                $ok = false;
            }
        }

        return $ok;
    }

    /**
     * Save the consumer nonce in the database
     */
    public function Consumer_Nonce_save(ConsumerNonce $nonce): bool
    {
        $key = $nonce->getKey();
        $value = $nonce->getValue();
        $expires = date('Y-m-d H:i:s', $nonce->expires);
        $sql = 'INSERT INTO '
            . $this->dbTableNamePrefix
            . AbstractDataConnector::NONCE_TABLE_NAME
            . ' (consumer_key, value, expires) VALUES (:key, :value, :expires)';
        $query = $this->db->prepare($sql);
        $query->bindValue('key', $key, PDO::PARAM_STR);
        $query->bindValue('value', $value, PDO::PARAM_STR);
        $query->bindValue('expires', $expires, PDO::PARAM_STR);
        $ok = $query->execute();

        return $ok;
    }


    /**
     * Load the resource link share key from the database
     */
    public function Resource_Link_Share_Key_load(
        ResourceLinkShareKey $share_key
    ): bool {
        // Clear expired share keys
        $now = date('Y-m-d H:i:s', time());
        $sql = 'DELETE FROM '
            . $this->dbTableNamePrefix
            . AbstractDataConnector::RESOURCE_LINK_SHARE_KEY_TABLE_NAME
            . ' WHERE expires <= :now';
        $query = $this->db->prepare($sql);
        $query->bindValue('now', $now, PDO::PARAM_STR);
        $query->execute();

        // Load share key
        $id = $share_key->getId();
        $sql = 'SELECT share_key_id, primary_consumer_key, primary_context_id, auto_approve, expires ' .
           'FROM ' . $this->dbTableNamePrefix . AbstractDataConnector::RESOURCE_LINK_SHARE_KEY_TABLE_NAME . ' ' .
           'WHERE share_key_id = :id';
        $query = $this->db->prepare($sql);
        $query->bindValue('id', $id, PDO::PARAM_STR);
        $ok = $query->execute();
        if ($ok) {
            $row = $query->fetch();
            $ok = ($row !== false);
        }

        if ($ok) {
            $share_key->primary_consumer_key = $row['primary_consumer_key'];
            $share_key->primary_resource_link_id = $row['primary_context_id'];
            $share_key->auto_approve = ($row['auto_approve'] == 1);
            $share_key->expires = strtotime($row['expires']);
        }

        return $ok;
    }

    /**
     * Save the resource link share key to the database
     */
    public function Resource_Link_Share_Key_save(
        ResourceLinkShareKey$share_key
    ): bool {
        if ($share_key->auto_approve) {
            $approve = 1;
        } else {
            $approve = 0;
        }
        $expires = date('Y-m-d H:i:s', $share_key->expires);
        $id = $share_key->getId();
        $sql = 'INSERT INTO ' . $this->dbTableNamePrefix . AbstractDataConnector::RESOURCE_LINK_SHARE_KEY_TABLE_NAME . ' ' .
           '(share_key_id, primary_consumer_key, primary_context_id, auto_approve, expires) ' .
           'VALUES (:id, :primary_consumer_key, :primary_context_id, :approve, :expires)';
        $query = $this->db->prepare($sql);
        $query->bindValue('id', $id, PDO::PARAM_STR);
        $query->bindValue('primary_consumer_key', $share_key->primary_consumer_key, PDO::PARAM_STR);
        $query->bindValue('primary_context_id', $share_key->primary_resource_link_id, PDO::PARAM_STR);
        $query->bindValue('approve', $approve, PDO::PARAM_INT);
        $query->bindValue('expires', $expires, PDO::PARAM_STR);

        return $query->execute();
    }

    /**
     * Delete the resource link share key from the database
     */
    public function Resource_Link_Share_Key_delete($share_key): bool
    {
        $id = $share_key->getId();
        $sql = 'DELETE FROM ' . $this->dbTableNamePrefix . AbstractDataConnector::RESOURCE_LINK_SHARE_KEY_TABLE_NAME . ' WHERE share_key_id = :id';
        $query = $this->db->prepare($sql);
        $query->bindValue('id', $id, PDO::PARAM_STR);
        $ok = $query->execute();
        if ($ok) {
            $share_key->initialise();
        }

        return $ok;
    }


    /**
     * Load the user from the database
     */
    public function User_load(User $user): bool
    {

        $key = $user->getResourceLink()->getKey();
        $id = $user->getResourceLink()->getId();
        $userId = $user->getId(ToolProvider::ID_SCOPE_ID_ONLY);
        $sql = 'SELECT lti_result_sourcedid, created, updated ' .
           'FROM ' . $this->dbTableNamePrefix . AbstractDataConnector::USER_TABLE_NAME . ' ' .
           'WHERE (consumer_key = :key) AND (context_id = :id) AND (user_id = :user_id)';
        $query = $this->db->prepare($sql);
        $query->bindValue('key', $key, PDO::PARAM_STR);
        $query->bindValue('id', $id, PDO::PARAM_STR);
        $query->bindValue('user_id', $userId, PDO::PARAM_STR);
        $ok = $query->execute();
        if ($ok) {
            $row = $query->fetch();
            $ok = ($row !== false);
        }

        if ($ok) {
            $user->lti_result_sourcedid = $row['lti_result_sourcedid'];
            $user->created = strtotime($row['created']);
            $user->updated = strtotime($row['updated']);
        }

        return $ok;
    }

    /**
     * Save the user to the database.
     */
    public function User_save(User $user): bool
    {
        $time = time();
        $now = date('Y-m-d H:i:s', $time);
        $key = $user->getResourceLink()->getKey();
        $id = $user->getResourceLink()->getId();
        $userId = $user->getId(ToolProvider::ID_SCOPE_ID_ONLY);
        if (is_null($user->created)) {
            $sql = 'INSERT INTO ' . $this->dbTableNamePrefix . AbstractDataConnector::USER_TABLE_NAME . ' (consumer_key, context_id, ' .
             'user_id, lti_result_sourcedid, created, updated) ' .
             'VALUES (:key, :id, :user_id, :lti_result_sourcedid, :now, :now)';
        } else {
            $sql = 'UPDATE ' . $this->dbTableNamePrefix . AbstractDataConnector::USER_TABLE_NAME . ' ' .
             'SET lti_result_sourcedid = :lti_result_sourcedid, updated = :now ' .
             'WHERE (consumer_key = :key) AND (context_id = :id) AND (user_id = :user_id)';
        }
        $query = $this->db->prepare($sql);
        $query->bindValue('key', $key, PDO::PARAM_STR);
        $query->bindValue('id', $id, PDO::PARAM_STR);
        $query->bindValue('user_id', $userId, PDO::PARAM_STR);
        $query->bindValue('lti_result_sourcedid', $user->lti_result_sourcedid, PDO::PARAM_STR);
        $query->bindValue('now', $now, PDO::PARAM_STR);
        $ok = $query->execute();
        if ($ok) {
            if (is_null($user->created)) {
                $user->created = $time;
            }
            $user->updated = $time;
        }

        return $ok;
    }

    /**
     * Delete the user from the database
     */
    public function User_delete(User $user): bool
    {
        $key = $user->getResourceLink()->getKey();
        $id = $user->getResourceLink()->getId();
        $userId = $user->getId(ToolProvider::ID_SCOPE_ID_ONLY);
        $sql = 'DELETE FROM ' . $this->dbTableNamePrefix . AbstractDataConnector::USER_TABLE_NAME . ' ' .
           'WHERE (consumer_key = :key) AND (context_id = :id) AND (user_id = :user_id)';
        $query = $this->db->prepare($sql);
        $query->bindValue('key', $key, PDO::PARAM_STR);
        $query->bindValue('id', $id, PDO::PARAM_STR);
        $query->bindValue('user_id', $userId, PDO::PARAM_STR);
        $ok = $query->execute();

        if ($ok) {
            $user->initialise();
        }

        return $ok;
    }
}
