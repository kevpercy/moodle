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

use core\tests\fake_plugins_test_trait;

/**
 * Placements helper tests.
 *
 * @covers \core_ltix\local\placement\placements_manager
 * @package    core_ltix
 * @copyright  2025 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class placements_manager_test extends \advanced_testcase {

    use fake_plugins_test_trait;

    /**
     * Test registration of component placement types during install/upgrade, via the update_placement_types() method.
     *
     * @return void
     * @runInSeparateProcess
     */
    public function test_update_placement_types(): void {
        $this->resetAfterTest();
        global $CFG, $DB;

        // Using a fake plugin mock, verify the lti placement types are registered.
        // See: lib/tests/fixtures/fakeplugins/fake/fullfeatured/db/lti.php, where placementtypes + handlers are defined.
        $this->add_mocked_plugintype('fake', "{$CFG->dirroot}/lib/tests/fixtures/fakeplugins/fake", true);
        $this->add_mocked_plugin('fake', 'fullfeatured', "{$CFG->dirroot}/lib/tests/fixtures/fakeplugins/fake/fullfeatured");

        placements_manager::update_placement_types('fake_fullfeatured');
        $this->assertdebuggingcalledcount(1); // Expected, since one placement string is formatted incorrectly and is skipped.
        $this->assertEquals(3, $DB->count_records('lti_placement_type', ['component' => 'fake_fullfeatured']));

        // Next, mock the situation where a placement type has already been registered, and is now removed from lti.php config.
        $placementtypeid = $DB->insert_record('lti_placement_type', [
            'component' => 'fake_fullfeatured',
            'type' => 'fake_fullfeatured:thisonetobedeleted'
        ]);
        $DB->insert_record('lti_placement', ['placementtypeid' => $placementtypeid, 'toolid' => 101]);
        $this->assertEquals(1, $DB->count_records('lti_placement_type',
            ['component' => 'fake_fullfeatured', 'type' => 'fake_fullfeatured:thisonetobedeleted']));
        $this->assertEquals(1, $DB->count_records('lti_placement', ['toolid' => 101]));

        // The placement type and any placements using it should have been deleted.
        placements_manager::update_placement_types('fake_fullfeatured');
        $this->assertdebuggingcalledcount(1); // Expected, since one placement string is formatted incorrectly.
        $placementtypes = $DB->get_records_menu('lti_placement_type', ['component' => 'fake_fullfeatured']);
        $this->assertCount(3, $placementtypes);
        $this->assertContains('fake_fullfeatured:myfirstplacementtype', $placementtypes);
        $this->assertContains('fake_fullfeatured:anotherplacementtype', $placementtypes);
        $this->assertContains('fake_fullfeatured:thirdplacementtype', $placementtypes);
        $this->assertEquals(0, $DB->count_records('lti_placement_type',
            ['component' => 'fake_fullfeatured', 'type' => 'fake_fullfeatured:thisonetobedeleted']));
        $this->assertEquals(0, $DB->count_records('lti_placement', ['toolid' => 101]));
    }

    /**
     * Test fetching a deep linking placements component implementation.
     *
     * @return void
     * @runInSeparateProcess
     */
    public function test_get_deeplinking_placement_handler(): void {
        $this->resetAfterTest();
        global $CFG;

        // Deep mock a fake plugin with lti placement stubs.
        $this->add_full_mocked_plugintype('fake', 'lib/tests/fixtures/fakeplugins/fake');
        $this->add_mocked_plugin('fake', 'fullfeatured', "{$CFG->dirroot}/lib/tests/fixtures/fakeplugins/fake/fullfeatured");
        placements_manager::update_placement_types('fake_fullfeatured');
        $this->assertdebuggingcalledcount(1);

        // Expect an instance of deeplinking_placement_handler.
        $placementinstance = placements_manager::get_instance()
            ->get_deeplinking_placement_instance('fake_fullfeatured:myfirstplacementtype');
        $this->assertdebuggingcalledcount(1);
        $this->assertInstanceOf(\core_ltix\local\placement\deeplinking_placement_handler::class, $placementinstance);
        $this->assertEquals('fake_fullfeatured\lti\placement\myfirstplacementtype', get_class($placementinstance));

        // Verify an exception is thrown when an invalid placement type string is used.
        try {
            placements_manager::get_instance()->get_deeplinking_placement_instance('fake_fullfeatured:THIS:ISINVALID');
            $this->fail('Exception expected for invalid placement type string.');
        } catch (\coding_exception $e) {
            $this->assertMatchesRegularExpression('/.*Invalid placement type.*/', $e->getMessage());
        }

        // Verify an exception is thrown when an implementation of deeplinking_placement cannot be found.
        try {
            placements_manager::get_instance()->get_deeplinking_placement_instance('fake_fullfeatured:not_a_class_name');
            $this->fail('Exception expected when placement is not implemented.');
        } catch (\Exception $e) {
            $this->assertEquals('codingerror', $e->errorcode);
        }

        // Verify a type error is seen, if the implementor returns the wrong type for the implementation.
        try {
            placements_manager::get_instance()->get_deeplinking_placement_instance('fake_fullfeatured:thirdplacementtype');
            $this->fail('Exception expected when placement is the wrong type.');
        } catch (\Exception $e) {
            $this->assertEquals('codingerror', $e->errorcode);
        }
    }
}
