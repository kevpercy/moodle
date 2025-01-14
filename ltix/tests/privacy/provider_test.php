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
 * Privacy provider tests.
 *
 * @package    core_ltix
 * @copyright  2018 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace core_ltix\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once("{$CFG->dirroot}/ltix/tests/generator/lib.php");

/**
 * Privacy provider tests class.
 *
 * @package    core_ltix
 * @copyright  2018 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class provider_test extends \core_privacy\tests\provider_testcase {

    /**
     * Test for provider::get_metadata().
     */
    public function test_get_metadata(): void {
        $collection = new collection('core_ltix');
        $newcollection = provider::get_metadata($collection);
        $itemcollection = $newcollection->get_collection();
        $this->assertCount(4, $itemcollection);

        $ltiproviderlink = array_shift($itemcollection);
        $this->assertEquals('lti_provider', $ltiproviderlink->get_name());

        $ltisubmissiontable = array_shift($itemcollection);
        $this->assertEquals('lti_submission', $ltisubmissiontable->get_name());

        $ltitoolproxies = array_shift($itemcollection);
        $this->assertEquals('lti_tool_proxies', $ltitoolproxies->get_name());

        $ltitypestable = array_shift($itemcollection);
        $this->assertEquals('lti_types', $ltitypestable->get_name());

        $privacyfields = $ltisubmissiontable->get_privacy_fields();
        $this->assertArrayHasKey('userid', $privacyfields);
        $this->assertArrayHasKey('datesubmitted', $privacyfields);
        $this->assertArrayHasKey('dateupdated', $privacyfields);
        $this->assertArrayHasKey('gradepercent', $privacyfields);
        $this->assertArrayHasKey('originalgrade', $privacyfields);
        $this->assertEquals('privacy:metadata:lti_submission', $ltisubmissiontable->get_summary());

        $privacyfields = $ltitoolproxies->get_privacy_fields();
        $this->assertArrayHasKey('name', $privacyfields);
        $this->assertArrayHasKey('createdby', $privacyfields);
        $this->assertArrayHasKey('timecreated', $privacyfields);
        $this->assertArrayHasKey('timemodified', $privacyfields);
        $this->assertEquals('privacy:metadata:lti_tool_proxies', $ltitoolproxies->get_summary());

        $privacyfields = $ltitypestable->get_privacy_fields();
        $this->assertArrayHasKey('name', $privacyfields);
        $this->assertArrayHasKey('createdby', $privacyfields);
        $this->assertArrayHasKey('timecreated', $privacyfields);
        $this->assertArrayHasKey('timemodified', $privacyfields);
        $this->assertEquals('privacy:metadata:lti_types', $ltitypestable->get_summary());
    }

    /**
     * Test for provider::get_contexts_for_userid().
     */
    public function test_get_contexts_for_userid(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();

        // Create a user which will make an LTI tool.
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $ltigenerator = $this->getDataGenerator()->get_plugin_generator('core_ltix');
        $ltigenerator->create_tool_types(['baseurl' => 'https://www.moodle.org', 'course' => $course->id]);
        $ltigenerator->create_tool_proxies([]);

        // Check the contexts supplied are correct.
        $contextlist = provider::get_contexts_for_userid($user->id);
        $this->assertCount(2, $contextlist);

        $contextformodule = $contextlist->current();
        $cmcontext = \context_course::instance($course->id);
        $this->assertEquals($cmcontext->id, $contextformodule->id);

        $contextlist->next();
        $contextforsystem = $contextlist->current();
        $this->assertEquals(SYSCONTEXTID, $contextforsystem->id);
    }

    /**
     * Test for provider::test_get_users_in_context()
     */
    public function test_get_users_in_context(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $component = 'core_ltix';
        $ltigenerator = $this->getDataGenerator()->get_plugin_generator('core_ltix');

        // Create user which will make a tool type and a tool proxy.
        $user1 = $this->getDataGenerator()->create_user();
        $this->setUser($user1);
        $ltigenerator->create_tool_types(['baseurl' => 'https://www.moodle1.org', 'course' => $course->id]);
        $ltigenerator->create_tool_proxies([]);

        // Create user which will make a tool type only.
        $user2 = $this->getDataGenerator()->create_user();
        $this->setUser($user2);
        $ltigenerator->create_tool_types(['baseurl' => 'https://www.moodle2.org', 'course' => $course->id]);

        $context = \context_course::instance($course->id);
        $userlist = new \core_privacy\local\request\userlist($context, $component);
        provider::get_users_in_context($userlist);

        $this->assertCount(2, $userlist);
        $expected = [$user1->id, $user2->id];
        $actual = $userlist->get_userids();
        sort($expected);
        sort($actual);

        $this->assertEquals($expected, $actual);

        $context = \context_system::instance();
        $userlist = new \core_privacy\local\request\userlist($context, $component);
        provider::get_users_in_context($userlist);
        $this->assertCount(1, $userlist);
        $actual = $userlist->get_userids();
        $this->assertEquals($user1->id, $actual[0]);
    }

    /**
     * Test for provider::export_user_data().
     */
    public function test_export_for_context_tool_types(): void {
        $this->resetAfterTest();

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();

        // Create a user which will make a tool type.
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        // Create a user that will not make a tool type.
        $this->getDataGenerator()->create_user();

        $ltigenerator = $this->getDataGenerator()->get_plugin_generator('core_ltix');
        $ltigenerator->create_tool_types(['baseurl' => 'https://www.moodle.org', 'course' => $course1->id]);
        $ltigenerator->create_tool_types(['baseurl' => 'https://www.moodle.org', 'course' => $course1->id]);
        $ltigenerator->create_tool_types(['baseurl' => 'https://www.moodle.org', 'course' => $course2->id]);

        // Export all of the data for the context.
        $coursecontext = \context_course::instance($course1->id);
        $this->export_context_data_for_user($user->id, $coursecontext, 'core_ltix');
        $writer = \core_privacy\local\request\writer::with_context($coursecontext);

        $this->assertTrue($writer->has_any_data());

        $data = $writer->get_data();
        $this->assertCount(2, $data->lti_types);

        $coursecontext = \context_course::instance($course2->id);
        $this->export_context_data_for_user($user->id, $coursecontext, 'core_ltix');
        $writer = \core_privacy\local\request\writer::with_context($coursecontext);

        $this->assertTrue($writer->has_any_data());

        $data = $writer->get_data();
        $this->assertCount(1, $data->lti_types);
    }

    /**
     * Test for provider::export_user_data().
     */
    public function test_export_for_context_tool_proxies(): void {
        $this->resetAfterTest();

        // Create a user that will not make a tool proxy.
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $toolproxy = new \stdClass();
        $toolproxy->createdby = $user;
        \core_ltix\helper::add_tool_proxy($toolproxy);

        // Export all of the data for the context.
        $systemcontext = \context_system::instance();
        $this->export_context_data_for_user($user->id, $systemcontext, 'core_ltix');
        $writer = \core_privacy\local\request\writer::with_context($systemcontext);

        $this->assertTrue($writer->has_any_data());

        $data = $writer->get_data();
        $this->assertCount(1, $data->lti_tool_proxies);
    }

    /**
     * Test for provider::delete_data_for_all_users_in_context().
     */
    public function test_delete_data_for_all_users_in_context(): void {
        global $DB;

        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $ltigenerator = $this->getDataGenerator()->get_plugin_generator('core_ltix');

        // Create users that will make LTI tools.
        $user1 = $this->getDataGenerator()->create_user();
        $this->setUser($user1);
        $ltigenerator->create_tool_types(['baseurl' => 'https://www.moodle.org', 'course' => $course->id]);
        $ltigenerator->create_tool_proxies([]);

        $user2 = $this->getDataGenerator()->create_user();
        $this->setUser($user2);
        $ltigenerator->create_tool_types(['baseurl' => 'https://www.moodle.org', 'course' => $course->id]);

        // Before deletion, we should have 2 responses in lti_types.
        $count = $DB->count_records('lti_types', ['course' => $course->id]);
        $this->assertEquals(2, $count);

        // Delete data based on context.
        $ccontext = \context_course::instance($course->id);
        provider::delete_data_for_all_users_in_context($ccontext);

        // After deletion, we should have 0 records in lti_types.
        $count = $DB->count_records('lti_types', ['course' => $course->id]);
        $this->assertEquals(0, $count);

        // Before deletion, we should have 1 record in lti_tool_proxies.
        $count = $DB->count_records('lti_tool_proxies');
        $this->assertEquals(1, $count);

        // Delete data based on context.
        $scontext = \context_system::instance();
        provider::delete_data_for_all_users_in_context($scontext);

        // After deletion, we should have 0 records in lti_types.
        $count = $DB->count_records('lti_tool_proxies');
        $this->assertEquals(0, $count);
    }

    /**
     * Test for provider::delete_data_for_user().
     */
    public function test_delete_data_for_user(): void {
        global $DB;

        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $ltigenerator = $this->getDataGenerator()->get_plugin_generator('core_ltix');

        // Create users that will make LTI tools.
        $user1 = $this->getDataGenerator()->create_user();
        $this->setUser($user1);
        $ltigenerator->create_tool_types(['baseurl' => 'https://www.moodle.org', 'course' => $course->id]);
        $ltigenerator->create_tool_proxies([]);

        $user2 = $this->getDataGenerator()->create_user();
        $this->setUser($user2);
        $ltigenerator->create_tool_types(['baseurl' => 'https://www.moodle.org', 'course' => $course->id]);

        // Before deletion we should have 2 responses.
        $count = $DB->count_records('lti_types', ['course' => $course->id]);
        $this->assertEquals(2, $count);

        // Before deletion, we should have 1 record in lti_tool_proxies.
        $count = $DB->count_records('lti_tool_proxies');
        $this->assertEquals(1, $count);

        $context = \context_course::instance($course->id);
        $contextlist = new approved_contextlist($user1, 'ltix',
            [\context_system::instance()->id, $context->id]);
        provider::delete_data_for_user($contextlist);

        // After deletion the lti type for the first user should have been updated.
        // The LTI type will not be deleted, in case it is used by someone else, but the createdby field will be reset.
        $count = $DB->count_records('lti_types', ['course' => $course->id]);
        $this->assertEquals(2, $count);
        $count = $DB->count_records('lti_types', ['course' => $course->id, 'createdby' => $user1->id]);
        $this->assertEquals(0, $count);

        // After deletion the lti type for the first user should have been updated.
        // The LTI type will not be deleted, in case it is used by someone else, but the createdby field will be reset.
        $count = $DB->count_records('lti_tool_proxies');
        $this->assertEquals(1, $count);
        $count = $DB->count_records('lti_tool_proxies', ['createdby' => $user1->id]);
        $this->assertEquals(0, $count);
    }

    /**
     * Test for provider::delete_data_for_users().
     */
    public function test_delete_data_for_users(): void {
        global $DB;
        $component = 'core_ltix';

        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $ltigenerator = $this->getDataGenerator()->get_plugin_generator('core_ltix');

        // Create users that will make submissions.
        $user1 = $this->getDataGenerator()->create_user();
        $this->setUser($user1);
        $ltigenerator->create_tool_types(['baseurl' => 'https://www.moodle.org', 'course' => $course->id]);
        $ltigenerator->create_tool_proxies([]);

        $user2 = $this->getDataGenerator()->create_user();
        $this->setUser($user2);
        $ltigenerator->create_tool_types(['baseurl' => 'https://www.moodle.org', 'course' => $course->id]);

        $user3 = $this->getDataGenerator()->create_user();
        $this->setUser($user3);
        $ltigenerator->create_tool_types(['baseurl' => 'https://www.moodle.org', 'course' => $course->id]);

        // Before deletion we should have 3 responses.
        $count = $DB->count_records('lti_types', ['course' => $course->id]);
        $this->assertEquals(3, $count);

        $context = \context_course::instance($course->id);
        $approveduserids = [$user1->id, $user2->id];
        $approvedlist = new approved_userlist($context, $component, $approveduserids);
        provider::delete_data_for_users($approvedlist);

        // After deletion the lti submission for the first two users should have been deleted.
        list($insql, $inparams) = $DB->get_in_or_equal($approveduserids, SQL_PARAMS_NAMED);
        $sql = "course = :courseid AND createdby {$insql}";
        $params = array_merge($inparams, ['courseid' => $course->id]);
        $count = $DB->count_records_select('lti_types', $sql, $params);
        $this->assertEquals(0, $count);

        // Check the submission for the third user is still there.
        $count = $DB->count_records('lti_types', ['course' => $course->id, 'createdby' => $user3->id]);
        $this->assertEquals(1, $count);
    }
}
