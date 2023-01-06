<?php

declare(strict_types=1);

namespace Oscelot\Lti;

use Exception;
use Oscelot\OAuth\Request;
use Oscelot\OAuth\Server;
use Oscelot\OAuth\SignatureMethodHmacSha1;

/**
 * LTI Tool Provider
 */
class ToolProvider
{
    public const CONNECTION_ERROR_MESSAGE = 'Sorry, there was an error connecting you to the application.';

    // LTI version for messages.
    public const LTI_VERSION = 'LTI-1p0';

    // Use ID value only.
    public const ID_SCOPE_ID_ONLY = 0;

    //Prefix an ID with the consumer key.
    public const ID_SCOPE_GLOBAL = 1;

    // Prefix the ID with the consumer key and context ID.
    public const ID_SCOPE_CONTEXT = 2;

    // Prefix the ID with the consumer key and resource ID.
    public const ID_SCOPE_RESOURCE = 3;

    // Character used to separate each element of an ID.
    public const ID_SCOPE_SEPARATOR = ':';

    // True if the last request was successful.
    public bool $isOK = true;

    // ToolConsumer object.
    public ?ToolConsumer $consumer = null;

    // Return URL provided by tool consumer.
    public ?string $return_url = null;

    // User object.
    public ?User $user = null;

    // ResourceLink object.
    public ?ResourceLink $resource_link = null;

    /**
     *  @deprecated Use resource_link instead
     *  @see ToolProvider::$resource_link
     */
    public ?Context $context = null;

    public ?AbstractDataConnector $data_connector = null;

    // Default email domain.
    public string $defaultEmail = '';

    // Scope to use for user IDs.
    public int $id_scope = self::ID_SCOPE_ID_ONLY;

    // True if shared resource link arrangements are permitted.
    public bool $allowSharing = false;

    // Message for last request processed.
    public ?string $message = self::CONNECTION_ERROR_MESSAGE;

    // Error message for last request processed.
    public ?string $reason = null;

    // URL to redirect user to on successful completion of the request.
    private ?string $redirectURL = null;

    // Callback functions for handling requests.
    private ?array $callbackHandler = null;

    // HTML to be displayed on successful completion of the request.
    private ?string $output = null;

    // URL to redirect user to if the request is not successful.
    private ?string $error = null;

    // True if debug messages explaining the cause of errors are to be returned to the tool consumer.
    private bool $debugMode = false;

    // Array of LTI parameter constraints for auto validation checks.
    private ?array $constraints = null;

    // Names of LTI parameters to be retained in the settings property.
    private array $lti_settings_names = [
        'ext_resource_link_content',
        'ext_resource_link_content_signature',
        'lis_result_sourcedid',
        'lis_outcome_service_url',
        'ext_ims_lis_basic_outcome_url',
        'ext_ims_lis_resultvalue_sourcedids',
        'ext_ims_lis_memberships_id',
        'ext_ims_lis_memberships_url',
        'ext_ims_lti_tool_setting',
        'ext_ims_lti_tool_setting_id',
        'ext_ims_lti_tool_setting_url'
    ];

    /**
     * Class constructor
     *
     * @param mixed $callbackHandler String containing name of callback
     *                               function for connect request, or
     *                               associative array of callback functions for
     *                               each request type.
     * @param mixed $data_connector  String containing table name prefix, or
     *                               database connection object, or array
     *                               containing one or both values (optional,
     *                               default is a blank prefix and MySQL).
     */
    public function __construct(mixed $callbackHandler, mixed $data_connector = '')
    {

        if (!is_array($callbackHandler)) {
            $this->callbackHandler['connect'] = $callbackHandler;
        } elseif (isset($callbackHandler['connect'])) {
            $this->callbackHandler = $callbackHandler;
        } elseif (count($callbackHandler) > 0) {
            $callbackHandlers = array_values($callbackHandler);
            $this->callbackHandler['connect'] = $callbackHandlers[0];
        }
        $this->data_connector = $data_connector;
        $this->constraints = array();
        $this->context = &$this->resource_link;
    }

    /**
     * Process a launch request
     *
     * @return void Returns TRUE or FALSE, a redirection URL or HTML
     */
    public function execute(): void
    {
        // Initialise data connector
        $this->data_connector = AbstractDataConnector::getDataConnector($this->data_connector);

        // Set return URL if available
        if (isset($_POST['launch_presentation_return_url'])) {
            $this->return_url = $_POST['launch_presentation_return_url'];
        }

        // Perform action
        if ($this->authenticate()) {
            $this->doCallback();
        }
        $this->result();
    }

