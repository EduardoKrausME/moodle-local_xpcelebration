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
 * Integration bridge for local_personalxp public API.
 *
 * @package    local_xpcelebration
 * @copyright  2026 Eduardo Kraus
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_xpcelebration\local;

use local_personalxp\service\xp_manager;
use local_xpcelebration\api;

/**
 * Detect Personal XP changes without reading Personal XP tables directly.
 */
final class personalxp_bridge {
    /**
     * Store a baseline without producing historical celebrations.
     *
     * @param int $userid User id.
     * @param int $courseid Course id.
     * @return void
     */
    public static function prime_state(int $userid, int $courseid): void {
        global $DB;

        if ($userid <= 0 || $courseid <= 0 || $DB->record_exists('local_xpcelebration_state', [
            'userid' => $userid,
            'courseid' => $courseid,
        ])) {
            return;
        }

        $xp = xp_manager::get_total($userid, $courseid);
        $level = xp_manager::get_level_state($xp);
        try {
            $DB->insert_record('local_xpcelebration_state', (object)[
                'userid' => $userid,
                'courseid' => $courseid,
                'lastxp' => $xp,
                'lastlevelxp' => (int)$level['current']['xp'],
                'lastprogress' => 0,
                'timemodified' => time(),
            ]);
        } catch (\dml_write_exception $exception) {
            // Another concurrent request may have created the baseline first.
        }
    }

    /**
     * Compare persisted baseline with Personal XP's current public state and queue changes.
     *
     * @param int $userid User id.
     * @param int $courseid Course id.
     * @return int[] Queue ids created by this sync.
     */
    public static function sync(int $userid, int $courseid): array {
        global $DB;

        if ($userid <= 0 || $courseid <= 0) {
            return [];
        }

        // A baseline avoids replaying historical levels. It is normally created when the learner opens the course.
        if (!$DB->record_exists('local_xpcelebration_state', ['userid' => $userid, 'courseid' => $courseid])) {
            self::prime_state($userid, $courseid);
            return [];
        }

        $transaction = $DB->start_delegated_transaction();
        $state = $DB->get_record_sql(
            "SELECT * FROM {local_xpcelebration_state}
              WHERE userid = :userid AND courseid = :courseid
              FOR UPDATE",
            ['userid' => $userid, 'courseid' => $courseid],
            MUST_EXIST
        );
        $currentxp = xp_manager::get_total($userid, $courseid);

        $previousxp = (int)$state->lastxp;
        if ($currentxp === $previousxp) {
            $transaction->allow_commit();
            return [];
        }

        $created = [];
        $previouslevel = xp_manager::get_level_state($previousxp);
        $currentlevel = xp_manager::get_level_state($currentxp);

        if ($currentxp > $previousxp && (int)$currentlevel['current']['xp'] > (int)$previouslevel['current']['xp']) {
            $previousnumber = self::level_number((int)$previouslevel['current']['xp']);
            $currentnumber = self::level_number((int)$currentlevel['current']['xp']);
            $created[] = api::queue(
                $userid,
                $courseid,
                api::TYPE_LEVELUP,
                get_string('leveluptitle', 'local_xpcelebration'),
                get_string('levelupmessage', 'local_xpcelebration'),
                [
                    'level' => $currentnumber,
                    'previouslevel' => $previousnumber,
                    'levelname' => (string)$currentlevel['current']['name'],
                    'xp' => $currentxp,
                    '_theme' => 'levelup',
                    '_display' => 'modal',
                    '_animation' => 'confetti',
                ]
            );
        }

        if ($currentxp > $previousxp) {
            $milestone = self::highest_crossed_milestone($previousxp, $currentxp);
            if ($milestone !== null) {
                $created[] = api::queue(
                    $userid,
                    $courseid,
                    api::TYPE_XP_MILESTONE,
                    get_string('xpmilestonetitle', 'local_xpcelebration'),
                    get_string('xpmilestonemessage', 'local_xpcelebration'),
                    [
                        'milestone' => $milestone,
                        'xp' => $currentxp,
                        'level' => self::level_number((int)$currentlevel['current']['xp']),
                        '_theme' => 'confetti',
                        '_display' => 'achievement',
                        '_animation' => 'glow',
                    ]
                );
            }
        }

        $state->lastxp = $currentxp;
        $state->lastlevelxp = (int)$currentlevel['current']['xp'];
        $state->timemodified = time();
        $DB->update_record('local_xpcelebration_state', $state);
        $transaction->allow_commit();

        return $created;
    }

    /**
     * Mark a completed course as important course progress.
     *
     * @param int $userid User id.
     * @param int $courseid Course id.
     * @return int Queue id.
     */
    public static function queue_course_completed(int $userid, int $courseid): int {
        return api::queue(
            $userid,
            $courseid,
            api::TYPE_COURSE_PROGRESS,
            get_string('coursecompletedtitle', 'local_xpcelebration'),
            get_string('coursecompletedmessage', 'local_xpcelebration'),
            [
                'progress' => 100,
                '_theme' => 'minimal',
                '_display' => 'toast',
                '_animation' => 'progress',
                '_priority' => 90,
            ]
        );
    }

    /**
     * @param int $threshold Level XP threshold.
     * @return int One-based level number.
     */
    private static function level_number(int $threshold): int {
        $levels = xp_manager::parse_levels();
        foreach ($levels as $index => $level) {
            if ((int)$level['xp'] === $threshold) {
                return $index + 1;
            }
        }
        return 1;
    }

    /**
     * Return only the highest XP milestone crossed in one change to avoid celebration storms.
     *
     * @param int $previousxp Previous XP.
     * @param int $currentxp Current XP.
     * @return int|null
     */
    private static function highest_crossed_milestone(int $previousxp, int $currentxp): ?int {
        $configured = (string)get_config('local_xpcelebration', 'xpmilestones');
        if (trim($configured) === '') {
            $configured = "500\n1000\n2000\n5000\n10000";
        }

        $crossed = [];
        foreach (preg_split('/[\s,;]+/', $configured) as $value) {
            if ($value === '' || !is_numeric($value)) {
                continue;
            }
            $value = (int)$value;
            if ($value > $previousxp && $value <= $currentxp && $value > 0) {
                $crossed[] = $value;
            }
        }
        return $crossed ? max($crossed) : null;
    }
}
