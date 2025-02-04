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
 * Upgrade-specific helper handling data migration from mod_lti into core_ltix.
 *
 * @package    mod_lti
 * @copyright  2025 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class lti_migration_upgrade_helper {
    /**
     * Create a placement of type 'mod_lti:activityplacement' for existing tools, where applicable.
     *
     * This ensures that existing tools can continue to be used as mod_lti activity instances, post-upgrade.
     *
     * @return void
     */
    public function create_default_placements(): void {
        global $DB, $CFG;
        require_once($CFG->libdir . '/accesslib.php'); // Required for CONTEXT_XX consts usage.

        // As part of the migration of LTI to core, placements must be created for every tool which is visible to courses.
        // The placement type is 'mod_lti:activityplacement'. See mod/lti/db/lti.php, where this placement type is defined.
        // This process involves:
        // 1. Creating the placement
        // 2. Creating any placement config
        // 3. Creating any lti_placement_status records.
        //
        // The existing 'tool configuration usage' (coursevisible) affects placement/placement config in the following way:
        // 1. If the coursevisible is 'Do not show', then no placement is created nor is placement config set.
        // 2. If the coursevisible is 'Show as a preconfigured tool', then the placement is created, and 'default=disabled' should
        // be set in placement config.
        // 3. If the coursevisible is 'Show in activity chooser and as a preconfigured tool', then the placement is created, and
        // 'default=enabled' should be set in placement config.
        // Note that since course tools do not support the 'Do not show' status, all course tools, by default, receive a placement.
        //
        // The existing 'Supports deep linking' + 'Content Selection URL' affect placement config in the following way:
        // 1. If 'supports DL' is true, then 'supports_deep_linking=true' should be set in placement config.
        // 2. If 'supports DL' is true, then 'target_link_uri' should conditionally be set in placement config:
        // - set if 'toolurl_ContentItemSelectionRequest' in the tool config is not null.
        // - not set otherwise (and the tool URL will be used).
        //
        // The existing 'Show in activity chooser' feature of course tools (the lti_coursevisible records signifying a course-level
        // override to the value of the tool-level 'coursevisible') is replaced by lti_placement_status which behaves the same way.
        // This is mapped in the following way:
        // 1. If a tool has a placement and has an lti_coursevisible record, then create a corresponding lti_placement_status
        // record.
        // 2. If a tool has no placement, or has no lti_coursevisible record, then skip creation of the lti_placement_status.
        // In this case, the tool's placement config will determine the coursevisible value in courses.

        $placementtypeid = $DB->get_field('lti_placement_type', 'id', ['type' => 'mod_lti:activityplacement'], MUST_EXIST);

        $placementsql = <<<EOF
            INSERT INTO {lti_placement} (
                toolid,
                placementtypeid
            ) SELECT
                tool.id AS toolid,
                :placementtypeid AS placementtypeid
               FROM {lti_types} tool
              WHERE tool.coursevisible != :coursevisiblehidden
        EOF;
        $DB->execute($placementsql, ['placementtypeid' => $placementtypeid, 'coursevisiblehidden' => 0]);

        // Conditionally set 'default_usage config' config value.
        $defaultusageconfigsql = <<<EOF
            INSERT INTO {lti_placement_config} (
                placementid,
                name,
                value
            ) SELECT
                p.id AS placementid,
                :name AS name,
               (CASE WHEN tool.coursevisible = :coursevisibleactchooser THEN 'enabled' ELSE 'disabled' END) AS value
                FROM {lti_placement} p
                JOIN {lti_types} tool ON (tool.id = p.toolid)
               WHERE p.placementtypeid = :placementtypeid
        EOF;
        $DB->execute($defaultusageconfigsql, [
            'name' => 'default_usage',
            'coursevisibleactchooser' => 2,
            'placementtypeid' => $placementtypeid
        ]);

        // Set 'supports_deep_linking' config value.
        $supportsdeeplinkingconfigsql = <<<EOF
            INSERT INTO {lti_placement_config} (
                placementid,
                name,
                value
            ) SELECT
                p.id AS placementid,
                :name AS name,
                tc.value AS value
              FROM {lti_types} tool
              JOIN {lti_types_config} tc ON (tc.typeid = tool.id AND tc.name = :contentitem)
              JOIN {lti_placement} p ON (tool.id = p.toolid)
              WHERE p.placementtypeid = :placementtypeid
        EOF;
        $DB->execute($supportsdeeplinkingconfigsql, [
            'name' => 'supports_deep_linking',
            'contentitem' => 'contentitem',
            'placementtypeid' => $placementtypeid,
        ]);

        // Set 'deep_linking_url' config value.
        $isnotempty = $DB->sql_isnotempty('lti_types_config', 'tc.value', false, true);
        $deeplinkingurlconfigsql = <<<EOF
            INSERT INTO {lti_placement_config} (
                placementid,
                name,
                value
            ) SELECT
                p.id AS placementid,
                :name AS name,
                tc.value as value
              FROM {lti_types} tool
              JOIN {lti_types_config} tc ON (tc.typeid = tool.id AND tc.name = :contentitemurl)
              JOIN {lti_placement} p ON (tool.id = p.toolid)
              WHERE p.placementtypeid = :placementtypeid
                AND $isnotempty
        EOF;
        $DB->execute($deeplinkingurlconfigsql, [
            'name' => 'deep_linking_url',
            'contentitemurl' => 'toolurl_ContentItemSelectionRequest',
            'placementtypeid' => $placementtypeid,
        ]);

        // Set the context-specific status of the placement. Maps lti_coursevisible to lti_placement_status.
        // Note: this query captures those tools which:
        // a) have a configured 'mod_lti:activityplacement' placement (per above logic) and;
        // b) have an lti_coursevisible record.
        $placementstatussql = <<<EOF
            INSERT INTO {lti_placement_status} (
                placementid,
                contextid,
                status
            ) SELECT
                p.id AS placementid,
                ctx.id AS contextid,
                (CASE WHEN lcv.coursevisible = :coursevisibleactchooser THEN 2 ELSE 1 END) AS status
              FROM {lti_types} tool
              JOIN {lti_coursevisible} lcv ON (tool.id = lcv.typeid)
              JOIN {context} ctx ON (ctx.contextlevel = :coursecontextlevel AND ctx.instanceid = lcv.courseid)
              JOIN {lti_placement} p ON (p.toolid = tool.id)
        EOF;
        $DB->execute($placementstatussql, [
            'coursevisibleactchooser' => 2,
            'coursecontextlevel' => CONTEXT_COURSE,
        ]);
    }
}