    /**
     * Add a parameter constraint to be checked on launch
     *
     * @param string  $name       Name of parameter to be checked.
     * @param boolean $required   True if parameter is required.
     * @param ?int    $max_length Maximum permitted length of parameter value
     *                         (optional, default is NULL).
     */
    public function setParameterConstraint(
        string $name,
        bool $required,
        ?int $max_length = null
    ) {
        $name = trim($name);
        if (strlen($name) > 0) {
            $this->constraints[$name] = array('required' => $required, 'max_length' => $max_length);
        }
    }

    /**
     * Get an array of defined tool consumers
     *
     * @return array Array of ToolConsumer objects
     */
    public function getConsumers(): array
    {
        // Initialise data connector
        $this->data_connector = AbstractDataConnector::getDataConnector($this->data_connector);

        return $this->data_connector->Tool_Consumer_list();
    }

    /**
     * Get an array of fully qualified user roles
     *
     * @param string $rolesString Comma-separated list of roles
     *
     * @return array Array of roles
     */
    public static function parseRoles(string $rolesString): array
    {
        $rolesArray = explode(',', $rolesString);
        $roles = array();
        foreach ($rolesArray as $role) {
            $role = trim($role);
            if (!empty($role)) {
                if (substr($role, 0, 4) != 'urn:') {
                    $role = 'urn:lti:role:ims/lis/' . $role;
                }
                $roles[] = $role;
            }
        }

        return $roles;
    }

    /**
     * Call any callback function for the requested action.
     *
     * This function may set the redirectURL and output properties.
     *
     * @return void True if no error reported
     */
    private function doCallback(): void
    {
        if (isset($this->callbackHandler['connect'])) {
            $result = call_user_func($this->callbackHandler['connect'], $this);

            // Callback function may return HTML, a redirect URL, or a boolean value
            if (is_string($result)) {
                if ((substr($result, 0, 7) == 'http://') || (substr($result, 0, 8) == 'https://')) {
                    $this->redirectURL = $result;
                } else {
                    if (is_null($this->output)) {
                        $this->output = '';
                    }
                    $this->output .= $result;
                }
            } elseif (is_bool($result)) {
                $this->isOK = $result;
            }
        }
    }

    /**
     * Perform the result of an action.
     *
     * This function may redirect the user to another URL rather than returning a value.
     *
     * @return string Output to be displayed (redirection, or display HTML or message)
     */
    private function result(): string {

        $ok = false;
        if (!$this->isOK && isset($this->callbackHandler['error'])) {
            $ok = call_user_func($this->callbackHandler['error'], $this);
        }
        if (!$ok) {
            if (!$this->isOK) {
      #
      ### If not valid, return an error message to the tool consumer if a return URL is provided
      #
                if (!empty($this->return_url)) {
                    $this->error = $this->return_url;
                    if (strpos($this->error, '?') === false) {
                        $this->error .= '?';
                    } else {
                        $this->error .= '&';
                    }
                    if ($this->debugMode && !is_null($this->reason)) {
                          $this->error .= 'lti_errormsg=' . urlencode("Debug error: $this->reason");
                    } else {
                        $this->error .= 'lti_errormsg=' . urlencode($this->message);
                        if (!is_null($this->reason)) {
                            $this->error .= '&lti_errorlog=' . urlencode("Debug error: $this->reason");
                        }
                    }
                } elseif ($this->debugMode) {
                    $this->error = $this->reason;
                }
                if (is_null($this->error)) {
                    $this->error = $this->message;
                }
                if ((substr($this->error, 0, 7) == 'http://') || (substr($this->error, 0, 8) == 'https://')) {
                    header("Location: {$this->error}");
                } else {
                    echo "Error: {$this->error}";
                }
            } elseif (!is_null($this->redirectURL)) {
                header("Location: {$this->redirectURL}");
            } elseif (!is_null($this->output)) {
                echo $this->output;
            }
        }
    }

