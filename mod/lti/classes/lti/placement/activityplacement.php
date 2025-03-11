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

namespace mod_lti\lti\placement;

use core_ltix\local\placement\deeplinking_placement_handler;

/**
 * Deep linking placement handler.
 *
 * @package    mod_lti
 * @copyright  2025 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class activityplacement extends deeplinking_placement_handler {

    public static function instance(): static {
        return new self();
    }

    #[\Override]
    public static function format_contentitem_return_data(string $contentitemsjson, object $tool): \stdClass|string {
        $items = json_decode($contentitemsjson);
        if (empty($items)) {
            throw new \moodle_exception('errorinvaliddata', 'core_ltix', '', $contentitemsjson);
        }
        if (!isset($items->{'@graph'}) || !is_array($items->{'@graph'})) {
            throw new \moodle_exception('errorinvalidresponseformat', 'core_ltix');
        }

        $config = null;
        $items = $items->{'@graph'};
        if (!empty($items)) {
            $typeconfig = \core_ltix\helper::get_type_type_config($tool->id);
            if (count($items) == 1) {
                $config = self::content_item_to_form($tool, $typeconfig, $items[0]);
            } else {
                $multiple = [];
                foreach ($items as $item) {
                    $multiple[] = self::content_item_to_form($tool, $typeconfig, $item);
                }
                $config = new \stdClass();
                $config->multiple = $multiple;
            }
        }
        return $config;
    }

    /**
     * Converts LTI 1.1 Content Item for LTI Link to Form data.
     *
     * @param object $tool Tool for which the item is created for.
     * @param object $typeconfig The tool configuration.
     * @param object $item Item populated from JSON to be converted to Form form
     *
     * @return \stdClass Form config for the item
     */
    public static function content_item_to_form(object $tool, object $typeconfig, object $item): \stdClass {
        global $OUTPUT;

        $config = new \stdClass();
        $config->name = '';
        if (isset($item->title)) {
            $config->name = $item->title;
        }
        if (empty($config->name)) {
            $config->name = $tool->name;
        }
        if (isset($item->text)) {
            $config->introeditor = [
                'text' => $item->text,
                'format' => FORMAT_PLAIN
            ];
        } else {
            $config->introeditor = [
                'text' => '',
                'format' => FORMAT_PLAIN
            ];
        }
        if (isset($item->icon->{'@id'})) {
            $iconurl = new \moodle_url($item->icon->{'@id'});
            // Assign item's icon URL to secureicon or icon depending on its scheme.
            if (strtolower($iconurl->get_scheme()) === 'https') {
                $config->secureicon = $iconurl->out(false);
            } else {
                $config->icon = $iconurl->out(false);
            }
        }
        if (isset($item->url)) {
            $url = new \moodle_url($item->url);
            $config->toolurl = $url->out(false);
            $config->typeid = 0;
        } else {
            $config->typeid = $tool->id;
        }
        $config->instructorchoiceacceptgrades = \core_ltix\constants::LTI_SETTING_NEVER;
        $islti2 = $tool->ltiversion === \core_ltix\constants::LTI_VERSION_2;
        if (!$islti2 && isset($typeconfig->lti_acceptgrades)) {
            $acceptgrades = $typeconfig->lti_acceptgrades;
            if ($acceptgrades == \core_ltix\constants::LTI_SETTING_ALWAYS) {
                // We create a line item regardless if the definition contains one or not.
                $config->instructorchoiceacceptgrades = \core_ltix\constants::LTI_SETTING_ALWAYS;
                $config->grade_modgrade_point = 100;
            }
            if ($acceptgrades == \core_ltix\constants::LTI_SETTING_DELEGATE || $acceptgrades == \core_ltix\constants::LTI_SETTING_ALWAYS) {
                if (isset($item->lineItem)) {
                    $lineitem = $item->lineItem;
                    $config->instructorchoiceacceptgrades = \core_ltix\constants::LTI_SETTING_ALWAYS;
                    $maxscore = 100;
                    if (isset($lineitem->scoreConstraints)) {
                        $sc = $lineitem->scoreConstraints;
                        if (isset($sc->totalMaximum)) {
                            $maxscore = $sc->totalMaximum;
                        } else if (isset($sc->normalMaximum)) {
                            $maxscore = $sc->normalMaximum;
                        }
                    }
                    $config->grade_modgrade_point = $maxscore;
                    $config->lineitemresourceid = '';
                    $config->lineitemtag = '';
                    $config->lineitemsubreviewurl = '';
                    $config->lineitemsubreviewparams = '';
                    if (isset($lineitem->assignedActivity) && isset($lineitem->assignedActivity->activityId)) {
                        $config->lineitemresourceid = $lineitem->assignedActivity->activityId?:'';
                    }
                    if (isset($lineitem->tag)) {
                        $config->lineitemtag = $lineitem->tag?:'';
                    }
                    if (isset($lineitem->submissionReview)) {
                        $subreview = $lineitem->submissionReview;
                        $config->lineitemsubreviewurl = 'DEFAULT';
                        if (!empty($subreview->url)) {
                            $config->lineitemsubreviewurl = $subreview->url;
                        }
                        if (isset($subreview->custom)) {
                            $config->lineitemsubreviewparams = \core_ltix\helper::params_to_string($subreview->custom);
                        }
                    }
                }
            }
        }
        $config->instructorchoicesendname = \core_ltix\constants::LTI_SETTING_NEVER;
        $config->instructorchoicesendemailaddr = \core_ltix\constants::LTI_SETTING_NEVER;

        // Since 4.3, the launch container is dictated by the value set in tool configuration and isn't controllable by content items.
        $config->launchcontainer = \core_ltix\constants::LTI_LAUNCH_CONTAINER_DEFAULT;

        if (isset($item->custom)) {
            $config->instructorcustomparameters = \core_ltix\helper::params_to_string($item->custom);
        }

        // Pass an indicator to the relevant form field.
        $config->selectcontentindicator = $OUTPUT->pix_icon('i/valid', get_string('yes')) . get_string('contentselected', 'core_ltix');

        return $config;
    }
}
