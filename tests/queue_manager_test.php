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
 * tests/queue_manager_test.php for local_xpcelebration.
 *
 * @package    local_xpcelebration
 * @copyright  2026 Eduardo Kraus
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_xpcelebration;

use local_xpcelebration\queue_manager;

/**
 * Queue semantics tests.
 *
 * @package local_xpcelebration
 * @covers \local_xpcelebration\queue_manager
 */
final class queue_manager_test extends \advanced_testcase {
    public function test_celebration_is_claimed_only_once(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $this->setUser($user);

        $id = api::queue($user->id, $course->id, 'levelup', 'Level 2', '100 XP');
        $first = queue_manager::claim_pending($user->id, $course->id, 1);
        $second = queue_manager::claim_pending($user->id, $course->id, 1);

        $this->assertCount(1, $first);
        $this->assertSame($id, (int)$first[0]->id);
        $this->assertSame('shown', $first[0]->status);
        $this->assertCount(0, $second);
    }

    public function test_multiple_events_form_priority_queue(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $this->setUser($user);

        $lowid = api::queue($user->id, $course->id, 'xpmilestone', 'XP', 'XP milestone', ['_priority' => 10]);
        $highid = api::queue($user->id, $course->id, 'levelup', 'Level', 'Level up', ['_priority' => 100]);
        $mediumid = api::queue($user->id, $course->id, 'streak', 'Streak', 'Streak', ['_priority' => 50]);

        $first = queue_manager::claim_pending($user->id, $course->id, 1);
        $second = queue_manager::claim_pending($user->id, $course->id, 1);
        $third = queue_manager::claim_pending($user->id, $course->id, 1);

        $this->assertSame($highid, (int)$first[0]->id);
        $this->assertSame($mediumid, (int)$second[0]->id);
        $this->assertSame($lowid, (int)$third[0]->id);
    }

    public function test_expired_celebration_is_never_claimed(): void {
        global $DB;
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $this->setUser($user);

        $id = api::queue($user->id, $course->id, 'milestone', 'Old', 'Expired', [
            '_timeexpired' => time() - 10,
        ]);
        $record = $DB->get_record('local_xpcelebration_queue', ['id' => $id], '*', MUST_EXIST);
        $this->assertSame('expired', $record->status);
        $this->assertCount(0, queue_manager::claim_pending($user->id, $course->id, 1));
    }

    public function test_queue_isolated_by_user(): void {
        $this->resetAfterTest();
        $usera = $this->getDataGenerator()->create_user();
        $userb = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $this->setUser($usera);

        api::queue($userb->id, $course->id, 'rewardunlocked', 'Reward', 'For B');
        $this->assertCount(0, queue_manager::claim_pending($usera->id, $course->id, 1));
    }

    public function test_queue_isolated_by_course_context(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $coursea = $this->getDataGenerator()->create_course();
        $courseb = $this->getDataGenerator()->create_course();
        $this->setUser($user);

        $ida = api::queue($user->id, $coursea->id, 'goalcompleted', 'A', 'Course A');
        api::queue($user->id, $courseb->id, 'goalcompleted', 'B', 'Course B');

        $records = queue_manager::claim_pending($user->id, $coursea->id, 10);
        $this->assertCount(1, $records);
        $this->assertSame($ida, (int)$records[0]->id);
    }
}
