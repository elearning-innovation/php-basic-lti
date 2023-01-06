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
    const CONSUMER_TABLE_NAME = 'lti_consumer';

    /**
     * Default name for database table used to store resource links.
     */
    const RESOURCE_LINK_TABLE_NAME = 'lti_context';

    /**
     * Default name for database table used to store users.
     */
    const USER_TABLE_NAME = 'User';

    /**
     * Default name for database table used to store resource link share keys.
     */
    const RESOURCE_LINK_SHARE_KEY_TABLE_NAME = 'lti_share_key';

    /**
     * Default name for database table used to store nonce values.
     */
    const NONCE_TABLE_NAME = 'lti_nonce';

    /**
     * Load tool consumer object.
     *
     * @param mixed $consumer ToolConsumer object
     *
     * @return boolean True if the tool consumer object was successfully loaded
     */
    abstract public function Tool_Consumer_load($consumer);

    /**
     * Save tool consumer object.
     *
     * @param ToolConsumer $consumer Consumer object
     *
     * @return boolean True if the tool consumer object was successfully saved
     */
    abstract public function Tool_Consumer_save($consumer);

    /**
     * Delete tool consumer object.
     *
     * @param ToolConsumer $consumer Consumer object
     *
     * @return boolean True if the tool consumer object was successfully deleted
     */
    abstract public function Tool_Consumer_delete($consumer);

    /**
     * Load tool consumer objects.
     *
     * @return array Array of all defined ToolConsumer objects
     */
    abstract public function Tool_Consumer_list();

    /**
     * Load resource link object.
     *
     * @param ResourceLink $resource_link Resource_Link object
     *
     * @return boolean True if the resource link object was successfully loaded
     */
    abstract public function Resource_Link_load($resource_link);

    /**
     * Save resource link object.
     *
     * @param ResourceLink $resource_link Resource_Link object
     *
     * @return boolean True if the resource link object was successfully saved
     */
    abstract public function Resource_Link_save($resource_link);

    /**
     * Delete resource link object.
     *
     * @param ResourceLink $resource_link Resource_Link object
     *
     * @return boolean True if the Resource_Link object was successfully deleted
     */
    abstract public function Resource_Link_delete($resource_link);

    /**
     * Get array of user objects.
     *
     * @param ResourceLink $resource_link      Resource link object
     * @param boolean     $local_only True if only users within the resource link are to be returned (excluding users sharing this resource link)
     * @param int         $id_scope     Scope value to use for user IDs
     *
     * @return array Array of User objects
     */
    abstract public function Resource_Link_getUserResultSourcedIDs($resource_link, $local_only, $id_scope);

    /**
     * Get array of shares defined for this resource link.
     *
     * @param ResourceLink $resource_link Resource_Link object
     *
     * @return array Array of ResourceLink_Share objects
     */
    abstract public function Resource_Link_getShares($resource_link);

    /**
     * Load nonce object.
     *
     * @param ConsumerNonce $nonce Nonce object
     *
     * @return boolean True if the nonce object was successfully loaded
     */
    abstract public function Consumer_Nonce_load($nonce);

    /**
     * Save nonce object.
     *
     * @param ConsumerNonce $nonce Nonce object
     *
     * @return boolean True if the nonce object was successfully saved
     */
    abstract public function Consumer_Nonce_save($nonce);

    /**
     * Load resource link share key object.
     *
     * @param ResourceLinkShareKey $share_key Resource_Link share key object
     *
     * @return boolean True if the resource link share key object was successfully loaded
     */
    abstract public function Resource_Link_Share_Key_load($share_key);

    /**
     * Save resource link share key object.
     *
     * @param ResourceLinkShareKey $share_key Resource link share key object
     *
     * @return boolean True if the resource link share key object was successfully saved
     */
    abstract public function Resource_Link_Share_Key_save($share_key);

    /**
     * Delete resource link share key object.
     *
     * @param ResourceLinkShareKey $share_key Resource link share key object
     *
     * @return boolean True if the resource link share key object was successfully deleted
     */
    abstract public function Resource_Link_Share_Key_delete($share_key);

    /**
     * Load user object.
     *
     * @param User $user User object
     *
     * @return boolean True if the user object was successfully loaded
     */
    abstract public function User_load($user);

    /**
     * Save user object.
     *
     * @param User $user User object
     *
     * @return boolean True if the user object was successfully saved
     */
    abstract public function User_save($user);

    /**
     * Delete user object.
     *
     * @param User $user User object
     *
     * @return boolean True if the user object was successfully deleted
     */
    abstract public function User_delete($user);

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
     * The generated string will only comprise letters (upper- and lower-case) and digits.
     *
     * @param int $length Length of string to be generated (optional, default is 8 characters)
     *
     * @return string Random string
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
    public static function quoted(?string $value, bool $addQuotes = true): null|bool|string
    {
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
