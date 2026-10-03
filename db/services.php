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
 * AJAX external functions.
 *
 * @package    local_xpcelebration
 * @copyright  2026 Eduardo Kraus
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$functions = [
    'local_xpcelebration_claim_pending' => [
        'classname' => '\\local_xpcelebration\\external\\claim_pending',
        'methodname' => 'execute',
        'description' => 'Atomically claim pending celebrations for the current user and course.',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => true,
    ],
    'local_xpcelebration_set_reduced_motion' => [
        'classname' => '\\local_xpcelebration\\external\\set_reduced_motion',
        'methodname' => 'execute',
        'description' => 'Store the current user reduced-animation preference.',
        'type' => 'write',
        'ajax' => true,
        'loginrequired' => true,
    ],
];
