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

namespace core_ltix\external;

use core_external\external_api;
use core_ltix\lti_testcase;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/ltix/tests/lti_testcase.php');

/**
 * Unit test for get_tool_proxies external function.
 *
 * @coversDefaultClass \core_ltix\external\get_tool_proxies
 * @package    core_ltix
 * @copyright  2024 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_tool_proxies_test extends lti_testcase {

    /**
     * Setup method.
     *
     * @return void
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Test get_tool_proxies.
     *
     * @covers ::execute
     * @return void
     */
    public function test_get_tool_proxies(): void {
        // Create two tool proxies. One to associate with tool, and one to leave orphaned.
        $this->setAdminUser();
        $proxy = $this->generate_tool_proxy("1");
        $orphanedproxy = $this->generate_tool_proxy("2");
        $this->generate_tool_type("1", $proxy->id); // Associate proxy 1 with tool type.

        // Fetch all proxies.
        $proxies = get_tool_proxies::execute(false);
        $proxies = external_api::clean_returnvalue(get_tool_proxies::execute_returns(), $proxies);

        $this->assertCount(2, $proxies);
        $this->assertEqualsCanonicalizing([(array) $proxy, (array) $orphanedproxy], $proxies);
    }

    /**
     * Test get_tool_proxies with orphaned proxies only.
     *
     * @covers ::execute
     * @return void
     */
    public function test_get_orphaned_tool_proxies(): void {
        // Create two tool proxies. One to associate with tool, and one to leave orphaned.
        $this->setAdminUser();
        $proxy = $this->generate_tool_proxy("1");
        $orphanedproxy = $this->generate_tool_proxy("2");
        $this->generate_tool_type("1", $proxy->id); // Associate proxy 1 with tool type.

        // Fetch all proxies.
        $proxies = get_tool_proxies::execute(true);
        $proxies = external_api::clean_returnvalue(get_tool_proxies::execute_returns(), $proxies);

        $this->assertCount(1, $proxies);
        $this->assertEqualsCanonicalizing([(array) $orphanedproxy], $proxies);
    }
}
