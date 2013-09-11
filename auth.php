<?php

/**
 * This plugin is to be used in combination with the LinkedIn authentication block
 *
 * @package    auth
 * @subpackage linkedin
 * @copyright  2013 Bas Brands
 * @author     Bas Brands bas@sonsbeekmedia.nl
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once($CFG->libdir.'/authlib.php');


class auth_plugin_linkedin extends auth_plugin_base {

    /**
     * Constructor.
     */
    function auth_plugin_linkedin() {
        $this->authtype = 'linkedin';
        $this->roleauth = 'auth_linkedin';
        $this->errorlogtag = '[AUTH linkedin] ';
        $this->config = get_config('auth/linkedin');
    }

    /**
     * Prevent authenticate_user_login() to update the password in the DB
     * @return boolean
     */
    function prevent_local_passwords() {
        return true;
    }

    /**
     * Authenticates user against the selected authentication provide (Google, Facebook...)
     *
     * @param string $username The username (with system magic quotes)
     * @param string $password The password (with system magic quotes)
     * @return bool Authentication success or failure.
     */
    function user_login ($username, $password) {
        if (isset($_REQUEST['oauth_verifier'])){
            return true;
        } else {
            return false;
        }
    }

    /**
     * Returns true if this authentication plugin is 'internal'.
     *
     * @return bool
     */
    function is_internal() {
        return false;
    }

    /**
     * Returns true if this authentication plugin can change the user's
     * password.
     *
     * @return bool
     */
    function can_change_password() {
        return false;
    }

    /**
     * Authentication hook - is called every time user hit the login page
     * The code is run only if the param code is mentionned.
     */
    function loginpage_hook() {
        global $USER, $SESSION, $CFG, $DB;
        require_once( $CFG->dirroot . "/auth/linkedin/linkedin.php" );
        require_once( $CFG->libdir. "/gdlib.php" );

        $access = get_config("auth/linkedin", 'linkedin_access');
        $secret = get_config("auth/linkedin", 'linkedin_secret');

        $linkedin = new LinkedInAuth($access, $secret, $CFG->wwwroot.'/login/index.php' );

        $linkedinpost = false;

        if (isset($_REQUEST['oauth_verifier'])){
            $_SESSION['oauth_verifier']     = $_REQUEST['oauth_verifier'];

            $linkedin->request_token    =   unserialize($_SESSION['requestToken']);
            $linkedin->oauth_verifier   =   $_SESSION['oauth_verifier'];
            $linkedin->getAccessToken($_REQUEST['oauth_verifier']);

            $_SESSION['oauth_access_token'] = serialize($linkedin->access_token);

            $xml_response = $linkedin->getProfile("~:(id,email-address,first-name,last-name,headline,picture-url,location,industry,interests,public-profile-url,positions,summary)");
            $linkedin_response = new SimpleXMLElement($xml_response);
            $linkedinpost = true;
        }

        if (isset($linkedin_response->id)) {

            $username = clean_param($linkedin_response->id, PARAM_ALPHA);
            $user = $DB->get_record('user', array('username' => $username, 'deleted' => 0, 'mnethostid' => $CFG->mnet_localhost_id));

            if (empty($user)) {
                // Creating a new user object
                $newuser = new stdClass();
                $newuser->firstname =  (string) $linkedin_response->{'first-name'};
                $newuser->lastname =  (string) $linkedin_response->{'last-name'};
                $newuser->email =  (string) $linkedin_response->{'email-address'};

                $newuser->picture = 1;

                if (isset($linkedin_response->{'location'}->country->code)) {
                    $countrycode = (string) $linkedin_response->{'location'}->country->code;
                    $newuser->country = strtoupper($countrycode);
                }
                if (isset($linkedin_response->{'positions'}->position[0]->company->name)) {
                    $newuser->institution = substr($linkedin_response->{'positions'}->position[0]->company->name,0,39);
                }
                if (isset($linkedin_response->{'headline'})) {
                    $newuser->description = (string) $linkedin_response->{'headline'};
                }
                if (isset($linkedin_response->{'location'}->name)) {
                    $newuser->city = (string) $linkedin_response->{'location'}->name;
                }
                if (isset($linkedin_response->{'public-profile-url'})) {
                    $newuser->url = (string) $linkedin_response->{'public-profile-url'};
                }
                events_trigger('user_created', $newuser);
            }

            //Creating a new user account
            $user = authenticate_user_login($username, null);

            if ($user) {
                if (!empty($newuser)) {
                    $newuser->id = $user->id;
                    $newuser->username = $username;
                    $newuser->email =  (string) $linkedin_response->{'email-address'};

                    $DB->update_record('user', $newuser);
                    events_trigger('user_updated', $newuser);

                } else {

                    //Update profile url & description
                    if (isset($linkedin_response->{'public-profile-url'})) {
                        $profileurl = (array) $linkedin_response->{'public-profile-url'};
                        $user->url = $profileurl[0];
                    }
                    $countrycode = (string) $linkedin_response->{'location'}->country->code;
                    $user->country = strtoupper($countrycode);

                    $user->description = (string) $linkedin_response->headline;
                    $user->email =  (string) $linkedin_response->{'email-address'};
                    $DB->update_record('user', $user);
                }

                //Get the user picture
                $context = get_context_instance(CONTEXT_USER, $user->id, MUST_EXIST);
                $fs = get_file_storage();

                $linkedinurl  = $linkedin_response->{'picture-url'};
                $linkedinpic = $CFG->dataroot .  '/temp/' . $user->id . '-picture.jpg';

                //Download using Curl
                $ch = curl_init($linkedinurl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $data = curl_exec($ch);
                curl_close($ch);

                //Set the picture for new users
                file_put_contents($linkedinpic, $data);
                if (isset($newuser->id)) {
                    if (process_new_icon($context, 'user', 'icon', 0, $linkedinpic)) {
                        //$DB->set_field('user', 'picture', 1, array('id'=>$newuser->id));
                    }
                }

                complete_user_login($user);


                if (user_not_fully_set_up($user)) {
                    //Send users to the email completion form
                    $urltogo = $CFG->wwwroot.'/user/edit.php?id=' . $user->id;
                } else {
                    //Send back and reload homepage
                    $urltogo = $CFG->wwwroot.'/auth/linkedin/reloadhome.php';
                }
                redirect($urltogo);

            }   else {
                throw new moodle_exception('couldnotgetlinkedinaccesstoken', 'auth_linkedin');
            }

        }

    }



    function config_form($config, $err, $user_fields) {
        global $OUTPUT;

        // set to defaults if undefined
        if (!isset($config->linkedin_access)) {
            $config->linkedin_access = '';
        }
        if (!isset($config->linkedin_secret)) {
            $config->linkedin_secret = '';
        }


        echo '<table cellspacing="0" cellpadding="5" border="0">
            <tr>
               <td colspan="3">
                    <h2 class="main">';

        print_string('auth_linkedin', 'auth_linkedin');


        echo '</h2>
               </td>
            </tr>
            <tr>
                <td align="right"><label for="linkedinaccesskey">';

        print_string('auth_linkedin_access_key', 'auth_linkedin');

        echo '</label></td><td>';


        echo html_writer::empty_tag('input',
        array('type' => 'text', 'id' => 'linkedin_access', 'name' => 'linkedin_access',
                    'class' => 'linkedin_access', 'value' => $config->linkedin_access));

        if (isset($err["linkedin_access"])) {
            echo $OUTPUT->error_text($err["linkedin_access"]);
        }

        echo '</td><td>';

        print_string('auth_linkedin_access_details', 'auth_linkedin') ;

        echo '</td></tr>';


        echo '<tr>
                <td align="right"><label for="googleclientsecret">';

        print_string('auth_linkedin_secret_key', 'auth_linkedin');

        echo '</label></td><td>';


        echo html_writer::empty_tag('input',
        array('type' => 'text', 'id' => 'linkedin_secret', 'name' => 'linkedin_secret',
                    'class' => 'linkedin_secret', 'value' => $config->linkedin_secret));

        if (isset($err["linkedin_secret"])) {
            echo $OUTPUT->error_text($err["linkedin_secret"]);
        }

        echo '</td><td>';

        print_string('auth_linkedin_secret_details', 'auth_linkedin') ;

        echo '</td></tr>';

        echo '</table>';
    }

    /**
     * Processes and stores configuration data for this authentication plugin.
     */
    function process_config($config) {
        // set to defaults if undefined
        if (!isset ($config->linkedin_access)) {
            $config->linkedin_access = '';
        }
        if (!isset ($config->linkedin_secret)) {
            $config->linkedin_access = '';
        }
        set_config('linkedin_access', $config->linkedin_access, 'auth/linkedin');
        set_config('linkedin_secret', $config->linkedin_secret, 'auth/linkedin');

        return true;
    }


}
