<?php

/**
 * This plugin is to be used in combination with the LinkedIn authentication block
 *
 * @package    auth
 * @subpackage linkedin
 * @copyright  2012 Bas Brands
 * @copyright  2012 Bright Alley Knowledge and learning
 * @author     Bas Brands bmbrands@gmail.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once('../../config.php');

require_once($CFG->dirroot.'/auth/linkedin/linkedin_edituser_form.php');

//require_login();

$userid = optional_param('id', $USER->id, PARAM_INT);    // user id
$course = optional_param('course', SITEID, PARAM_INT);   // course id (defaults to Site)

$PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));
$PAGE->set_url('/auth/linkedin/linkedin_edituser', array('course'=>$course, 'id'=>$userid));
$PAGE->set_pagelayout('embedded');
$PAGE->set_title("userform");
$PAGE->set_heading("userform");

if (!$user = $DB->get_record('user', array('id'=>$userid))) {
	print_error('invaliduserid');
}

$userform = new linkedin_edituser_form();

if ($usernew = $userform->get_data()) {
	add_to_log('1', 'user', 'loginlinkedin', 'id = ' .$usernew->id . ' username = ' .$usernew->username, '');
	$DB->update_record('user', $usernew);

	$usernew = $DB->get_record('user', array('id'=>$user->id));
	if ($USER->id == $user->id) {
		// Override old $USER session variable if needed
		foreach ((array)$usernew as $variable => $value) {
			$USER->$variable = $value;
		}
	}
	events_trigger('user_updated', $usernew);
	$urltogo = $CFG->wwwroot.'/auth/linkedin/reloadhome.php';
	redirect($urltogo);
}

$userform->set_data($user);
echo $OUTPUT->header();
echo get_string('forminfo','auth_linkedin');
$userform->display();
echo $OUTPUT->footer();
