<?php

declare(strict_types=1);

namespace Oscelot\Lti;

use Oscelot\Lti\DataConnectorPdo;

/**
 * Abstract class to provide a connection to a persistent store for LTI objects
 */
abstract class AbstractDataConnector
{
    /**
     * Default name for database table used to store tool consumers.
     */
    public const CONSUMER_TABLE_NAME = 'lti_consumer';

    /**
     * Default name for database table used to store resource links.
     */
    public const RESOURCE_LINK_TABLE_NAME = 'lti_context';

    /**
     * Default name for database table used to store users.
     */
    public const USER_TABLE_NAME = 'User';

    /**
     * Default name for database table used to store resource link share keys.
     */
    public const RESOURCE_LINK_SHARE_KEY_TABLE_NAME = 'lti_share_key';

    /**
     * Default name for database table used to store nonce values.
     */
    public const NONCE_TABLE_NAME = 'lti_nonce';

    /**
     * Load tool consumer object.
     *
     * @param ToolConsumer $consumer ToolConsumer object.
     * @return bool True if the tool consumer object was successfully loaded.
     */
    abstract public function Tool_Consumer_load(ToolConsumer $consumer): bool;

    /**
     * Save tool consumer object.
     *
     * @param ToolConsumer $consumer Consumer object.
     * @return bool True if the tool consumer object was successfully saved.
     */
    abstract public function Tool_Consumer_save(ToolConsumer $consumer): bool;

    /**
     * Delete tool consumer object.
     *
     * @param ToolConsumer $consumer Consumer object.
     * @return bool True if the tool consumer object was successfully deleted.
     */
    abstract public function Tool_Consumer_delete(ToolConsumer $consumer): bool;

    /**
     * Load tool consumer objects.
     *
     * @return ToolConsumer[] Array of all defined ToolConsumer objects.
     */
    abstract public function Tool_Consumer_list(): array;

    /**
     * Load resource link object.
     *
     * @param ResourceLink $resource_link Resource_Link object.
     * @return bool True if the resource link object was successfully loaded.
     */
    abstract public function Resource_Link_load(ResourceLink $resource_link);

    /**
     * Save resource link object.
     *
     * @param ResourceLink $resource_link Resource_Link object.
     * @return bool True if the resource link object was successfully saved.
     */
    abstract public function Resource_Link_save(
        ResourceLink $resource_link
    ): bool;

    /**
     * Delete resource link object.
     *
     * @param ResourceLink $resource_link Resource_Link object.
     * @return bool True if the Resource_Link object was successfully deleted.
     */
    abstract public function Resource_Link_delete(
        ResourceLink $resource_link
    ): bool;

    /**
     * Get array of user objects.
     *
     * @param ResourceLink $resource_link Resource link object.
     * @param bool         $local_only    True if only users within the resource link are
     *                         to be returned (excluding users sharing this
     *                         resource link).
     * @param int          $id_scope      Scope value to use for user IDs.
     * @return User[] Array of User objects.
     */
    abstract public function Resource_Link_getUserResultSourcedIDs(
        ResourceLink $resource_link,
        bool $local_only,
        int $id_scope
    ): array;

    /**
     * Get array of shares defined for this resource link.
     *
     * @param ResourceLink $resource_link Resource_Link object.
     * @return ResourceLinkShare[] Array of ResourceLinkShare objects.
     */
    abstract public function Resource_Link_getShares(
        ResourceLink $resource_link
    ): array;

    /**
     * Load nonce object.
     *
     * @param ConsumerNonce $nonce Nonce object.
     * @return bool True if the nonce object was successfully loaded.
     */
    abstract public function Consumer_Nonce_load(ConsumerNonce $nonce);

    /**
     * Save nonce object.
     *
     * @param ConsumerNonce $nonce Nonce object.
     * @return bool True if the nonce object was successfully saved.
     */
    abstract public function Consumer_Nonce_save(ConsumerNonce $nonce);

    /**
     * Load resource link share key object.
     *
     * @param ResourceLinkShareKey $share_key Resource_Link share key object.
     * @return bool True if the resource link share key object was successfully
     *              loaded.
     */
    abstract public function Resource_Link_Share_Key_load(
        ResourceLinkShareKey $share_key
    ): bool;

    /**
     * Save resource link share key object.
     *
     * @param ResourceLinkShareKey $share_key Resource link share key object.
     * @return bool True if the resource link share key object was successfully
     *              saved.
     */
    abstract public function Resource_Link_Share_Key_save(
        ResourceLinkShareKey $share_key
    ): bool;

