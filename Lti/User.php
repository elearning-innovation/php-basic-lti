<?php

declare(strict_types=1);

namespace Oscelot\Lti;

use AllowDynamicProperties;

/**
 * Class to represent a tool consumer user
 */
#[AllowDynamicProperties]
class User
{
    /**
     * User's first name.
     */
    public string $firstname = '';

    /**
     * User's last name (surname or family name).
     */
    public string $lastname = '';

    /**
     * User's full name.
     */
    public string $fullname = '';

    /**
     * User's email address.
     */
    public string $email = '';

    /**
     * Array of roles for user.
     */
    public array $roles = array();

    /**
     * Array of groups for user.
     */
    public array $groups = array();

    /**
     * User's result sourcedid.
     */
    public mixed $lti_result_sourcedid = null;

    /**
     * Date/time the record was created.
     */
    public mixed $created = null;

    /**
     * Date/time the record was last updated.
     */
    public mixed $updated = null;

    /**
     * ResourceLink object.
     */
    private ?ResourceLink $resource_link;

     /**
     * LTI_Context object.
     */
    private ?ResourceLink $context = null;

    /**
     * User ID value.
     */
    private ?string $id;

    /**
     * Class constructor.
     *
     * @param ResourceLink $resource_link Resource_Link object
     * @param string       $id            User ID value
     */
    public function __construct(ResourceLink $resource_link, string $id)
    {
        $this->initialise();
        $this->resource_link = $resource_link;
        /** @noinspection UnusedConstructorDependenciesInspection */
        $this->context = &$this->resource_link;
        $this->id = $id;
        $this->load();
    }

    /**
     * Initialise the user.
     */
    public function initialise(): void
    {
        $this->firstname = '';
        $this->lastname = '';
        $this->fullname = '';
        $this->email = '';
        $this->roles = array();
        $this->groups = array();
        $this->lti_result_sourcedid = null;
        $this->created = null;
        $this->updated = null;
    }

    /**
     * Load the user from the database.
     */
    public function load(): void
    {
        $this->initialise();
        $this->resource_link->getConsumer()?->getDataConnector()->User_load($this);
    }

    /**
     * Save the user to the database.
     *
     * @return bool True if the user object was successfully saved
     */
    public function save(): bool
    {
        if (!empty($this->lti_result_sourcedid)) {
            $ok = $this->resource_link->getConsumer()?->getDataConnector()->User_save($this);
        } else {
            $ok = true;
        }

        return $ok;
    }

    /**
     * Delete the user from the database.
     *
     * @return bool True if the user object was successfully deleted
     */
    public function delete(): bool
    {
        return $this->resource_link->getConsumer()?->getDataConnector()->User_delete($this);
    }

    /**
     * Get resource link.
     *
     * @return ResourceLink Resource link object
     */
    public function getResourceLink(): ResourceLink
    {
        return $this->resource_link;
    }

    /**
     * Get context.
     *
     * @return ResourceLink Context object
     * @noinspection PhpUnused*@see User::getResourceLink()
     *
     * @deprecated Use getResourceLink() instead
     */
    public function getContext(): ResourceLink
    {
        return $this->resource_link;
    }

    /**
     * Get the user ID (which may be a compound of the tool consumer and resource link IDs).
     *
     * @param ?int $id_scope Scope to use for user ID (optional, default is null for consumer default setting)
     *
     * @return string User ID value
     */
    public function getId(?int $id_scope = null): string
    {
        if (empty($id_scope)) {
            $id_scope = $this->resource_link->getConsumer()->id_scope;
        }
        switch ($id_scope) {
            case ToolProvider::ID_SCOPE_GLOBAL:
                $id = $this->resource_link->getKey() . ToolProvider::ID_SCOPE_SEPARATOR . $this->id;
                break;
            case ToolProvider::ID_SCOPE_CONTEXT:
                $id = $this->resource_link->getKey();
                if ($this->resource_link->lti_context_id) {
                    $id .= ToolProvider::ID_SCOPE_SEPARATOR . $this->resource_link->lti_context_id;
                }
                $id .= ToolProvider::ID_SCOPE_SEPARATOR . $this->id;
                break;
            case ToolProvider::ID_SCOPE_RESOURCE:
                $id = $this->resource_link->getKey();
                if ($this->resource_link->lti_resource_id) {
                    $id .= ToolProvider::ID_SCOPE_SEPARATOR . $this->resource_link->lti_resource_id;
                }
                $id .= ToolProvider::ID_SCOPE_SEPARATOR . $this->id;
                break;
            default:
                $id = $this->id;
                break;
        }

        return $id;
    }

    /**
     * Set the user's name.
     *
     * @param string $firstname User's first name.
     * @param string $lastname  User's last name.
     * @param string $fullname  User's full name.
     */
    public function setNames(
        string $firstname,
        string $lastname,
        string $fullname
    ): void {
        $names = array(0 => '', 1 => '');
        if (!empty($fullname)) {
            $this->fullname = trim($fullname);
            /** @noinspection RegExpSimplifiable */
            $names = preg_split("/[\s]+/", $this->fullname, 2);
        }
        if (!empty($firstname)) {
            $this->firstname = trim($firstname);
            $names[0] = $this->firstname;
        } elseif (!empty($names[0])) {
            $this->firstname = $names[0];
        } else {
            $this->firstname = 'User';
        }
        if (!empty($lastname)) {
            $this->lastname = trim($lastname);
            $names[1] = $this->lastname;
        } elseif (!empty($names[1])) {
            $this->lastname = $names[1];
        } else {
            $this->lastname = $this->id;
        }
        if (empty($this->fullname)) {
            $this->fullname = "{$this->firstname} {$this->lastname}";
        }
    }

    /**
     * Set the user's email address.
     *
     * @param string      $email        Email address value
     * @param ?string $defaultEmail Value to use if no email is provided (optional, default is none)
     */
    public function setEmail(string $email, ?string $defaultEmail = null): void
    {
        if (!empty($email)) {
            $this->email = $email;
        } elseif (!empty($defaultEmail)) {
            $this->email = $defaultEmail;
            if (substr($this->email, 0, 1) == '@') {
                $this->email = $this->getId() . $this->email;
            }
        } else {
            $this->email = '';
        }
    }

    /**
     * Check if the user is an administrator (at any of the system, institution or context levels).
     *
     * @return bool True if the user has a role of administrator
     * @noinspection PhpUnused
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('Administrator')
            || $this->hasRole('urn:lti:sysrole:ims/lis/SysAdmin')
            || $this->hasRole('urn:lti:sysrole:ims/lis/Administrator')
            || $this->hasRole('urn:lti:instrole:ims/lis/Administrator');
    }

    /**
     * Check if the user is staff.
     *
     * @return bool True if the user has a role of instructor, contentdeveloper or teachingassistant
     */
    public function isStaff(): bool
    {
        return (
            $this->hasRole('Instructor')
            || $this->hasRole('ContentDeveloper')
            || $this->hasRole('TeachingAssistant')
        );
    }

    /**
     * Check if the user is a learner.
     *
     * @return bool True if the user has a role of learner
     */
    public function isLearner(): bool
    {
        return $this->hasRole('Learner');
    }

    /**
     * Check whether the user has a specified role name.
     *
     * @param string $role Name of role
     *
     * @return bool True if the user has the specified role
     */
    private function hasRole(string $role): bool
    {
        if (substr($role, 0, 4) != 'urn:') {
            $role = 'urn:lti:role:ims/lis/' . $role;
        }

        return in_array($role, $this->roles);
    }
}
