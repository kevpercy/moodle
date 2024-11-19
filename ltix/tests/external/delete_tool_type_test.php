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

namespace external;

use core_external\external_api;
use core_ltix\external\create_tool_type;
use core_ltix\external\delete_tool_type;
use core_ltix\helper;
use core_ltix\lti_testcase;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/ltix/tests/lti_testcase.php');

/**
 * Unit test for delete_tool_type external function.
 *
 * @coversDefaultClass \core_ltix\external\delete_tool_type
 * @package    core_ltix
 * @copyright  2024 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class delete_tool_type_test extends lti_testcase {

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
     * Test delete_tool_type for a user with the required capability.
     *
     * @covers ::execute
     * @return void
     */
    public function test_delete_tool_type(): void {
        $this->setAdminUser();
        $type = create_tool_type::execute($this->getExternalTestFileUrl('/ims_cartridge_basic_lti_link.xml'), '', '');
        $type = external_api::clean_returnvalue(create_tool_type::execute_returns(), $type);
        $this->assertNotEmpty(helper::get_type($type['id']));

        $type = delete_tool_type::execute($type['id']);
        $type = external_api::clean_returnvalue(delete_tool_type::execute_returns(), $type);
        $this->assertEmpty(helper::get_type($type['id']));
    }

    /**
     * Test delete_tool_type for a user without the required capability.
     *
     * @covers ::execute
     * @return void
     */
    public function test_delete_tool_type_without_capability(): void {
        $this->setAdminUser();
        $type = create_tool_type::execute($this->getExternalTestFileUrl('/ims_cartridge_basic_lti_link.xml'), '', '');
        $type = external_api::clean_returnvalue(create_tool_type::execute_returns(), $type);
        $this->assertNotEmpty(helper::get_type($type['id']));

        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $this->setUser($teacher);
        $this->expectException(\required_capability_exception::class);
        delete_tool_type::execute($type['id']);
    }
}
