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
use core_ltix\external\is_cartridge;
use core_ltix\lti_testcase;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/ltix/tests/lti_testcase.php');

/**
 * Unit test for is_cartridge external function.
 *
 * @coversDefaultClass \core_ltix\external\is_cartridge
 * @package    core_ltix
 * @copyright  2024 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class is_cartridge_test extends lti_testcase {

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
     * Test is_cartridge.
     *
     * @covers ::execute
     * @return void
     */
    public function test_is_cartridge(): void {
        $this->setAdminUser();
        $result = is_cartridge::execute($this->getExternalTestFileUrl('/ims_cartridge_basic_lti_link.xml'));
        $result = external_api::clean_returnvalue(is_cartridge::execute_returns(), $result);
        $this->assertTrue($result['iscartridge']);

        $result = is_cartridge::execute($this->getExternalTestFileUrl('/test.html'));
        $result = external_api::clean_returnvalue(is_cartridge::execute_returns(), $result);
        $this->assertFalse($result['iscartridge']);
    }
}
