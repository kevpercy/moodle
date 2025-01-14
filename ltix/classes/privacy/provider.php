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

namespace core_ltix\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\writer;
use core_privacy\local\request\userlist;
use core_privacy\local\request\approved_userlist;

/**
 * Privacy Subsystem for core_ltix implementing null_provider.
 *
 * @package    core_ltix
 * @author     Alex Morris <alex.morris@catalyst.net.nz>
 * @copyright  2023 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    // core_ltix stores user data.
    \core_privacy\local\metadata\provider,

    // The core_ltix subsystem provides data to other components.
    \core_privacy\local\request\subsystem\plugin_provider,

    // This plugin is capable of determining which users have data within it.
    \core_privacy\local\request\core_userlist_provider,

    // The core_ltix subsystem may have data that belongs to this user.
    \core_privacy\local\request\plugin\provider,

    \core_privacy\local\request\shared_userlist_provider
{

    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        // Fetch all LTI types.
        $sql = "SELECT c.id
                 FROM {context} c
                 JOIN {course} course
                   ON c.contextlevel = :contextlevel
                  AND c.instanceid = course.id
                 JOIN {lti_types} ltit
                   ON ltit.course = course.id
                WHERE ltit.createdby = :userid";
        $params = [
            'contextlevel' => CONTEXT_COURSE,
            'userid' => $userid
        ];
        $contextlist->add_from_sql($sql, $params);

        // The LTI tool proxies sit in the system context.
        $contextlist->add_system_context();
        return $contextlist;
    }

    /**
     * Get SQL to retrieve all LTI instances where the user has been involved.
     *
     * @return array
     */
    public static function get_join_sql(int $userid) {
        $join = "INNER JOIN {lti_submission} ltisub
                ON ltisub.ltiid = lti.id ";

        $where = "WHERE ltisub.userid = :userid";

        return [
            'join' => $join,
            'where' => $where,
            'params' => ['userid' => $userid],
        ];
    }

    public static function export_lti_submissions(
        int $userid,
        \context $context,
        array $subcontext,
        string $component,
        string $itemtype,
        int $itemid,
    ) {
        // TODO: Update this when lti_instance table is fleshed out.
        global $DB;

        $sql = "SELECT *
                FROM {lti_submission} ls
                INNER JOIN {lti_instance} li ON li.ltiid = ls.id
                WHERE li.component = :component
                    AND li.itemtype = :itemtype
                    AND li.itemid = :itemid
                    AND ls.userid = :userid";

        $params = [
            'component' => $component,
            'itemtype' => $itemtype,
            'itemid' => $itemid,
            'userid' => $userid,
        ];

        if ($submissions = $DB->get_fieldset_sql($sql, $params)) {
            $writer = \core_privacy\local\request\writer::with_context($context)
                ->export_related_data($subcontext, 'submissions', $submissions);
        }
    }

    public static function export_user_data(approved_contextlist $contextlist) {
        self::export_user_data_lti_types($contextlist);
        self::export_user_data_lti_tool_proxies($contextlist);
    }

    /**
     * Export personal data for the given approved_contextlist related to LTI types.
     *
     * @param approved_contextlist $contextlist a list of contexts approved for export.
     */
    protected static function export_user_data_lti_types(approved_contextlist $contextlist) {
        global $DB;

        // Filter out any contexts that are not related to courses.
        $courseids = array_reduce($contextlist->get_contexts(), function($carry, $context) {
            if ($context->contextlevel == CONTEXT_COURSE) {
                $carry[] = $context->instanceid;
            }
            return $carry;
        }, []);

        if (empty($courseids)) {
            return;
        }

        $user = $contextlist->get_user();

        list($insql, $inparams) = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED);
        $params = array_merge($inparams, ['userid' => $user->id]);
        $ltitypes = $DB->get_recordset_select('lti_types', "course $insql AND createdby = :userid", $params, 'timecreated ASC');
        self::recordset_loop_and_export($ltitypes, 'course', [], function($carry, $record) {
            $context = \context_course::instance($record->course);
            $options = ['context' => $context];
            $carry[] = [
                'name' => format_string($record->name, true, $options),
                'createdby' => transform::user($record->createdby),
                'timecreated' => transform::datetime($record->timecreated),
                'timemodified' => transform::datetime($record->timemodified)
            ];
            return $carry;
        }, function($courseid, $data) {
            $context = \context_course::instance($courseid);
            $finaldata = (object) ['lti_types' => $data];
            writer::with_context($context)->export_data([], $finaldata);
        });
    }

    /**
     * Export personal data for the given approved_contextlist related to LTI tool proxies.
     *
     * @param approved_contextlist $contextlist a list of contexts approved for export.
     */
    protected static function export_user_data_lti_tool_proxies(approved_contextlist $contextlist) {
        global $DB;

        // Filter out any contexts that are not related to system context.
        $systemcontexts = array_filter($contextlist->get_contexts(), function($context) {
            return $context->contextlevel == CONTEXT_SYSTEM;
        });

        if (empty($systemcontexts)) {
            return;
        }

        $user = $contextlist->get_user();

        $systemcontext = \context_system::instance();

        $data = [];
        $ltiproxies = $DB->get_recordset('lti_tool_proxies', ['createdby' => $user->id], 'timecreated ASC');
        foreach ($ltiproxies as $ltiproxy) {
            $data[] = [
                'name' => format_string($ltiproxy->name, true, ['context' => $systemcontext]),
                'createdby' => transform::user($ltiproxy->createdby),
                'timecreated' => transform::datetime($ltiproxy->timecreated),
                'timemodified' => transform::datetime($ltiproxy->timemodified)
            ];
        }
        $ltiproxies->close();

        $finaldata = (object) ['lti_tool_proxies' => $data];
        writer::with_context($systemcontext)->export_data([], $finaldata);
    }

    public static function delete_data_for_all_users_in_context(\context $context) {
        // TODO: Implement delete_data_for_all_users_in_context() method.
    }

    public static function delete_data_for_user(approved_contextlist $contextlist) {
        // TODO: Implement delete_data_for_user() method.
    }

    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();

        if ($context->contextlevel == CONTEXT_SYSTEM) {
            // Fetch all LTI tool proxies.
            $sql = "SELECT ltit.createdby AS userid
                      FROM {lti_tool_proxies} ltp";
            $userlist->add_from_sql('userid', $sql, []);
        }

        if ($context->contextlevel == CONTEXT_COURSE) {
            // Fetch all LTI types.
            $sql = "SELECT ltit.createdby AS userid
                 FROM {context} c
                 JOIN {course} course
                   ON c.contextlevel = :contextlevel
                  AND c.instanceid = course.id
                 JOIN {lti_types} ltit
                   ON ltit.course = course.id
                WHERE c.id = :contextid";
            $params = [
                'contextlevel' => CONTEXT_COURSE,
                'contextid' => $context->id,
            ];
            $userlist->add_from_sql('userid', $sql, $params);
        }
    }

    public static function get_users_in_context_from_sql(
            userlist $userlist,
            string $alias,
            string $component,
            string $itemtype,
            string $insql,
            $params
    ): void {
        $sql = "SELECT {$alias}.userid
                FROM {lti_submission} {$alias}
                INNER JOIN {lti_instance} li ON li.ltiid = {$alias}.id
                WHERE li.component = :{$alias}component
                    AND li.itemtype = :{$alias}itemtype
                    AND li.itemid IN ({$insql})";

        $params["{$alias}component"] = $component;
        $params["{$alias}itemtype"] = $itemtype;

        $userlist->add_from_sql('userid', $sql, $params);
    }

    public static function delete_data_for_users(approved_userlist $userlist) {
        // TODO: Implement delete_data_for_users() method.
    }

    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
            'lti_submission',
            [
                'userid' => 'privacy:metadata:lti_submission:userid',
                'datesubmitted' => 'privacy:metadata:lti_submission:datesubmitted',
                'dateupdated' => 'privacy:metadata:lti_submission:dateupdated',
                'gradepercent' => 'privacy:metadata:lti_submission:gradepercent',
                'originalgrade' => 'privacy:metadata:lti_submission:originalgrade',
            ],
            'privacy:metadata:lti_submission'
        );

        $collection->add_database_table(
            'lti_tool_proxies',
            [
                'name' => 'privacy:metadata:lti_tool_proxies:name',
                'createdby' => 'privacy:metadata:createdby',
                'timecreated' => 'privacy:metadata:timecreated',
                'timemodified' => 'privacy:metadata:timemodified',
            ],
            'privacy:metadata:lti_tool_proxies'
        );
        $collection->add_database_table(
            'lti_types',
            [
                'name' => 'privacy:metadata:lti_types:name',
                'createdby' => 'privacy:metadata:createdby',
                'timecreated' => 'privacy:metadata:timecreated',
                'timemodified' => 'privacy:metadata:timemodified',
            ],
            'privacy:metadata:lti_types'
        );
        return $collection;
    }

    /**
     * Loop and export from a recordset.
     *
     * @param \moodle_recordset $recordset The recordset.
     * @param string $splitkey The record key to determine when to export.
     * @param mixed $initial The initial data to reduce from.
     * @param callable $reducer The function to return the dataset, receives current dataset, and the current record.
     * @param callable $export The function to export the dataset, receives the last value from $splitkey and the dataset.
     * @return void
     */
    public static function recordset_loop_and_export(\moodle_recordset $recordset, $splitkey, $initial,
                                                        callable $reducer, callable $export) {
        $data = $initial;
        $lastid = null;

        foreach ($recordset as $record) {
            if ($lastid && $record->{$splitkey} != $lastid) {
                $export($lastid, $data);
                $data = $initial;
            }
            $data = $reducer($data, $record);
            $lastid = $record->{$splitkey};
        }
        $recordset->close();

        if (!empty($lastid)) {
            $export($lastid, $data);
        }
    }

    public static function delete_instance_data($ltiid) {
        global $DB;

        $DB->delete_records('lti_submission', ['ltiid' => $ltiid]);
    }
}
