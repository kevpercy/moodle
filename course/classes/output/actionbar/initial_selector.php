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

namespace core_course\output\actionbar;

use core\output\comboboxsearch;
use renderable;
use renderer_base;
use stdClass;
use templatable;

/**
 * Renderable class for the group selector element in the action bar.
 *
 * @package    core_course
 * @copyright  2024 Shamim Rezaie <shamim@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class initial_selector implements renderable, templatable {

    /**
     * @var stdClass The course object.
     */
    protected $course;

    /**
     * @var string The URL of the page to filter.
     */
    protected $slug;

    /**
     * @var array Additional parameters to add to the URL.
     */
    protected $additionalparams;

    /**
     * @var string The selected initial for the first name.
     */
    protected $firstinitial;

    /**
     * @var string The parameter name for the first name initial.
     */
    protected $firstinitialparam;

    /**
     * @var string The selected initial for the last name.
     */
    protected $lastinitial;

    /**
     * @var string The parameter name for the last name initial.
     */
    protected $lastinitialparam;

    /**
     * The class constructor.
     *
     * @param stdClass $course The course object.
     * @param string $slug The base URL to send the form to.
     * @param string $firstinitial The selected first initial.
     * @param string $lastinitial The selected last initial.
     * @param string $firstinitialparam The parameter name for the first initial.
     * @param string $lastinitialparam The parameter name for the last initial.
     * @param array $additionalparams Any additional parameters required for the form submission URL.
     */
    public function __construct(stdClass $course, string $slug, string $firstinitial = '', string $lastinitial = '',
            string $firstinitialparam = 'sifirst', string $lastinitialparam = 'silast', array $additionalparams = []) {
        $this->course = $course;
        $this->slug = $slug;
        $this->firstinitial = $firstinitial;
        $this->lastinitial = $lastinitial;
        $this->additionalparams = $additionalparams;

        // Defaults to sifirst/silast, but flextable uses tifirst/tilast, for example.
        $this->firstinitialparam = $firstinitialparam;
        $this->lastinitialparam = $lastinitialparam;
    }

    /**
     * Export the data for the mustache template.
     *
     * @param renderer_base $output The renderer that will be used to render the output.
     * @return array
     */
    public function export_for_template(renderer_base $output) {
        global $OUTPUT, $PAGE;

        // User search.
        $searchvalue = optional_param('gpr_search', null, PARAM_NOTAGS);
        $userid = optional_param('gpr_userid', null, PARAM_INT);

        $renderer = $PAGE->get_renderer('core_user');
        $initialsbar = $renderer->partial_user_search($this->slug, $this->firstinitial, $this->lastinitial, true);

        $currentfilter = '';
        if ($this->firstinitial !== '' && $this->lastinitial !== '') {
            $currentfilter = get_string('filterbothactive', 'grades', ['first' => $this->firstinitial, 'last' => $this->lastinitial]);
        } else if ($this->firstinitial !== '') {
            $currentfilter = get_string('filterfirstactive', 'grades', ['first' => $this->firstinitial]);
        } else if ($this->lastinitial !== '') {
            $currentfilter = get_string('filterlastactive', 'grades', ['last' => $this->lastinitial]);
        }

        $PAGE->requires->js_call_amd('core_grades/searchwidget/initials', 'init',
            [$this->slug, $userid, $searchvalue, $this->firstinitialparam, $this->lastinitialparam, $this->additionalparams]);

        $formdata = (object) [
            'courseid' => $this->course->id,
            'initialsbars' => $initialsbar,
        ];
        $dropdowncontent = $OUTPUT->render_from_template('core_grades/initials_dropdown_form', $formdata);

        $initialselector = new comboboxsearch(
            false,
            $currentfilter !== '' ? $currentfilter : get_string('filterbyname', 'core_grades'),
            $dropdowncontent,
            'initials-selector',
            'initialswidget',
            'initialsdropdown',
            $currentfilter !== '' ? get_string('name') : null,
            true,
            get_string('filterbyname', 'core_grades'),
            'nameinitials',
            json_encode([
                'first' => $this->firstinitial,
                'last' => $this->lastinitial,
            ])
        );

        return $initialselector->export_for_template($OUTPUT);
    }

    /**
     * Returns the template for the group selector.
     *
     * @return string
     */
    public function get_template(): string {
        return 'core/comboboxsearch';
    }
}
