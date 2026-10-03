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
 * tests/reduced_motion_test.php for local_xpcelebration.
 *
 * @package    local_xpcelebration
 * @copyright  2026 Eduardo Kraus
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_xpcelebration;

use local_xpcelebration\local\presentation;

/**
 * Reduced motion preference tests.
 *
 * @package local_xpcelebration
 * @covers \local_xpcelebration\local\presentation
 */
final class reduced_motion_test extends \advanced_testcase {
    public function test_user_reduced_motion_is_exposed_to_amd_config(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        set_user_preference('local_xpcelebration_reducedmotion', 1, $user->id);

        $config = presentation::get_client_config($user->id);
        $this->assertTrue($config['reducedmotion']);
    }
}
