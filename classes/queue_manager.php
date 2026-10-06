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
 * Queue persistence and atomic claiming.
 *
 * @package    local_xpcelebration
 * @copyright  2026 Eduardo Kraus
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_xpcelebration;

use context_course;
use invalid_parameter_exception;
use local_xpcelebration\event\celebration_shown;
use stdClass;
use Throwable;

/**
 * Queue manager.
 */
final class queue_manager {
    /** @var string Pending queue item. */
    public const STATUS_PENDING = 'pending';
    /** @var string Already claimed for display. */
    public const STATUS_SHOWN = 'shown';
    /** @var string Expired before display. */
    public const STATUS_EXPIRED = 'expired';

    /**
     * Queue a celebration.
     *
     * @param int $userid User id.
     * @param int $courseid Course id.
     * @param string $type Type.
     * @param string $title Title.
     * @param string $message Message.
     * @param array $data Data/placeholders.
     * @return int Record id.
     */
    public static function queue(
        int $userid,
        int $courseid,
        string $type,
        string $title,
        string $message,
        array $data = []
    ): int {
        global $DB;

        if ($userid <= 0 || !$DB->record_exists('user', ['id' => $userid, 'deleted' => 0])) {
            throw new invalid_parameter_exception('Invalid celebration user.');
        }
        if ($courseid <= 0 || !$DB->record_exists('course', ['id' => $courseid])) {
            throw new invalid_parameter_exception('Invalid celebration course.');
        }

        $type = strtolower(trim($type));
        $type = preg_replace('/[^a-z0-9_:-]/', '', $type);
        if ($type === '') {
            throw new invalid_parameter_exception('Celebration type cannot be empty.');
        }
        $type = substr($type, 0, 64);

        $title = trim(clean_param($title, PARAM_TEXT));
        $message = trim(clean_param($message, PARAM_TEXT));
        if ($title === '' || $message === '') {
            throw new invalid_parameter_exception('Celebration title and message are required.');
        }

        $data = self::sanitise_data($data);
        $priority = isset($data['_priority']) ? max(-1000, min(1000, (int)$data['_priority'])) : self::default_priority($type);
        unset($data['_priority']);

        $now = time();
        $expirydays = (int)get_config('local_xpcelebration', 'expirydays');
        if ($expirydays <= 0) {
            $expirydays = 14;
        }
        $timeexpired = isset($data['_timeexpired']) ? (int)$data['_timeexpired'] : $now + ($expirydays * DAYSECS);
        unset($data['_timeexpired']);

        $status = ($timeexpired > 0 && $timeexpired <= $now) ? self::STATUS_EXPIRED : self::STATUS_PENDING;
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false || strlen($json) > 65535) {
            throw new invalid_parameter_exception('Celebration data is too large or invalid.');
        }

        $record = (object)[
            'userid' => $userid,
            'courseid' => $courseid,
            'type' => $type,
            'title' => \core_text::substr($title, 0, 255),
            'message' => $message,
            'datajson' => $json,
            'priority' => $priority,
            'status' => $status,
            'timecreated' => $now,
            'timeshown' => null,
            'timeexpired' => $timeexpired > 0 ? $timeexpired : null,
        ];

