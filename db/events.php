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
 * Event observers.
 *
 * @package    local_xpcelebration
 * @copyright  2026 Eduardo Kraus
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$observers = [
    // Future/native Personal XP event. The current Personal XP release does not emit it yet,
    // so the core source events below provide a compatibility bridge using its public API.
    [
        'eventname' => '\\local_personalxp\\event\\xp_awarded',
        'callback' => '\\local_xpcelebration\\observer::personalxp_event',
        'priority' => -100,
    ],
    [
        'eventname' => '\\core\\event\\course_module_completion_updated',
        'callback' => '\\local_xpcelebration\\observer::personalxp_source_event',
        'priority' => -100,
    ],
    [
        'eventname' => '\\core\\event\\course_completed',
        'callback' => '\\local_xpcelebration\\observer::personalxp_source_event',
        'priority' => -100,
    ],
    [
        'eventname' => '\\mod_forum\\event\\post_created',
        'callback' => '\\local_xpcelebration\\observer::personalxp_source_event',
        'priority' => -100,
    ],
    [
        'eventname' => '\\mod_quiz\\event\\attempt_submitted',
        'callback' => '\\local_xpcelebration\\observer::personalxp_source_event',
        'priority' => -100,
    ],
];
