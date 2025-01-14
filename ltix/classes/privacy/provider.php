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
use core_privacy\local\request\helper;
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


    /**
     * Returns information about the user data stored in this component.
     *
     * @param collection $collection A list of information about this component
     * @return collection The collection object filled out with information about this component.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_external_location_link(
            'lti_provider',
            [
                'userid' => 'privacy:metadata:userid',
                'username' => 'privacy:metadata:username',
                'useridnumber' => 'privacy:metadata:useridnumber',
                'firstname' => 'privacy:metadata:firstname',
                'lastname' => 'privacy:metadata:lastname',
                'fullname' => 'privacy:metadata:fullname',
                'email' => 'privacy:metadata:email',
                'role' => 'privacy:metadata:role',
                'courseid' => 'privacy:metadata:courseid',
                'courseidnumber' => 'privacy:metadata:courseidnumber',
                'courseshortname' => 'privacy:metadata:courseshortname',
                'coursefullname' => 'privacy:metadata:coursefullname',
            ],
            'privacy:metadata:externalpurpose'
        );

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
     * Gets all of the users in a specified context.
     *
     * @param userlist $userlist List of users and context to check.
     * @return void
     */
    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();

        if ($context->contextlevel == CONTEXT_SYSTEM) {
            // Fetch all LTI tool proxies.
            $sql = "SELECT ltp.createdby AS userid
                      FROM {lti_tool_proxies} ltp";
            $userlist->add_from_sql('userid', $sql, []);
        }

        if ($context->contextlevel == CONTEXT_COURSE) {
            // Fetch all LTI types.
            $sql = "SELECT lt.createdby AS userid
                 FROM {context} c
                 JOIN {course} course
                   ON c.contextlevel = :contextlevel
                  AND c.instanceid = course.id
                 JOIN {lti_types} lt
                   ON lt.course = course.id
                WHERE c.id = :contextid";
            $params = [
                'contextlevel' => CONTEXT_COURSE,
                'contextid' => $context->id,
            ];
            $userlist->add_from_sql('userid', $sql, $params);
        }
    }

    /**
     * Gets a list of users in the LTI submission and instance tables
     * using the requested parameters.
     *
     * @param userlist $userlist List of users and context to check.
     * @param string $alias Alias of the submission table.
     * @param string $insql SQL list of item IDs.
     * @param array $params SQL parameters
     * @return void
     */
    public static function get_users_in_context_from_sql(
        userlist $userlist,
        string $alias,
        string $insql,
        array $params
    ): void {
        // TODO: Add handling for LTI instance table.

        $sql = "SELECT {$alias}.userid
                FROM {lti_submission} {$alias}
                WHERE {$alias}.ltiid IN ({$insql})";

        $userlist->add_from_sql('userid', $sql, $params);
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param   int $userid The user to search.
     * @return  contextlist $contextlist The contextlist containing the list of contexts used in this plugin.
     */
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
     * Get SQL to retrieve all LTI instances/submissions where the user has been involved.
     *
     * @param int $userid The user to search
     * @return array join/where/params SQL parts to include in queries
     */
    public static function get_join_sql(int $userid): array {
        // TODO: Add handling for LTI instance table.

        $join = "INNER JOIN {lti_submission} ltisub
                ON ltisub.ltiid = lti.id ";

        $where = "WHERE ltisub.userid = :userid";

        return [
            'join' => $join,
            'where' => $where,
            'params' => ['userid' => $userid],
        ];
    }

    /**
     * Extracts and exports all of the user data for the provided contexts.
     *
     * @param approved_contextlist $contextlist The list of contexts.
     * @return void
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        self::export_user_data_lti_types($contextlist);
        self::export_user_data_lti_tool_proxies($contextlist);
    }

    /**
     * Export personal data for the given approved_contextlist related to LTI types.
     *
     * @param approved_contextlist $contextlist a list of contexts approved for export.
     * @return void
     */
    protected static function export_user_data_lti_types(approved_contextlist $contextlist): void {
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
     * @return void
     */
    protected static function export_user_data_lti_tool_proxies(approved_contextlist $contextlist): void {
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

    /**
     * Export personal data for the given approved_contextlist related to LTI submissions.
     * TODO: This is tightly coupled to mod_lti. It needs to be rewritten and will need to include the instance table too.
     *
     * @param approved_contextlist $contextlist a list of contexts approved for export.
     * @return void
     */
    public static function export_user_data_lti_submissions(approved_contextlist $contextlist): void {
        global $DB;

        // Filter out any contexts that are not related to modules.
        $cmids = array_reduce($contextlist->get_contexts(), function($carry, $context) {
            if ($context->contextlevel == CONTEXT_MODULE) {
                $carry[] = $context->instanceid;
            }
            return $carry;
        }, []);

        if (empty($cmids)) {
            return;
        }

        $user = $contextlist->get_user();

        // Get all the LTI activities associated with the above course modules.
        $ltiidstocmids = self::get_lti_ids_to_cmids_from_cmids($cmids);
        $ltiids = array_keys($ltiidstocmids);

        list($insql, $inparams) = $DB->get_in_or_equal($ltiids, SQL_PARAMS_NAMED);
        $params = array_merge($inparams, ['userid' => $user->id]);
        $recordset = $DB->get_recordset_select('lti_submission', "ltiid $insql AND userid = :userid", $params, 'dateupdated, id');
        \core_ltix\privacy\provider::recordset_loop_and_export($recordset, 'ltiid', [], function($carry, $record) use ($user, $ltiidstocmids) {
            $carry[] = [
                'gradepercent' => $record->gradepercent,
                'originalgrade' => $record->originalgrade,
                'datesubmitted' => transform::datetime($record->datesubmitted),
                'dateupdated' => transform::datetime($record->dateupdated)
            ];
            return $carry;
        }, function($ltiid, $data) use ($user, $ltiidstocmids) {
            $context = \context_module::instance($ltiidstocmids[$ltiid]);
            $contextdata = helper::get_context_data($context, $user);
            $finaldata = (object) array_merge((array) $contextdata, ['submissions' => $data]);
            helper::export_context_files($context, $user);
            writer::with_context($context)->export_data([], $finaldata);
        });
    }

    /**
     * Deletes LTI data for all users in a given context.
     *
     * @param \context $context The context to delete all data for.
     * @return void
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;

        if ($context->contextlevel == CONTEXT_SYSTEM) {
            $DB->delete_records('lti_tool_proxies');
        } else if ($context->contextlevel == CONTEXT_COURSE) {
            $DB->delete_records('lti_types');
        }
    }

    /**
     * Deletes LTI data for a given user in all provided contexts.
     * This function just updates the User ID to 0 instead of deleting the LTI instances
     * because the instances may be used by other people.
     *
     * @param approved_contextlist $contextlist List of contexts to delete the user from.
     * @return void
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;
        if (!$contextlist->count()) {
            return;
        }

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel == CONTEXT_SYSTEM) {
                $table = 'lti_tool_proxies';
            } else if ($context->contextlevel == CONTEXT_COURSE) {
                $table = 'lti_types';
            } else {
                continue;
            }

            $DB->set_field_select($table, 'createdby', 0, 'createdby = ?', [$contextlist->get_user()->id]);
        }
    }

    /**
     * Deletes LTI data for a given list of users and their contexts.
     * This function just updates the User ID to 0 instead of the deleting the LTI instances
     * because the instances may be used by other people.
     *
     * @param approved_userlist $userlist The list of contexts and users to delete the user from.
     * @return void
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        global $DB;

        $context = $userlist->get_context();

        if ($context->contextlevel == CONTEXT_SYSTEM) {
            $table = 'lti_tool_proxies';
        } else if ($context->contextlevel == CONTEXT_COURSE) {
            $table = 'lti_types';
        } else {
            return;
        }

        list($usersql, $userparams) = $DB->get_in_or_equal($userlist->get_userids(), SQL_PARAMS_NAMED);
        $DB->set_field_select($table, 'createdby', 0, "createdby {$usersql}", $userparams);
    }

    /**
     * Deletes the data from the LTI submission and instance tables.
     *
     * @param int $ltiid ID of the LTI submission.
     * @param int|array|null $userids User ID or array of IDs to delete the data for.
     * @return void
     */
    public static function delete_instance_data(int $ltiid, int|array $userids = null): void {
        // TODO: Add handling for LTI instance table.

        global $DB;

        $params = ['ltiid' => $ltiid];
        $sql = "ltiid = :ltiid";

        if (!is_null($userids)) {
            $userids = (array) $userids;
            list($insql, $inparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
            $sql .= " AND userid {$insql}";
            $params = array_merge($params, $inparams);
        }

        $DB->delete_records_select('lti_submission', $sql, $params);
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
    public static function recordset_loop_and_export(
            \moodle_recordset $recordset,
            string $splitkey,
            $initial,
            callable $reducer,
            callable $export
    ) {
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

    /**
     * Return a dict of LTI IDs mapped to their course module ID.
     * TODO: This is used by export_user_data_lti_submissions() and shouldn't be here.
     * TODO: Deal with this when export_user_data_lti_submissions() is fixed.
     *
     * @param array $cmids The course module IDs.
     * @return array In the form of [$ltiid => $cmid].
     */
    protected static function get_lti_ids_to_cmids_from_cmids(array $cmids): array {
        global $DB;

        list($insql, $inparams) = $DB->get_in_or_equal($cmids, SQL_PARAMS_NAMED);
        $sql = "SELECT lti.id, cm.id AS cmid
                 FROM {lti} lti
                 JOIN {modules} m
                   ON m.name = :lti
                 JOIN {course_modules} cm
                   ON cm.instance = lti.id
                  AND cm.module = m.id
                WHERE cm.id $insql";
        $params = array_merge($inparams, ['lti' => 'lti']);

        return $DB->get_records_sql_menu($sql, $params);
    }
}
