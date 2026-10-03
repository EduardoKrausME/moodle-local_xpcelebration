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
 * Claim pending celebrations AJAX endpoint.
 *
 * @package    local_xpcelebration
 * @copyright  2026 Eduardo Kraus
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_xpcelebration\external;

use context_course;
use external_api;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;
use local_xpcelebration\local\presentation;
use local_xpcelebration\local\queue_manager;

defined('MOODLE_INTERNAL') || die;
global $CFG;
require_once($CFG->libdir . '/externallib.php');

/**
 * Atomically claim the current user's queue.
 */
final class claim_pending extends external_api {
    /**
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Current course id'),
            'limit' => new external_value(PARAM_INT, 'Maximum number to claim', VALUE_DEFAULT, 5),
        ]);
    }

    /**
     * @param int $courseid Course id.
     * @param int $limit Requested limit.
     * @return array
     */
    public static function execute(int $courseid, int $limit = 5): array {
        global $DB, $SESSION, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
            'limit' => $limit,
        ]);
        $course = $DB->get_record('course', ['id' => $params['courseid']], '*', MUST_EXIST);
        $context = context_course::instance($course->id);
        self::validate_context($context);
        require_login($course);
        require_capability('local/xpcelebration:view', $context);

        $configured = (int)get_config('local_xpcelebration', 'sessionlimit');
        $configured = max(1, min(20, $configured ?: 5));
        $alreadyshown = isset($SESSION->local_xpcelebration_shown_count)
            ? (int)$SESSION->local_xpcelebration_shown_count : 0;
        $remaining = max(0, $configured - $alreadyshown);
        if ($remaining === 0) {
            return [];
        }

        $limit = max(1, min(20, (int)$params['limit'], $remaining));
        $records = queue_manager::claim_pending((int)$USER->id, (int)$course->id, $limit);
        $SESSION->local_xpcelebration_shown_count = $alreadyshown + count($records);

        return array_map(static function($record): array {
            return presentation::prepare($record);
        }, $records);
    }

    /**
     * @return external_multiple_structure
     */
    public static function execute_returns(): external_multiple_structure {
        return new external_multiple_structure(new external_single_structure([
            'id' => new external_value(PARAM_INT, 'Queue id'),
            'courseid' => new external_value(PARAM_INT, 'Course id'),
            'type' => new external_value(PARAM_TEXT, 'Celebration type'),
            'title' => new external_value(PARAM_TEXT, 'Resolved title'),
            'message' => new external_value(PARAM_TEXT, 'Resolved message'),
            'priority' => new external_value(PARAM_INT, 'Priority'),
            'theme' => new external_value(PARAM_ALPHANUMEXT, 'Theme'),
            'display' => new external_value(PARAM_ALPHANUMEXT, 'Display surface'),
            'animation' => new external_value(PARAM_ALPHANUMEXT, 'Animation'),
            'progress' => new external_value(PARAM_INT, 'Progress 0-100'),
            'hasprogress' => new external_value(PARAM_BOOL, 'Whether progress should be rendered'),
        ]));
    }
}
