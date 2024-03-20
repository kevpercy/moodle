<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * URL configuration form
 *
 * @package    mod_url
 * @copyright  2009 Petr Skoda  {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once ($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot.'/mod/url/locallib.php');

class mod_url_mod_form extends moodleform_mod {
    function definition() {
        global $CFG, $DB;
        $mform = $this->_form;

        $config = get_config('url');

        //-------------------------------------------------------
        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'testing_a', 'Testing A', ['size' => 10]);
        $mform->setType('testing_a', PARAM_TEXT);
        $mform->addElement('text', 'testing_b', 'Testing B', ['size' => 10]);
        $mform->setType('testing_b', PARAM_TEXT);
        $mform->addElement('checkbox', 'element_to_show', 'This is a test and will hide if Testing A = 1, and Testing B = 2');

        /**
         * This will show the checkbox if Testing A is not 1, OR if Testing B is not 2.
         * This has the effect of hiding the checkbox if Testing A = 1 AND Testing B = 2.
         */
        $mform->showIf('element_to_show', 'testing_a', 'neq', 1);
        $mform->showIf('element_to_show', 'testing_b', 'neq', 2);

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    function data_preprocessing(&$default_values) {
        if (!empty($default_values['displayoptions'])) {
            $displayoptions = (array) unserialize_array($default_values['displayoptions']);
            if (isset($displayoptions['printintro'])) {
                $default_values['printintro'] = $displayoptions['printintro'];
            }
            if (!empty($displayoptions['popupwidth'])) {
                $default_values['popupwidth'] = $displayoptions['popupwidth'];
            }
            if (!empty($displayoptions['popupheight'])) {
                $default_values['popupheight'] = $displayoptions['popupheight'];
            }
        }
        if (!empty($default_values['parameters'])) {
            $parameters = (array) unserialize_array($default_values['parameters']);
            $i = 0;
            foreach ($parameters as $parameter=>$variable) {
                $default_values['parameter_'.$i] = $parameter;
                $default_values['variable_'.$i]  = $variable;
                $i++;
            }
        }
    }

    function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // Validating Entered url, we are looking for obvious problems only,
        // teachers are responsible for testing if it actually works.

        // This is not a security validation!! Teachers are allowed to enter "javascript:alert(666)" for example.

        // NOTE: do not try to explain the difference between URL and URI, people would be only confused...

        if (!empty($data['externalurl'])) {
            $url = $data['externalurl'];
            if (preg_match('|^/|', $url)) {
                // links relative to server root are ok - no validation necessary

            } else if (preg_match('|^[a-z]+://|i', $url) or preg_match('|^https?:|i', $url) or preg_match('|^ftp:|i', $url)) {
                // normal URL
                if (!url_appears_valid_url($url)) {
                    $errors['externalurl'] = get_string('invalidurl', 'url');
                }

            } else if (preg_match('|^[a-z]+:|i', $url)) {
                // general URI such as teamspeak, mailto, etc. - it may or may not work in all browsers,
                // we do not validate these at all, sorry

            } else {
                // invalid URI, we try to fix it by adding 'http://' prefix,
                // relative links are NOT allowed because we display the link on different pages!
                if (!url_appears_valid_url('http://'.$url)) {
                    $errors['externalurl'] = get_string('invalidurl', 'url');
                }
            }
        }
        return $errors;
    }

}
