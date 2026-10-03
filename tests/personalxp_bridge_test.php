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
 * tests/personalxp_bridge_test.php for local_xpcelebration.
 *
 * @package    local_xpcelebration
 * @copyright  2026 Eduardo Kraus
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_xpcelebration;

use local_personalxp\service\xp_manager;
use local_xpcelebration\local\personalxp_bridge;

/**
 * Personal XP backend level detection tests.
 *
 * @package local_xpcelebration
 * @covers \local_xpcelebration\local\personalxp_bridge
 */
final class personalxp_bridge_test extends \advanced_testcase {
    public function test_backend_level_up_creates_one_celebration(): void {
        global $DB;
        $this->resetAfterTest();
        set_config('levels', "0|Beginner\n100|Explorer\n300|Specialist", 'local_personalxp');
        set_config('xpmilestones', '500 1000', 'local_xpcelebration');

        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        personalxp_bridge::prime_state($user->id, $course->id);

        $awarded = xp_manager::award(
            $user->id,
            $course->id,
            'phpunit',
            101,
            120,
            'PHPUnit award',
            'local_xpcelebration',
            'phpunit'
        );
        $this->assertTrue($awarded);

        personalxp_bridge::sync($user->id, $course->id);
        personalxp_bridge::sync($user->id, $course->id);

        $records = $DB->get_records('local_xpcelebration_queue', [
            'userid' => $user->id,
            'courseid' => $course->id,
            'type' => api::TYPE_LEVELUP,
        ]);
        $this->assertCount(1, $records);
        $record = reset($records);
        $data = json_decode($record->datajson, true);
        $this->assertSame(2, (int)$data['level']);
        $this->assertSame(1, (int)$data['previouslevel']);
        $this->assertSame(120, (int)$data['xp']);
    }

    public function test_first_snapshot_does_not_replay_historical_level(): void {
        global $DB;
        $this->resetAfterTest();
        set_config('levels', "0|Beginner\n100|Explorer", 'local_personalxp');

        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        xp_manager::award($user->id, $course->id, 'beforeinstall', 1, 150, 'Existing XP', 'phpunit', 'phpunit');

        personalxp_bridge::sync($user->id, $course->id);
        $this->assertFalse($DB->record_exists('local_xpcelebration_queue', [
            'userid' => $user->id,
            'courseid' => $course->id,
        ]));
    }
}
