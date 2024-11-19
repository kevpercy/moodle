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
 * Unit test for create_tool_proxy external function.
 *
 * @coversDefaultClass \core_ltix\external\create_tool_proxy
 * @package    core_ltix
 * @copyright  2024 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class create_tool_proxy_test extends lti_testcase {

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
     * Test create_tool_proxy.
     *
     * @covers ::execute
     * @return void
     */
    public function test_create_tool_proxy(): void {
        $this->setAdminUser();
        $capabilities = ['AA', 'BB'];
        $proxy = create_tool_proxy::execute('Test proxy', $this->getExternalTestFileUrl('/test.html'), $capabilities, []);
        $proxy = (object) external_api::clean_returnvalue(create_tool_proxy::execute_returns(), $proxy);

        $this->assertEquals('Test proxy', $proxy->name);
        $this->assertEquals($this->getExternalTestFileUrl('/test.html'), $proxy->regurl);
        $this->assertEquals(LTI_TOOL_PROXY_STATE_PENDING, $proxy->state);
        $this->assertEquals(implode("\n", $capabilities), $proxy->capabilityoffered);
    }

    /**
     * Test create_tool_proxy with a duplicate url.
     *
     * @covers ::execute
     * @return void
     */
    public function test_create_tool_proxy_duplicateurl(): void {
        $this->setAdminUser();
        create_tool_proxy::execute('Test proxy 1', $this->getExternalTestFileUrl('/test.html'), array(), array());

        $this->expectException(\moodle_exception::class);
        create_tool_proxy::execute('Test proxy 2', $this->getExternalTestFileUrl('/test.html'), array(), array());
    }

    /**
     * Test create_tool_proxy for a user without the required capability.
     *
     * @covers ::execute
     * @return void
     */
    public function test_create_tool_proxy_without_capability(): void {
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $this->setUser($teacher);
        $this->expectException(\required_capability_exception::class);
        create_tool_proxy::execute('Test proxy', $this->getExternalTestFileUrl('/test.html'), array(), array());
    }
}
