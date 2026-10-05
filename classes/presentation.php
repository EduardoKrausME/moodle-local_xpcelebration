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
 * Presentation preparation and placeholder resolution.
 *
 * @package    local_xpcelebration
 * @copyright  2026 Eduardo Kraus
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_xpcelebration;

use core_user;
use stdClass;

/**
 * Presentation helper.
 */
final class presentation {
    /** @var string[] Allowed themes. */
    private const THEMES = ['minimal', 'confetti', 'levelup', 'achievement'];
    /** @var string[] Allowed display surfaces. */
    private const DISPLAYS = ['modal', 'toast', 'achievement'];
    /** @var string[] Allowed animations. */
    private const ANIMATIONS = ['confetti', 'particles', 'glow', 'badge', 'progress', 'none'];

    /**
     * Prepare only the data needed by the browser.
     *
     * @param stdClass $record Queue record.
     * @return array
     */
    public static function prepare(stdClass $record): array {
        $data = json_decode((string)$record->datajson, true);
        if (!is_array($data)) {
            $data = [];
        }

        $user = core_user::get_user((int)$record->userid, '*', MUST_EXIST);
        $course = get_course((int)$record->courseid);
        $values = [
            'fullname' => fullname($user),
            'coursename' => format_string($course->fullname, true, ['context' => \context_course::instance($course->id)]),
        ];
        foreach ($data as $key => $value) {
            if (is_scalar($value) || $value === null) {
                $values[(string)$key] = $value === null ? '' : (string)$value;
            }
        }

        $theme = isset($data['_theme']) ? (string)$data['_theme'] : self::theme_for_type((string)$record->type);
        if (!in_array($theme, self::THEMES, true)) {
            $theme = self::default_theme();
        }

        $display = isset($data['_display']) ? (string)$data['_display'] : self::display_for_type((string)$record->type);
        if (!in_array($display, self::DISPLAYS, true)) {
            $display = 'achievement';
        }

        $animation = isset($data['_animation']) ? (string)$data['_animation'] : self::animation_for_type((string)$record->type);
        if (!in_array($animation, self::ANIMATIONS, true)) {
            $animation = 'none';
        }

        $progress = isset($data['progress']) ? max(0, min(100, (int)$data['progress'])) : 0;

        return [
            'id' => (int)$record->id,
            'courseid' => (int)$record->courseid,
            'type' => (string)$record->type,
            'title' => self::replace_placeholders((string)$record->title, $values),
            'message' => self::replace_placeholders((string)$record->message, $values),
            'priority' => (int)$record->priority,
            'theme' => $theme,
            'display' => $display,
            'animation' => $animation,
            'progress' => $progress,
            'hasprogress' => $progress > 0,
        ];
    }

    /**
     * Client settings, with strict bounds so bad admin values cannot create aggressive animation loops.
     *
     * @param int $userid User id.
     * @return array
     */
    public static function get_client_config(int $userid): array {
        $duration = (int)get_config('local_xpcelebration', 'duration');
        $duration = max(1500, min(15000, $duration ?: 4500));
        $interval = (int)get_config('local_xpcelebration', 'interval');
        $interval = max(250, min(10000, $interval ?: 900));
        $sessionlimit = (int)get_config('local_xpcelebration', 'sessionlimit');
        $sessionlimit = max(1, min(20, $sessionlimit ?: 5));
        $intensity = (string)get_config('local_xpcelebration', 'intensity');
        if (!in_array($intensity, ['low', 'medium', 'high'], true)) {
            $intensity = 'medium';
        }

        $confetti = get_config('local_xpcelebration', 'confetti');

        return [
            'confetti' => $confetti === false ? true : (bool)$confetti,
            'duration' => $duration,
            'interval' => $interval,
            'sessionlimit' => $sessionlimit,
            'intensity' => $intensity,
            'sound' => (bool)get_config('local_xpcelebration', 'sound'),
            'reducedmotion' => (bool)get_user_preferences('local_xpcelebration_reducedmotion', 0, $userid),
        ];
    }

    /**
     * Replace {placeholder} tokens from safe scalar values.
     *
     * @param string $text Template text.
     * @param array $values Values.
     * @return string
     */
    private static function replace_placeholders(string $text, array $values): string {
        $replace = [];
        foreach ($values as $key => $value) {
            $replace['{' . $key . '}'] = (string)$value;
        }
        return strtr($text, $replace);
    }

    /**
     * Resolve the default theme for a celebration type.
     *
     * @param string $type Type.
     * @return string
     */
    private static function theme_for_type(string $type): string {
        if ($type === 'levelup') {
            return 'levelup';
        }
        if ($type === 'xpmilestone') {
            return 'confetti';
        }
        if (in_array($type, ['personalrecord', 'goalcompleted', 'streak', 'milestone', 'questcompleted', 'rewardunlocked'], true)) {
            return 'achievement';
        }
        return self::default_theme();
    }

    /**
     * Return the configured default theme.
     *
     * @return string
     */
    private static function default_theme(): string {
        $theme = (string)get_config('local_xpcelebration', 'defaulttheme');
        return in_array($theme, self::THEMES, true) ? $theme : 'minimal';
    }

    /**
     * Resolve the display surface for a celebration type.
     *
     * @param string $type Type.
     * @return string
     */
    private static function display_for_type(string $type): string {
        if ($type === 'levelup') {
            return 'modal';
        }
        if ($type === 'courseprogress') {
            return 'toast';
        }
        return 'achievement';
    }

    /**
     * Resolve the animation for a celebration type.
     *
     * @param string $type Type.
     * @return string
     */
    private static function animation_for_type(string $type): string {
        $map = [
            'levelup' => 'confetti',
            'xpmilestone' => 'glow',
            'personalrecord' => 'badge',
            'goalcompleted' => 'particles',
            'streak' => 'glow',
            'milestone' => 'badge',
            'questcompleted' => 'particles',
            'rewardunlocked' => 'badge',
            'courseprogress' => 'progress',
        ];
        return $map[$type] ?? 'none';
    }
}
