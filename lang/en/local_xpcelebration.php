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
 * English strings.
 *
 * @package    local_xpcelebration
 * @copyright  2026 Eduardo Kraus
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$string['continue'] = 'Continue';
$string['coursecompletedmessage'] = 'You reached 100% progress in {coursename}.';
$string['coursecompletedtitle'] = 'Course progress completed';
$string['dismiss'] = 'Dismiss';
$string['enableanimations'] = 'Use standard animations';
$string['eventcelebrationshown'] = 'Celebration shown';
$string['intensityhigh'] = 'High';
$string['intensitylow'] = 'Low';
$string['intensitymedium'] = 'Medium';
$string['levelupmessage'] = 'You accumulated {xp} XP. Keep going at your own pace.';
$string['leveluptitle'] = 'Level {level} reached';
$string['pluginname'] = 'XP Celebration';
$string['privacy:export:celebrations'] = 'Celebrations';
$string['privacy:export:state'] = 'Personal XP snapshot';
$string['privacy:metadata:preference:reducedmotion'] = 'Stores whether the learner asked XP Celebration to reduce animations.';
$string['privacy:metadata:queue'] = 'Stores personal visual celebrations waiting to be shown or already shown.';
$string['privacy:metadata:queue:courseid'] = 'The course related to the celebration.';
$string['privacy:metadata:queue:datajson'] = 'Placeholder and presentation data supplied by the integration.';
$string['privacy:metadata:queue:message'] = 'The celebration message template.';
$string['privacy:metadata:queue:status'] = 'Whether the celebration is pending, shown, or expired.';
$string['privacy:metadata:queue:timecreated'] = 'When the celebration was queued.';
$string['privacy:metadata:queue:timeexpired'] = 'When the celebration stops being eligible for display.';
$string['privacy:metadata:queue:timeshown'] = 'When the celebration was claimed for display.';
$string['privacy:metadata:queue:title'] = 'The celebration title template.';
$string['privacy:metadata:queue:type'] = 'The semantic celebration type.';
$string['privacy:metadata:queue:userid'] = 'The user who owns the celebration.';
$string['privacy:metadata:state'] = 'Stores the last Personal XP snapshot used to detect personal level changes.';
$string['privacy:metadata:state:courseid'] = 'The course represented by the snapshot.';
$string['privacy:metadata:state:lastlevelxp'] = 'The XP threshold of the last observed level.';
$string['privacy:metadata:state:lastprogress'] = 'The last important progress value recorded for future integrations.';
$string['privacy:metadata:state:lastxp'] = 'The last XP total observed through the Personal XP public API.';
$string['privacy:metadata:state:timemodified'] = 'When the snapshot was last updated.';
$string['privacy:metadata:state:userid'] = 'The user represented by the snapshot.';
$string['privacy:preference:reducedmotion'] = 'Whether reduced animation is enabled for XP celebrations.';
$string['reduceanimations'] = 'Reduce animations';
$string['settingconfetti'] = 'Enable confetti';
$string['settingconfetti_desc'] = 'Allow confetti when a celebration requests it. Reduced-motion preferences always override this setting.';
$string['settingdefaulttheme'] = 'Default visual theme';
$string['settingdefaulttheme_desc'] = 'Theme used when the celebration type does not define a more specific presentation.';
$string['settingduration'] = 'Celebration duration';
$string['settingduration_desc'] = 'How long, in milliseconds, a celebration remains visible before it closes automatically.';
$string['settingexpirydays'] = 'Queue expiration';
$string['settingexpirydays_desc'] = 'Number of days a pending celebration remains eligible to be shown.';
$string['settingintensity'] = 'Animation intensity';
$string['settingintensity_desc'] = 'Controls how many decorative particles are created. It does not change the meaning of the celebration.';
$string['settinginterval'] = 'Interval between celebrations';
$string['settinginterval_desc'] = 'Minimum delay, in milliseconds, between one celebration and the next.';
$string['settingsessionlimit'] = 'Celebrations per session';
$string['settingsessionlimit_desc'] = 'Maximum number of celebrations claimed in one Moodle session.';
$string['settingsound'] = 'Enable sound';
$string['settingsound_desc'] = 'Play a short locally generated tone for celebrations. Disabled by default and never loaded from an external file.';
$string['settingxpmilestones'] = 'XP milestones';
$string['settingxpmilestones_desc'] = 'Cumulative XP values that create milestone celebrations, separated by lines, spaces, commas, or semicolons. When one XP change crosses several values, only the highest is queued.';
$string['taskexpirequeue'] = 'Expire old XP celebrations';
$string['themeachievement'] = 'Achievement';
$string['themeconfetti'] = 'Confetti';
$string['themelevelup'] = 'Level up';
$string['thememinimal'] = 'Minimal';
$string['xpcelebration:manage'] = 'Manage XP Celebration settings';
$string['xpcelebration:view'] = 'View personal XP celebrations';
$string['xpmilestonemessage'] = 'You have reached {xp} XP in {coursename}.';
$string['xpmilestonetitle'] = '{milestone} XP milestone';
