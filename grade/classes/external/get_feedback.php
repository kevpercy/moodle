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

namespace core_grades\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/grade/lib.php');

/**
 * Web service to fetch students feedback for a grade item.
 *
 * @package    core_grades
 * @copyright  2023 Kevin Percy <kevin.percy@moodle.com>
 * @category   external
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_feedback extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters (
            [
                'userid' => new external_value(PARAM_INT, 'User ID', VALUE_REQUIRED),
                'itemid' => new external_value(PARAM_INT, 'Grade Item ID', VALUE_REQUIRED)
            ]
        );
    }

    /**
     * Given a user ID and grade item ID, return feedback and user details.
     *
     * @param int $userid
     * @param int $itemid
     * @return array Feedback and user details
     */
    public static function execute(int $userid, int $itemid): array {
        global $DB, $OUTPUT, $PAGE;

        $params = self::validate_parameters(
            self::execute_parameters(),
            [
                'userid' => $userid,
                'itemid' => $itemid
            ]
        );

        $gradeitem = $DB->get_record('grade_items', ['id' => $params['itemid']]);

        $context = \context_course::instance($gradeitem->courseid);
        parent::validate_context($context);
        $PAGE->set_context($context);

        require_capability('gradereport/grader:view', $context);

        $grade = $DB->get_record('grade_grades', ['userid' => $params['userid'], 'itemid' => $params['itemid']]);
        $user = \core_user::get_user($params['userid']);
        $extrafields = \core_user\fields::get_identity_fields($context);

        return [
            'feedbacktext' => $grade->feedback,
            'title' => $gradeitem->itemname,
            'fullname' => fullname($user),
            'picture' => $OUTPUT->user_picture($user, ['size' => 45, 'link' => false]),
            'additionalfield' => empty($extrafields) ? '' : $user->{$extrafields[0]},
        ];
    }

    /**
     * Describes the return structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'feedbacktext' => new external_value(PARAM_RAW, 'The full feedback text'),
            'title' => new external_value(PARAM_TEXT, 'Title of the grade item that the feedback is for'),
            'fullname' => new external_value(PARAM_TEXT, 'Students name'),
            'picture' => new external_value(PARAM_RAW, 'Students picture'),
            'additionalfield' => new external_value(PARAM_TEXT, 'Additional field for the user (email or ID number, for example)'),
        ]);
    }
}
