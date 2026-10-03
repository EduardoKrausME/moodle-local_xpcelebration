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
 * Celebration shown event.
 *
 * @package    local_xpcelebration
 * @copyright  2026 Eduardo Kraus
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_xpcelebration\event;

use core\event\base;

/**
 * Event emitted when a queue record is atomically claimed for display.
 */
final class celebration_shown extends base {
    /**
     * Event properties.
     *
     * @return void
     */
    protected function init(): void {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'local_xpcelebration_queue';
    }

    /**
     * Return the event name.
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('eventcelebrationshown', 'local_xpcelebration');
    }

    /**
     * Do not include title/message or arbitrary data in the event log.
     *
     * @return string
     */
    public function get_description(): string {
        return "The user with id '{$this->userid}' was shown celebration queue item '{$this->objectid}' " .
            "of type '{$this->other['type']}' in course '{$this->other['courseid']}'.";
    }

    /**
     * Validate required event data.
     *
     * @return void
     */
    protected function validate_data(): void {
        parent::validate_data();
        if (!isset($this->other['type']) || !isset($this->other['courseid'])) {
            throw new \coding_exception('celebration_shown requires type and courseid.');
        }
    }
}
