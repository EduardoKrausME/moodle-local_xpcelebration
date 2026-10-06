<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Reduced motion preference endpoint.
 *
 * @package    local_xpcelebration
 * @copyright  2026 Eduardo Kraus
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_xpcelebration\external;

use context_system;
use external_api;
use external_function_parameters;
use external_value;

defined('MOODLE_INTERNAL') || die;
global $CFG;
require_once($CFG->libdir . '/externallib.php');

/**
 * Persist learner preference.
 */
final class set_reduced_motion extends external_api {
    /**
     * Describe the external function parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'enabled' => new external_value(PARAM_BOOL, 'Reduce celebration animations'),
        ]);
    }

    /**
     * Store the reduced-motion preference.
     *
     * @param bool $enabled Preference.
     * @return bool
     */
    public static function execute(bool $enabled): bool {
        global $USER;

        $params = self::validate_parameters(self::execute_parameters(), ['enabled' => $enabled]);
        self::validate_context(context_system::instance());
        require_login();
        set_user_preference('local_xpcelebration_reducedmotion', (int)$params['enabled'], (int)$USER->id);
        return (bool)$params['enabled'];
    }

    /**
     * Describe the external function return value.
     *
     * @return external_value
     */
    public static function execute_returns(): external_value {
        return new external_value(PARAM_BOOL, 'Stored preference');
    }
}
