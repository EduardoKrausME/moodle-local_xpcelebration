<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Hook callbacks for local_xpcelebration.
 *
 * @package    local_xpcelebration
 * @copyright  2026 Eduardo Kraus
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_xpcelebration;

use core\hook\output\before_standard_head_html_generation;

/**
 * Hook callback handlers.
 */
class hook_callbacks {
    /**
     * Initialise the visual queue on standard Moodle pages.
     *
     * @param before_standard_head_html_generation $hook The output hook.
     * @return void
     */
    public static function before_standard_head_html_generation(
        before_standard_head_html_generation $hook,
    ): void {
        global $CFG, $PAGE, $USER;

        if (during_initial_install() || isset($CFG->upgraderunning)) {
            return;
        }

        if (!isloggedin() || isguestuser() || empty($USER->id)) {
            return;
        }

        try {
            $courseid = !empty($PAGE->course->id) ? (int) $PAGE->course->id : 0;
            if ($courseid <= 0) {
                return;
            }

            $context = \context_course::instance($courseid, IGNORE_MISSING);
            if (!$context || !has_capability('local/xpcelebration:view', $context, $USER->id)) {
                return;
            }

            // Prime the Personal XP snapshot before the learner performs another XP-generating action.
            personalxp_bridge::prime_state((int) $USER->id, $courseid);

            $config = presentation::get_client_config((int) $USER->id);
            $PAGE->requires->js_call_amd('local_xpcelebration/celebration', 'init', [$courseid, $config]);
        } catch (\Throwable $exception) {
            debugging('local_xpcelebration could not initialise: ' . $exception->getMessage(), DEBUG_DEVELOPER);
        }
    }
}
