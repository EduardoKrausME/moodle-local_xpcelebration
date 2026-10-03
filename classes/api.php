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
 * Public integration API.
 *
 * @package    local_xpcelebration
 * @copyright  2026 Eduardo Kraus
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_xpcelebration;

use local_xpcelebration\local\queue_manager;

/**
 * Public API used by Personal XP and companion plugins.
 */
final class api {
    /** @var string Level changed. */
    public const TYPE_LEVELUP = 'levelup';
    /** @var string Cumulative XP threshold reached. */
    public const TYPE_XP_MILESTONE = 'xpmilestone';
    /** @var string Personal best/record. */
    public const TYPE_PERSONAL_RECORD = 'personalrecord';
    /** @var string Learner goal completed. */
    public const TYPE_GOAL_COMPLETED = 'goalcompleted';
    /** @var string Streak threshold reached. */
    public const TYPE_STREAK = 'streak';
    /** @var string Generic milestone. */
    public const TYPE_MILESTONE = 'milestone';
    /** @var string Quest completed. */
    public const TYPE_QUEST_COMPLETED = 'questcompleted';
    /** @var string Reward unlocked. */
    public const TYPE_REWARD_UNLOCKED = 'rewardunlocked';
    /** @var string Important course progress. */
    public const TYPE_COURSE_PROGRESS = 'courseprogress';

    /**
     * Queue a learner celebration.
     *
     * Reserved data keys:
     * - _priority: integer -1000..1000.
     * - _timeexpired: Unix timestamp.
     * - _theme: minimal|confetti|levelup|achievement.
     * - _display: modal|toast|achievement.
     * - _animation: confetti|particles|glow|badge|progress|none.
     *
     * Any scalar data key can also be used as a {placeholder} in title/message.
     *
     * @param int $userid Target user.
     * @param int $courseid Course where the celebration belongs.
     * @param string $type Celebration type.
     * @param string $title Title, optionally with placeholders.
     * @param string $message Message, optionally with placeholders.
     * @param array $data Presentation data and placeholders.
     * @return int Queue record id.
     */
    public static function queue(
        int $userid,
        int $courseid,
        string $type,
        string $title,
        string $message,
        array $data = []
    ): int {
        return queue_manager::queue($userid, $courseid, $type, $title, $message, $data);
    }
}
