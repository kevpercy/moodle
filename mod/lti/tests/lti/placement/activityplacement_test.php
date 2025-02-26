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

namespace mod_lti;

use core_ltix\local\placement\deeplinking_placement_handler;
use core_ltix\local\placement\placements_manager;

/**
 * Mod LTI activityplacement handler testing.
 *
 * @covers \mod_lti\lti\placement\activityplacement
 * @package    mod_lti
 * @copyright  Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class activityplacement_test extends \advanced_testcase {

    /**
     * Test confirming that an instance of the handler can be fetched when needed.
     *
     * @return void
     */
    public function test_get_instance(): void {
        $this->assertInstanceOf(
            deeplinking_placement_handler::class,
            placements_manager::get_instance()->get_deeplinking_placement_instance('mod_lti:activityplacement')
        );
    }
}