    /**
     * Delete resource link share key object.
     *
     * @param ResourceLinkShareKey $share_key Resource link share key object.
     * @return bool True if the resource link share key object was successfully
     *              deleted.
     */
    abstract public function Resource_Link_Share_Key_delete(
        ResourceLinkShareKey $share_key
    ): bool;

    /**
     * Load user object.
     *
     * @param User $user User object.
     * @return bool True if the user object was successfully loaded.
     */
    abstract public function User_load(User $user): bool;

    /**
     * Save user object.
     *
     * @param User $user User object.
     * @return bool True if the user object was successfully saved.
     */
    abstract public function User_save(User $user): bool;

    /**
     * Delete user object.
     *
     * @param User $user User object.
     * @return bool True if the user object was successfully deleted.
     */
    abstract public function User_delete(User $user): bool;

    /**
     * Create data connector object.
     *
     * A type and table name prefix are required to make a database connection.
     * The default is to use MySQL with no prefix.
     *
     * If a data connector object is passed, then this is returned unchanged.
     *
     * If the $data_connector parameter is a string, this is used as the prefix.
     *
     * If the $data_connector parameter is an array, the first entry should be a
     * prefix string and an optional second entry being a string containing the
     * database type or a database connection object (e.g. the value returned by
     * a call to mysqli_connect() or a PDO object).  A bespoke data connector
     * class can be specified in the optional third parameter.
     *
     * @param mixed       $data_connector A data connector object, string or array
     * @param mixed|null  $db             A database connection object or string (optional)
     * @param string|null $type           The type of data connector (optional)
     *
     * @return AbstractDataConnector Data connector object
     */
    public static function getDataConnector(
        mixed $data_connector,
        mixed $db = null,
        string $type = null
    ): AbstractDataConnector {
        if (!is_object($data_connector) || !is_subclass_of($data_connector, get_class())) {
            $prefix = null;
            if (is_string($data_connector)) {
                $prefix = $data_connector;
            } elseif (is_array($data_connector)) {
                for ($i = 0; $i < min(count($data_connector), 3); $i++) {
                    if (is_string($data_connector[$i])) {
                        if (is_null($prefix)) {
                            $prefix = $data_connector[$i];
                        } elseif (is_null($type)) {
                            $type = $data_connector[$i];
                        }
                    } elseif (is_null($db)) {
                        $db = $data_connector[$i];
                    }
                }
            } elseif (is_object($data_connector)) {
                $db = $data_connector;
            }
            if (is_null($prefix)) {
                $prefix = '';
            }
            if (!is_null($db)) {
                if (is_string($db)) {
                    $type = $db;
                } elseif (is_null($type)) {
                    if (is_object($db)) {
                        $type = get_class($db);
                    } else {
                        $type = 'mysql';
                    }
                }
            }
            if (is_null($type)) {
                $type = 'mysql';
            }
            $type = strtolower($type);
            $type = "DataConnector{$type}";

            if (is_null($db)) {
                $data_connector = new $type($prefix);
            } else {
                $data_connector = new $type($db, $prefix);
            }
        }

        return $data_connector;
    }

    /**
     * Generate a random string.
     *
     * The generated string will only comprise letters (upper- and lower-case)
     * and digits.
     *
     * @param int $length Length of string to be generated (optional, default is
     *                    8 characters).
     * @return string Random string.
     */
    public static function getRandomString(int $length = 8): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

        $value = '';
        $charsLength = strlen($chars) - 1;

        for ($i = 1; $i <= $length; $i++) {
            $value .= $chars[rand(0, $charsLength)];
        }

        return $value;
    }

    /**
     * Quote a string for use in a database query.
     *
     * Any single quotes in the value passed will be replaced with two single
     * quotes.  If a null value is passed, a string of 'NULL' is returned (which
     * will never be enclosed in quotes irrespective of the value of the
     * $addQuotes parameter.
     *
     * @param ?string $value     Value to be quoted.
     * @param bool    $addQuotes If true the returned string will be enclosed in
     *                           single quotes (optional, default is true).
     *
     * @return null|bool|string True if the user object was successfully deleted.
     */
    public static function quoted(
        ?string $value,
        bool $addQuotes = true
    ): null|bool|string {
        if (is_null($value)) {
            $value = 'NULL';
        } else {
            $value = str_replace('\'', '\'\'', $value);
            if ($addQuotes) {
                $value = "'{$value}'";
            }
        }

        return $value;
    }
}
