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
 * Unit test for get_tool_proxy_registration_request external function.
 *
 * @coversDefaultClass \core_ltix\external\get_tool_proxy_registration_request
 * @package    core_ltix
 * @copyright  2024 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_tool_proxy_registration_request_test extends lti_testcase {

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
     * Test get_tool_proxy_registration_request.
     *
     * @covers ::execute
     * @return void
     */
    public function test_get_tool_proxy_registration_request(): void {
        $this->setAdminUser();
        $proxy = create_tool_proxy::execute('Test proxy', $this->getExternalTestFileUrl('/test.html'), array(), array());
        $proxy = (object) external_api::clean_returnvalue(create_tool_proxy::execute_returns(), $proxy);

        $request = get_tool_proxy_registration_request::execute($proxy->id);
        $request = external_api::clean_returnvalue(get_tool_proxy_registration_request::execute_returns(),
            $request);

        $this->assertEquals('ToolProxyRegistrationRequest', $request['lti_message_type']);
        $this->assertEquals('LTI-2p0', $request['lti_version']);
    }
}
