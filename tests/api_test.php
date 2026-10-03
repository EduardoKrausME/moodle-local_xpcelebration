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
 * tests/api_test.php for local_xpcelebration.
 *
 * @package    local_xpcelebration
 * @copyright  2026 Eduardo Kraus
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_xpcelebration;

/**
 * Public API tests.
 *
 * @package local_xpcelebration
 * @covers \local_xpcelebration\api
 */
final class api_test extends \advanced_testcase {
    public function test_queue_persists_pending_celebration(): void {
        global $DB;
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $id = api::queue($user->id, $course->id, 'milestone', 'Hello {fullname}', 'Reached {milestone}', [
            'milestone' => 10,
        ]);

        $record = $DB->get_record('local_xpcelebration_queue', ['id' => $id], '*', MUST_EXIST);
        $this->assertSame((int)$user->id, (int)$record->userid);
        $this->assertSame((int)$course->id, (int)$record->courseid);
        $this->assertSame('pending', $record->status);
        $this->assertSame('milestone', $record->type);
    }

    public function test_invalid_user_or_course_is_rejected(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();

        $this->expectException(\invalid_parameter_exception::class);
        api::queue($user->id, $course->id + 999999, 'levelup', 'Title', 'Message');
    }
}
