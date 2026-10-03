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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <https://www.gnu.org/licenses/>.

/**
 * Library callbacks for local_xpcelebration.
 *
 * @package    local_xpcelebration
 * @copyright  2026 Eduardo Kraus
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Register user preferences owned by the plugin.
 *
 * @return array
 */
function local_xpcelebration_user_preferences(): array {
    return [
        'local_xpcelebration_reducedmotion' => [
            'type' => PARAM_BOOL,
            'null' => NULL_NOT_ALLOWED,
            'default' => 0,
        ],
    ];
}

/**
 * Initialise the visual queue on standard Moodle pages.
 *
 * The legacy output callback is intentionally used because the plugin supports Moodle 4.1.
 * Moodle 4.4+ still processes this callback through the output hook compatibility layer.
 *
 * @return string
 */
function local_xpcelebration_before_standard_html_head(): string {
    global $PAGE, $USER;

    if (!isloggedin() || isguestuser() || empty($USER->id)) {
        return '';
    }

    try {
        $courseid = !empty($PAGE->course->id) ? (int)$PAGE->course->id : 0;
        if ($courseid <= 0) {
            return '';
        }

        $context = context_course::instance($courseid, IGNORE_MISSING);
        if (!$context || !has_capability('local/xpcelebration:view', $context, $USER->id)) {
            return '';
        }

        // Prime the Personal XP snapshot before the learner performs another XP-generating action.
        \local_xpcelebration\local\personalxp_bridge::prime_state((int)$USER->id, $courseid);

        $config = \local_xpcelebration\local\presentation::get_client_config((int)$USER->id);
        $PAGE->requires->js_call_amd('local_xpcelebration/celebration', 'init', [$courseid, $config]);
    } catch (Throwable $exception) {
        debugging('local_xpcelebration could not initialise: ' . $exception->getMessage(), DEBUG_DEVELOPER);
    }

    return '';
}
