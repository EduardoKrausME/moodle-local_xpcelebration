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
 * Event observer.
 *
 * @package    local_xpcelebration
 * @copyright  2026 Eduardo Kraus
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_xpcelebration;

use core\event\base;
use local_xpcelebration\local\personalxp_bridge;
use Throwable;

/**
 * Backend Personal XP change observers.
 */
final class observer {
    /**
     * Observe a future/native Personal XP award event.
     *
     * @param base $event Event.
     * @return void
     */
    public static function personalxp_event(base $event): void {
        self::sync_event($event, false);
    }

    /**
     * Compatibility observer for the source events used by current local_personalxp.
     * Priority -100 makes this run after Personal XP's default-priority observer.
     *
     * @param base $event Event.
     * @return void
     */
    public static function personalxp_source_event(base $event): void {
        self::sync_event($event, $event instanceof \core\event\course_completed);
    }

    /**
     * Synchronise Personal XP state for an observed event.
     *
     * @param base $event Event.
     * @param bool $coursecompleted Whether to queue 100% course progress.
     * @return void
     */
    private static function sync_event(base $event, bool $coursecompleted): void {
        $userid = (int)($event->relateduserid ?: $event->userid);
        $courseid = (int)$event->courseid;
        if ($userid <= 0 || $courseid <= 0) {
            return;
        }

        try {
            personalxp_bridge::sync($userid, $courseid);
            if ($coursecompleted) {
                personalxp_bridge::queue_course_completed($userid, $courseid);
            }
        } catch (Throwable $exception) {
            debugging('local_xpcelebration observer failed: ' . $exception->getMessage(), DEBUG_DEVELOPER);
        }
    }
}