        return (int)$DB->insert_record('local_xpcelebration_queue', $record);
    }

    /**
     * Atomically claim pending items. Claimed items are already marked shown before they reach the browser.
     *
     * The SELECT FOR UPDATE form is valid for the supported MySQL/MariaDB/PostgreSQL targets and avoids
     * two tabs receiving the same pending record at the same time.
     *
     * @param int $userid User id.
     * @param int $courseid Course id.
     * @param int $limit Maximum number of records.
     * @return stdClass[] Claimed records.
     */
    public static function claim_pending(int $userid, int $courseid, int $limit): array {
        global $DB;

        $limit = max(0, min(20, $limit));
        if ($limit === 0) {
            return [];
        }

        self::expire_pending($userid, $courseid);
        $now = time();
        $transaction = $DB->start_delegated_transaction();

        $sql = "SELECT *
                  FROM {local_xpcelebration_queue}
                 WHERE userid = :userid
                   AND courseid = :courseid
                   AND status = :status
                   AND (timeexpired IS NULL OR timeexpired > :now)
              ORDER BY priority DESC, timecreated ASC, id ASC
                FOR UPDATE";
        $records = $DB->get_records_sql($sql, [
            'userid' => $userid,
            'courseid' => $courseid,
            'status' => self::STATUS_PENDING,
            'now' => $now,
        ]);

        if (count($records) > $limit) {
            $records = array_slice($records, 0, $limit, true);
        }

        foreach ($records as $record) {
            $record->status = self::STATUS_SHOWN;
            $record->timeshown = $now;
            $DB->update_record('local_xpcelebration_queue', $record);
        }
        $transaction->allow_commit();

        foreach ($records as $record) {
            self::trigger_shown_event($record);
        }

        return array_values($records);
    }

    /**
     * Expire pending rows whose deadline passed.
     *
     * @param int|null $userid Optional user filter.
     * @param int|null $courseid Optional course filter.
     * @return void
     */
    public static function expire_pending(?int $userid = null, ?int $courseid = null): void {
        global $DB;

        $params = [
            'pending' => self::STATUS_PENDING,
            'now' => time(),
        ];
        $select = 'status = :pending AND timeexpired IS NOT NULL AND timeexpired <= :now';
        if ($userid !== null) {
            $select .= ' AND userid = :userid';
            $params['userid'] = $userid;
        }
        if ($courseid !== null) {
            $select .= ' AND courseid = :courseid';
            $params['courseid'] = $courseid;
        }

        $DB->set_field_select('local_xpcelebration_queue', 'status', self::STATUS_EXPIRED, $select, $params);
    }

    /**
     * Default ordering by semantic importance.
     *
     * @param string $type Type.
     * @return int
     */
    private static function default_priority(string $type): int {
        $map = [
            'levelup' => 100,
            'courseprogress' => 90,
            'questcompleted' => 85,
            'milestone' => 80,
            'rewardunlocked' => 75,
            'goalcompleted' => 70,
            'streak' => 65,
            'personalrecord' => 60,
            'xpmilestone' => 55,
        ];
        return $map[$type] ?? 50;
    }

    /**
     * Sanitise integration data recursively.
     *
     * @param array $data Input.
     * @param int $depth Current nesting depth.
     * @return array
     */
    private static function sanitise_data(array $data, int $depth = 0): array {
        if ($depth > 4) {
            return [];
        }

        $clean = [];
        foreach ($data as $key => $value) {
            $key = preg_replace('/[^a-zA-Z0-9_:-]/', '', (string)$key);
            if ($key === '') {
                continue;
            }
            $key = substr($key, 0, 64);
            if (is_array($value)) {
                $clean[$key] = self::sanitise_data($value, $depth + 1);
            } else if (is_bool($value) || is_int($value) || is_float($value) || $value === null) {
                $clean[$key] = $value;
            } else if (is_string($value)) {
                $clean[$key] = \core_text::substr(clean_param($value, PARAM_TEXT), 0, 1000);
            }
        }
        return $clean;
    }

    /**
     * Trigger privacy-conscious shown event.
     *
     * @param stdClass $record Queue record.
     * @return void
     */
    private static function trigger_shown_event(stdClass $record): void {
        try {
            $context = context_course::instance((int)$record->courseid);
            $event = celebration_shown::create([
                'context' => $context,
                'objectid' => (int)$record->id,
                'userid' => (int)$record->userid,
                'other' => [
                    'type' => (string)$record->type,
                    'courseid' => (int)$record->courseid,
                ],
            ]);
            $event->trigger();
        } catch (Throwable $exception) {
            debugging('Could not trigger local_xpcelebration celebration_shown: ' . $exception->getMessage(), DEBUG_DEVELOPER);
        }
    }
}
