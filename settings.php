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
 * Admin settings.
 *
 * @package    local_xpcelebration
 * @copyright  2026 Eduardo Kraus
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_xpcelebration', get_string('pluginname', 'local_xpcelebration'));

    $settings->add(new admin_setting_configcheckbox(
        'local_xpcelebration/confetti',
        get_string('settingconfetti', 'local_xpcelebration'),
        get_string('settingconfetti_desc', 'local_xpcelebration'),
        1
    ));

    $settings->add(new admin_setting_configtext(
        'local_xpcelebration/duration',
        get_string('settingduration', 'local_xpcelebration'),
        get_string('settingduration_desc', 'local_xpcelebration'),
        4500,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configselect(
        'local_xpcelebration/intensity',
        get_string('settingintensity', 'local_xpcelebration'),
        get_string('settingintensity_desc', 'local_xpcelebration'),
        'medium',
        [
            'low' => get_string('intensitylow', 'local_xpcelebration'),
            'medium' => get_string('intensitymedium', 'local_xpcelebration'),
            'high' => get_string('intensityhigh', 'local_xpcelebration'),
        ]
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_xpcelebration/sound',
        get_string('settingsound', 'local_xpcelebration'),
        get_string('settingsound_desc', 'local_xpcelebration'),
        0
    ));

    $settings->add(new admin_setting_configtext(
        'local_xpcelebration/sessionlimit',
        get_string('settingsessionlimit', 'local_xpcelebration'),
        get_string('settingsessionlimit_desc', 'local_xpcelebration'),
        5,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'local_xpcelebration/interval',
        get_string('settinginterval', 'local_xpcelebration'),
        get_string('settinginterval_desc', 'local_xpcelebration'),
        900,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configselect(
        'local_xpcelebration/defaulttheme',
        get_string('settingdefaulttheme', 'local_xpcelebration'),
        get_string('settingdefaulttheme_desc', 'local_xpcelebration'),
        'minimal',
        [
            'minimal' => get_string('thememinimal', 'local_xpcelebration'),
            'confetti' => get_string('themeconfetti', 'local_xpcelebration'),
            'levelup' => get_string('themelevelup', 'local_xpcelebration'),
            'achievement' => get_string('themeachievement', 'local_xpcelebration'),
        ]
    ));

    $settings->add(new admin_setting_configtextarea(
        'local_xpcelebration/xpmilestones',
        get_string('settingxpmilestones', 'local_xpcelebration'),
        get_string('settingxpmilestones_desc', 'local_xpcelebration'),
        "500\n1000\n2000\n5000\n10000",
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configtext(
        'local_xpcelebration/expirydays',
        get_string('settingexpirydays', 'local_xpcelebration'),
        get_string('settingexpirydays_desc', 'local_xpcelebration'),
        14,
        PARAM_INT
    ));

    $ADMIN->add('localplugins', $settings);
}