    /**
     * Check the authenticity of the LTI launch request.
     *
     * The consumer, resource link and user objects will be initialised if the request is valid.
     *
     * @return bool True if the request has been successfully validated.
     */
    private function authenticate(): bool
    {
        // Set debug mode.
        $this->debugMode = isset($_POST['custom_debug']) && (strtolower($_POST['custom_debug']) == 'true');

        // Get the consumer
        $doSaveConsumer = false;

        // Check all required launch parameter constraints
        $this->isOK = isset($_POST['oauth_consumer_key']);
        if ($this->isOK) {
            $this->isOK = isset($_POST['lti_message_type']) && ($_POST['lti_message_type'] == 'basic-lti-launch-request');
        }
        if ($this->isOK) {
            $this->isOK = isset($_POST['lti_version']) && ($_POST['lti_version'] == self::LTI_VERSION);
        }
        if ($this->isOK) {
            $this->isOK = isset($_POST['resource_link_id']) && (strlen(trim($_POST['resource_link_id'])) > 0);
        }

        // Check consumer key
        if ($this->isOK) {
            $this->consumer = new ToolConsumer($_POST['oauth_consumer_key'], $this->data_connector);
            $this->isOK = !is_null($this->consumer->created);
            if ($this->debugMode && !$this->isOK) {
                $this->reason = 'Invalid consumer key.';
            }
        }
        $now = time();
        if ($this->isOK) {
            $today = date('Y-m-d', $now);
            if (is_null($this->consumer->last_access)) {
                $doSaveConsumer = true;
            } else {
                $last = date('Y-m-d', $this->consumer->last_access);
                $doSaveConsumer = $doSaveConsumer || ($last != $today);
            }
            $this->consumer->last_access = $now;
            try {
                $store = new OAuthDataStore($this);
                $server = new Server($store);
                $method = new SignatureMethodHmacSha1();
                $server->add_signature_method($method);
                $request = Request::from_request();
                $res = $server->verify_request($request);
            } catch (Exception $e) {
                $this->isOK = false;
                if (empty($this->reason)) {
                    $this->reason = 'OAuth signature check failed - perhaps an incorrect secret or timestamp.';
                }
            }
        }
        if ($this->isOK && $this->consumer->protected) {
            if (!is_null($this->consumer->consumer_guid)) {
                $this->isOK = isset($_POST['tool_consumer_instance_guid'])
                    && !empty($_POST['tool_consumer_instance_guid'])
                    && ($this->consumer->consumer_guid == $_POST['tool_consumer_instance_guid']);

                if ($this->debugMode && !$this->isOK) {
                    $this->reason = 'Request is from an invalid tool consumer.';
                }
            } else {
                $this->isOK = isset($_POST['tool_consumer_instance_guid']);
                if ($this->debugMode && !$this->isOK) {
                    $this->reason = 'A tool consumer GUID must be included in the launch request.';
                }
            }
        }
        if ($this->isOK) {
            $this->isOK = $this->consumer->enabled;
            if ($this->debugMode && !$this->isOK) {
                $this->reason = 'Tool consumer has not been enabled by the tool provider.';
            }
        }
        if ($this->isOK) {
            $this->isOK = is_null($this->consumer->enable_from) || ($this->consumer->enable_from <= $now);
            if ($this->isOK) {
                $this->isOK = is_null($this->consumer->enable_until) || ($this->consumer->enable_until > $now);
                if ($this->debugMode && !$this->isOK) {
                    $this->reason = 'Tool consumer access has expired.';
                }
            } elseif ($this->debugMode) {
                $this->reason = 'Tool consumer access is not yet available.';
            }
        }

        // Validate launch parameters
        if ($this->isOK) {
            $invalid_parameters = array();
            foreach ($this->constraints as $name => $constraint) {
                $ok = true;
                if ($constraint['required']) {
                    if (!isset($_POST[$name]) || (strlen(trim($_POST[$name])) <= 0)) {
                        $invalid_parameters[] = $name;
                        $ok = false;
                    }
                }
                if ($ok && !is_null($constraint['max_length']) && isset($_POST[$name])) {
                    if (strlen(trim($_POST[$name])) > $constraint['max_length']) {
                        $invalid_parameters[] = $name;
                    }
                }
            }
            if (count($invalid_parameters) > 0) {
                $this->isOK = false;
                if (empty($this->reason)) {
                    $this->reason = 'Invalid parameter(s): ' . implode(', ', $invalid_parameters) . '.';
                }
            }
        }

        if ($this->isOK) {
            $this->consumer->defaultEmail = $this->defaultEmail;
    #
    ### Set the request context/resource link
    #
            $this->resource_link = new ResourceLink($this->consumer, trim($_POST['resource_link_id']));
            if (isset($_POST['context_id'])) {
                $this->resource_link->lti_context_id = trim($_POST['context_id']);
            }
            $this->resource_link->lti_resource_id = trim($_POST['resource_link_id']);
            $title = '';
            if (isset($_POST['context_title'])) {
                $title = trim($_POST['context_title']);
            }
            if (isset($_POST['resource_link_title']) && (strlen(trim($_POST['resource_link_title'])) > 0)) {
                if (!empty($title)) {
                    $title .= ': ';
                }
                $title .= trim($_POST['resource_link_title']);
            }
            if (empty($title)) {
                $title = "Course {$this->resource_link->getId()}";
            }
            $this->resource_link->title = $title;
    // Save LTI parameters
            foreach ($this->lti_settings_names as $name) {
                if (isset($_POST[$name])) {
                    $this->resource_link->setSetting($name, $_POST[$name]);
                } else {
                    $this->resource_link->setSetting($name, null);
                }
            }
    // Delete any existing custom parameters
            foreach ($this->resource_link->getSettings() as $name => $value) {
                if (strpos($name, 'custom_') === 0) {
                    $this->resource_link->setSetting($name);
                }
            }
    // Save custom parameters
            foreach ($_POST as $name => $value) {
                if (strpos($name, 'custom_') === 0) {
                    $this->resource_link->setSetting($name, $value);
                }
            }
    #
    ### Set the user instance
    #
            $user_id = '';
            if (isset($_POST['user_id'])) {
                $user_id = trim($_POST['user_id']);
            }
            $this->user = new User($this->resource_link, $user_id);
    #
    ### Set the user name
    #
            $firstname = (isset($_POST['lis_person_name_given'])) ? $_POST['lis_person_name_given'] : '';
            $lastname = (isset($_POST['lis_person_name_family'])) ? $_POST['lis_person_name_family'] : '';
            $fullname = (isset($_POST['lis_person_name_full'])) ? $_POST['lis_person_name_full'] : '';
            $this->user->setNames($firstname, $lastname, $fullname);
    #
    ### Set the user email
    #
            $email = (isset($_POST['lis_person_contact_email_primary'])) ? $_POST['lis_person_contact_email_primary'] : '';
            $this->user->setEmail($email, $this->defaultEmail);
    #
    ### Set the user roles
    #
            if (isset($_POST['roles'])) {
                $this->user->roles = self::parseRoles($_POST['roles']);
            }
    #
    ### Save the user instance
    #
            if (isset($_POST['lis_result_sourcedid'])) {
                if ($this->user->lti_result_sourcedid != $_POST['lis_result_sourcedid']) {
                  // custom fix start
                    if (is_null($this->resource_link->created)) {
                        $this->resource_link->save();
                    }
                  // custom fix end
                    $this->user->lti_result_sourcedid = $_POST['lis_result_sourcedid'];
                    $this->user->save();
                }
            } elseif (!empty($this->user->lti_result_sourcedid)) {
                $this->user->delete();
            }
    #
    ### Initialise the consumer and check for changes
    #
            if ($this->consumer->lti_version != $_POST['lti_version']) {
                $this->consumer->lti_version = $_POST['lti_version'];
                $doSaveConsumer = true;
            }
            if (isset($_POST['tool_consumer_instance_name'])) {
                if ($this->consumer->consumer_name != $_POST['tool_consumer_instance_name']) {
                    $this->consumer->consumer_name = $_POST['tool_consumer_instance_name'];
                    $doSaveConsumer = true;
                }
            }
            if (isset($_POST['tool_consumer_info_product_family_code'])) {
                $version = $_POST['tool_consumer_info_product_family_code'];
                if (isset($_POST['tool_consumer_info_version'])) {
                    $version .= "-{$_POST['tool_consumer_info_version']}";
                }
      // do not delete any existing consumer version if none is passed
                if ($this->consumer->consumer_version != $version) {
                    $this->consumer->consumer_version = $version;
                    $doSaveConsumer = true;
                }
            } elseif (isset($_POST['ext_lms']) && ($this->consumer->consumer_name != $_POST['ext_lms'])) {
                $this->consumer->consumer_version = $_POST['ext_lms'];
                $doSaveConsumer = true;
            }
            if (isset($_POST['tool_consumer_instance_guid'])) {
                if (is_null($this->consumer->consumer_guid)) {
                    $this->consumer->consumer_guid = $_POST['tool_consumer_instance_guid'];
                    $doSaveConsumer = true;
                } elseif (!$this->consumer->protected) {
                    $doSaveConsumer = ($this->consumer->consumer_guid != $_POST['tool_consumer_instance_guid']);
                    if ($doSaveConsumer) {
                        $this->consumer->consumer_guid = $_POST['tool_consumer_instance_guid'];
                    }
                }
            }
            if (isset($_POST['launch_presentation_css_url'])) {
                if ($this->consumer->css_path != $_POST['launch_presentation_css_url']) {
                    $this->consumer->css_path = $_POST['launch_presentation_css_url'];
                    $doSaveConsumer = true;
                }
            } elseif (isset($_POST['ext_launch_presentation_css_url']) &&
            ($this->consumer->css_path != $_POST['ext_launch_presentation_css_url'])) {
                $this->consumer->css_path = $_POST['ext_launch_presentation_css_url'];
                $doSaveConsumer = true;
            } elseif (!empty($this->consumer->css_path)) {
                $this->consumer->css_path = null;
                $doSaveConsumer = true;
            }
        }
  #
  ### Persist changes to consumer
  #
        if ($doSaveConsumer) {
            $this->consumer->save();
        }

        if ($this->isOK) {
    #
    ### Check if a share arrangement is in place for this resource link
  #
            $this->isOK = $this->checkForShare();
  #
  ### Persist changes to resource link
  #
            $this->resource_link->save();
        }

        return $this->isOK;
    }

/**
 * Check if a share arrangement is in place.
 *
 * @return boolean True if no error is reported
 */
    private function checkForShare()
    {

        $ok = true;
        $doSaveResourceLink = true;

        $key = $this->resource_link->primary_consumer_key;
        $id = $this->resource_link->primary_resource_link_id;

        $shareRequest = isset($_POST['custom_share_key']) && !empty($_POST['custom_share_key']);
        if ($shareRequest) {
            if (!$this->allowSharing) {
                $ok = false;
                $this->reason = 'Your sharing request has been refused because sharing is not being permitted.';
            } else {
      // Check if this is a new share key
                $share_key = new ResourceLinkShareKey($this->resource_link, $_POST['custom_share_key']);
                if (!is_null($share_key->primary_consumer_key) && !is_null($share_key->primary_resource_link_id)) {
          // Update resource link with sharing primary resource link details
                    $key = $share_key->primary_consumer_key;
                    $id = $share_key->primary_resource_link_id;
                    $ok = ($key != $this->consumer->getKey()) || ($id != $this->resource_link->getId());
                    if ($ok) {
                        $this->resource_link->primary_consumer_key = $key;
                        $this->resource_link->primary_resource_link_id = $id;
                        $this->resource_link->share_approved = $share_key->auto_approve;
                        $ok = $this->resource_link->save();
                        if ($ok) {
                            $doSaveResourceLink = false;
                            $this->user->getResourceLink()->primary_consumer_key = $key;
                            $this->user->getResourceLink()->primary_resource_link_id = $id;
                            $this->user->getResourceLink()->share_approved = $share_key->auto_approve;
                            $this->user->getResourceLink()->updated = time();
                  // Remove share key
                            $share_key->delete();
                        } else {
                            $this->reason = 'An error occurred initialising your share arrangement.';
                        }
                    } else {
                        $this->reason = 'It is not possible to share your resource link with yourself.';
                    }
                }
                if ($ok) {
                    $ok = !is_null($key);
                    if (!$ok) {
                        $this->reason = 'You have requested to share a resource link but none is available.';
                    } else {
                        $ok = (!is_null($this->user->getResourceLink()->share_approved) && $this->user->getResourceLink()->share_approved);
                        if (!$ok) {
                            $this->reason = 'Your share request is waiting to be approved.';
                        }
                    }
                }
            }
        } else {
    // Check no share is in place
            $ok = is_null($key);
            if (!$ok) {
                $this->reason = 'You have not requested to share a resource link but an arrangement is currently in place.';
            }
        }

  // Look up primary resource link
        if ($ok && !is_null($key)) {
            $consumer = new ToolConsumer($key, $this->data_connector);
            $ok = !is_null($consumer->created);
            if ($ok) {
                $resource_link = new ResourceLink($consumer, $id);
                $ok = !is_null($resource_link->created);
            }
            if ($ok) {
                if ($doSaveResourceLink) {
                    $this->resource_link->save();
                }
                $this->resource_link = $resource_link;
            } else {
                $this->reason = 'Unable to load resource link being shared.';
            }
        }

        return $ok;
    }
}
