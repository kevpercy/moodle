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

namespace core_ltix\local\placement;

/**
 * LTI Placements manager.
 *
 * @package    core_ltix
 * @copyright  2025 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class placements_manager {

    /** @var placements_manager $instance the singleton instance. */
    private static placements_manager $instance;

    /** @var array $placementtypehandlers list of all registered handlers, indexed by placementtype string. */
    private array $placementtypehandlers = [];

    /**
     * Factory method returning the singleton.
     *
     * @return placements_manager the instance.
     */
    public static function get_instance(): self {
        if (!isset(self::$instance)) {
            self::$instance = new self();
            self::$instance->init_placement_type_handlers();
        }
        return self::$instance;
    }

    /**
     * Updates the lti_placement_type table with the component placement type definitions.
     * If no parameters are given, the function updates the core moodle placement types only.
     *
     * Note that the absence of the db/lti.php, placement type definition file
     * will cause any stored placement types for the component to be removed from
     * the database.
     *
     * This function IS safe to call during upgrade, such as if a component needs to load placementtypes before using them in an
     * upgrade step.
     *
     * @param string $component the frankenstyle component name.
     * @return void
     */
    public static function update_placement_types(string $component = 'moodle'): void {
        global $DB;

        $fileplacementtypes = self::load_placement_types($component);
        $fileplacementtypesnames = array_keys($fileplacementtypes);

        $registeredplacementtypes = $DB->get_records_menu('lti_placement_type', ['component' => $component]);

        $newplacementtypes = array_diff($fileplacementtypesnames, $registeredplacementtypes);
        foreach ($newplacementtypes as $newplacementtype) {
            $DB->insert_record('lti_placement_type', ['component' => $component, 'type' => $newplacementtype]);
        }

        $deletedplacementtypes = array_diff($registeredplacementtypes, $fileplacementtypesnames);
        self::placement_types_cleanup($component, $deletedplacementtypes);

        \cache::make('core', 'ltix_placementtype_handlers')->delete('handlers');
    }

    /**
     * Get an instance of a component's deeplinking placement handler.
     *
     * @param string $placementtype the placement type string. e.g. 'mod_lti:activityplacement'.
     * @return deeplinking_placement_handler
     */
    public function get_deeplinking_placement_instance(string $placementtype): deeplinking_placement_handler {
        $componentclassname = self::handler_from_placement_type($placementtype);

        if (is_null($componentclassname)) {
            throw new \coding_exception("No handler found for placement type '$placementtype'");
        }
        $componentclassname = "\\".$componentclassname;

        $validhandlertype = is_subclass_of($componentclassname, deeplinking_placement_handler::class);
        if (!$validhandlertype) {
            throw new \coding_exception("Handler must implement ".deeplinking_placement_handler::class);
        }

        return $componentclassname::instance();
    }

    /**
     * Loads the lti placement types from disk for the given component.
     *
     * @param string $component the frankenstyle component name.
     * @return array the array of placement types.
     */
    private static function load_placement_types(string $component): array {
        $filepath = \core\component::get_component_directory($component).'/db/lti.php';

        $placementtypes = [];
        if (file_exists($filepath)) {
            require($filepath);
        }

        return array_filter($placementtypes, function($placementtypeinfo, $placementtype) {
            $validformat = self::is_valid_placement_type_string($placementtype);
            if (!$validformat) {
                debugging("Invalid placement type name '$placementtype'. Should be of the format: ".
                    "'frankenstyle_component:placementname'. Loading of this placement type has been skipped.");
            }
            return $validformat;
        }, ARRAY_FILTER_USE_BOTH);
    }

    /**
     * Handle removal of a placement type from a component's lti.php.
     *
     * @param string $component the frankenstyle component name.
     * @param array $deletedplacementtypes array of deleted placement types, indexed by record id.
     * @return void
     */
    private static function placement_types_cleanup(string $component, array $deletedplacementtypes): void {
        global $DB;
        if (empty($deletedplacementtypes)) {
            return;
        }

        // Note: while lti_resource_link records have an association with placements, they are usable without the placement that
        // created them. As such, they will not be removed here as part of a placement type removal.

        list($insql, $inparams) = $DB->get_in_or_equal(array_keys($deletedplacementtypes), SQL_PARAMS_NAMED);
        $DB->delete_records_select('lti_placement', "placementtypeid $insql", $inparams);

        foreach ($deletedplacementtypes as $deletedplacementtype) {
            $DB->delete_records('lti_placement_type', ['component' => $component, 'type' => $deletedplacementtype]);
        }
    }

    /**
     * Validate a placement type string follows the COMPONENTNAME:PLACEMENTNAME syntax.
     *
     * @param string $placementtype the placement type string.
     * @return bool true if valid, false otherwise.
     */
    private static function is_valid_placement_type_string(string $placementtype): bool {
        return preg_match('/^[a-z]+_[a-z_0-9]+:[a-z_0-9]+$/', $placementtype);
    }

    /**
     * Init placement type handlers instance var from cache, or from disk if the cache isn't built yet.
     *
     * @return void
     */
    private function init_placement_type_handlers(): void {
        if (!$this->load_placement_type_handlers_from_cache()) {
            $this->load_placement_type_handlers_from_disk();
        }
    }

    /**
     * Load placement type handlers from cache, updating instance vars in the process.
     *
     * @return bool true if successfully loaded from cache, false otherwise.
     */
    private function load_placement_type_handlers_from_cache(): bool {
        if (!PHPUNIT_TEST && !CACHE_DISABLE_ALL) {
            $cache = \cache::make('core', 'ltix_placementtype_handlers');
            $handlers = $cache->get('handlers');

            if (is_array($handlers)) {
                $this->placementtypehandlers = $handlers;
                return true;
            }
        }
        return false;
    }

    /**
     * Load placement type handlers from disk, updating instance vars and caching in the process.
     *
     * @return void
     */
    private function load_placement_type_handlers_from_disk(): void {
        $componentnames = ['core'];
        foreach (\core\component::get_plugin_types() as $plugintype => $plugintypedir) {
            foreach (\core\component::get_plugin_list($plugintype) as $pluginname => $plugindir) {
                if ($plugindir) {
                    $componentnames[] = "{$plugintype}_{$pluginname}";
                }
            }
        }

        foreach ($componentnames as $component) {
            $componentplacements = self::load_placement_types($component);

            if (!is_array($componentplacements) || !$componentplacements) {
                continue;
            }

            foreach ($componentplacements as $placementtype => $placementtypeinfo) {
                // Note: a component may not specify a handler for a given placement type. It depends on usage of the type.
                if (!array_key_exists('handler', $placementtypeinfo)) {
                    continue;
                }

                $this->placementtypehandlers[$placementtype] = [
                    'handler' => ltrim($placementtypeinfo['handler'], '\\'), // Normalise leading slashes.
                    'component' => $component,
                ];
            }
        }
        $cache = \cache::make('core', 'ltix_placementtype_handlers');
        $cache->set('handlers', $this->placementtypehandlers);
    }

    /**
     * Helper to get a fully qualified class from a placement type string.
     *
     * @param string $placementtype the placement type, in the format COMPONENT:PLACEMENTNAME. e.g. 'mod_lti:activityplacement'.
     * @return null|string the fully qualified classname of the component's placement class for the given placement type, else null.
     * @throws \coding_exception if an invalid placement type is given.
     */
    private function handler_from_placement_type(string $placementtype): ?string {
        if (!self::is_valid_placement_type_string($placementtype)) {
            throw new \coding_exception("Invalid placement type. Should be of the form 'component:placementtypename'.");
        }

        // Note: not all placement type definitions require handlers.
        // Null is a valid return, used when the type is valid, but no handler was found.
        return $this->placementtypehandlers[$placementtype]['handler'] ?? null;
    }
}
