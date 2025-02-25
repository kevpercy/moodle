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
use core_ltix\local\placement\placement_status;

/**
 * Data generator class for core_ltix.
 *
 * @package    core_ltix
 * @category   test
 * @copyright  Copyright (c) 2012 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @author     Mark Nielsen
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_ltix_generator extends testing_module_generator {

    /**
     * Create a tool proxy.
     *
     * @param array $config
     */
    public function create_tool_proxies(array $config) {
        if (!isset($config['capabilityoffered'])) {
            $config['capabilityoffered'] = '';
        }
        if (!isset($config['serviceoffered'])) {
            $config['serviceoffered'] = '';
        }
        \core_ltix\helper::add_tool_proxy((object) $config);
    }

    /**
     * Split type creation data into 'type' and 'config' components, based on input array key prefixes.
     *
     * The $data array contains both the type data and config data that will be passed to lti_add_type(). This must be split into
     * two params (type, config) based on the array key prefixes ({@see lti_add_type()} for how the two params are handled):
     * - NO prefix: denotes 'type' data.
     * - 'lti_' prefix: denotes 'config' data.
     * - 'ltixservice_' prefix: denotes 'config' data, specifically config for service plugins.
     *
     * @param array $data array of type and config data containing prefixed keys.
     * @return array containing separated objects for type and config data. E.g. ['type' = stdClass, 'config' => stdClass]
     */
    protected function get_type_and_config_from_data(array $data): array {
        // Grab any non-prefixed fields; these are the type fields. The rest is considered config.
        $type = array_filter(
            $data,
            fn($val, $key) => !str_contains($key, 'lti_') && !str_contains($key, 'ltixservice_'),
            ARRAY_FILTER_USE_BOTH
        );
        $config = array_diff_key($data, $type);

        return ['type' => (object) $type, 'config' => (object) $config];
    }

    /**
     * Create a tool type.
     *
     * @param array $data
     * @return int ID of created tool
     */
    public function create_tool_types(array $data): int {

        if (!isset($data['baseurl'])) {
            throw new coding_exception('Must specify baseurl when creating a LTI tool type.');
        }
        $data['baseurl'] = (new moodle_url($data['baseurl']))->out(false); // Permits relative URLs in behat features.

        // Sensible defaults permitting the tool type to be used in a launch.
        $data['lti_acceptgrades'] = $data['lti_acceptgrades'] ?? \core_ltix\constants::LTI_SETTING_ALWAYS;
        $data['lti_sendname'] = $data['lti_sendname'] ?? \core_ltix\constants::LTI_SETTING_ALWAYS;
        $data['lti_sendemailaddr'] = $data['lti_sendname'] ?? \core_ltix\constants::LTI_SETTING_ALWAYS;
        $data['lti_launchcontainer'] = $data['lti_launchcontainer'] ?? \core_ltix\constants::LTI_LAUNCH_CONTAINER_EMBED_NO_BLOCKS;

        ['type' => $type, 'config' => $config] = $this->get_type_and_config_from_data($data);

        return \core_ltix\helper::add_type(type: $type, config: $config);
    }

    /**
     * Create a course tool type.
     *
     * @param array $type the type info.
     * @return int ID of created tool.
     * @throws coding_exception if any required fields are missing.
     */
    public function create_course_tool_types(array $type): int {
        global $SITE;

        if (!isset($type['baseurl'])) {
            throw new coding_exception('Must specify baseurl when creating a course tool type.');
        }
        if (!isset($type['course']) || $type['course'] == $SITE->id) {
            throw new coding_exception('Must specify a non-site course when creating a course tool type.');
        }

        $type['baseurl'] = (new moodle_url($type['baseurl']))->out(false); // Permits relative URLs in behat features.
        $type['coursevisible'] = $type['coursevisible'] ?? \core_ltix\constants::LTI_COURSEVISIBLE_ACTIVITYCHOOSER;
        $type['state'] = \core_ltix\constants::LTI_TOOL_STATE_CONFIGURED; // The default for course tools.

        // Sensible defaults permitting the tool type to be used in a launch.
        $type['lti_acceptgrades'] = $type['lti_acceptgrades'] ?? \core_ltix\constants::LTI_SETTING_ALWAYS;
        $type['lti_sendname'] = $type['lti_sendname'] ?? \core_ltix\constants::LTI_SETTING_ALWAYS;
        $type['lti_sendemailaddr'] = $type['lti_sendemailaddr'] ?? \core_ltix\constants::LTI_SETTING_ALWAYS;
        $type['lti_coursevisible'] = $type['coursevisible'] ?? \core_ltix\constants::LTI_COURSEVISIBLE_ACTIVITYCHOOSER;
        $type['lti_launchcontainer'] = $type['lti_launchcontainer'] ?? \core_ltix\constants::LTI_LAUNCH_CONTAINER_EMBED_NO_BLOCKS;

        // Required for cartridge processing support.
        $type['lti_toolurl'] = $type['baseurl'];
        $type['lti_description'] = $type['description'] ?? '';
        $type['lti_icon'] = $type['icon'] ?? '';
        $type['lti_secureicon'] = $type['secureicon'] ?? '';
        if (!empty($type['name'])) {
            $type['lti_typename'] = $type['name'];
        }

        ['type' => $type, 'config' => $config] = $this->get_type_and_config_from_data($type);

        \core_ltix\helper::load_type_if_cartridge($config);
        return \core_ltix\helper::add_type(type: $type, config: $config);
    }

    /**
     * Create a placement type for testing.
     *
     * @param string $component the component owning the placement type. e.g. core_ltix, mod_forum.
     * @param string $placementtype the placement type string.
     * @return stdClass the placementtype record.
     */
    public function create_placement_type(string $component, string $placementtype): stdClass {
        global $DB;
        $placementtype = (object) [
            'component' => $component,
            'type' => $placementtype,
        ];
        $placementtype->id = $DB->insert_record('lti_placement_type', $placementtype);
        return $placementtype;
    }

    /**
     * Create a tool placement of the given type for testing.
     *
     * @param int $toolid the tool id.
     * @param int $placementtypeid the id of the placement type.
     * @param array $placementconfig the array of placement config in the form of ['configname' => 'configvalue'].
     * @return stdClass the placement record.
     */
    public function create_placement(int $toolid, int $placementtypeid, array $placementconfig = []): stdClass {
        global $DB;

        $placement = (object) [
            'toolid' => $toolid,
            'placementtypeid' => $placementtypeid
        ];
        $placement->id = $DB->insert_record('lti_placement', $placement);

        if (empty($placementconfig)) {
            $placementconfig = ['default_usage' => 'disabled'];
        }

        foreach ($placementconfig as $name => $value) {
            $configrow = (object) [
                'placementid' => $placement->id,
                'name' => $name,
                'value' => $value,
            ];
            $DB->insert_record('lti_placement_config', $configrow);
        }

        return $placement;
    }

    /**
     * Generate a placement status override for the placement in the context.
     *
     * @param int $placementid the id of the tool placement.
     * @param placement_status $status the desired status.
     * @param int $contextid the context to set the status in.
     * @return stdClass the placement_status record created.
     */
    public function create_placement_status_in_context(int $placementid, placement_status $status, int $contextid): stdClass {
        global $DB;
        $placementstatusrow = (object) [
            'placementid' => $placementid,
            'contextid' => $contextid,
            'status' => $status->value,
        ];
        $placementstatusrow->id = $DB->insert_record('lti_placement_status', $placementstatusrow);

        return $placementstatusrow;
    }

    /**
     * Create the legacy lti_coursevisible record, signifying the value of "Show in activity chooser" at course level.
     *
     * @param int $toolid the tool to set the value for
     * @param int $courseid the course in which to set the value
     * @param int $coursevisible LTI_COURSEVISIBLE_ const from \core_ltix\constants, e.g. LTI_COURSEVISIBLE_PRECONFIGURED.
     * @return stdClass the inserted record.
     */
    public function create_legacy_lti_coursevisible(int $toolid, int $courseid, int $coursevisible): stdClass {
        global $DB;
        $rec = (object) ['typeid' => $toolid, 'courseid' => $courseid, 'coursevisible' => $coursevisible];
        $rec->id = $DB->insert_record('lti_coursevisible', $rec);

        return $rec;
    }
}
