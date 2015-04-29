<?php

/**
 * This plugin is to be used in combination with the LinkedIn authentication block
 *
 * @package    auth
 * @subpackage linkedin
 * @copyright  2013 Bas Brands, www.basbrands.nl
 * @author     Bas Brands bas@sonsbeekmedia.nl
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->version  = 2015042900;
$plugin->requires = 2014111000;
$plugin->release = '3 (Build: 2015042900)';
$plugin->maturity = MATURITY_STABLE;
$plugin->component = 'auth_linkedin';
$plugin->dependencies = array(
    'block_linkedin'  => 2013082100,
);