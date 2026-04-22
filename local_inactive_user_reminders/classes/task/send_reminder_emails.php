<?php
namespace local_inactive_user_reminders\task;

defined('MOODLE_INTERNAL') || die();

class send_reminder_emails extends \core\task\scheduled_task {

    public function get_name() {
        return 'Send inactive user reminder emails';
    }

    public function execute() {
        global $DB, $SITE;

        // Giorni di inattività dal plugin ufficiale
        $cleanupdays = (int) get_config(
            'tool_inactive_user_cleanup',
            'daysofinactivity'
        );

        if ($cleanupdays <= 0) {
            return;
        }

        $daysconfig = get_config('local_inactive_user_reminders', 'reminder_days');
        if (empty($daysconfig)) {
            return;
        }

        $thresholds = array_map('intval', explode(',', $daysconfig));
        $now = time();

        foreach ($thresholds as $days) {

            $from = $now - (($cleanupdays - $days + 1) * DAYSECS);
            $to   = $now - (($cleanupdays - $days) * DAYSECS);

            $sql = "
                SELECT u.*
                FROM {user} u
                WHERE u.deleted = 0
                  AND u.suspended = 0
                  AND u.lastaccess BETWEEN :from AND :to
            ";

            $users = $DB->get_records_sql($sql, [
                'from' => $from,
                'to'   => $to
            ]);

            foreach ($users as $user) {

                if ($DB->record_exists('local_iur_log', [
                    'userid' => $user->id,
                    'daysbefore' => $days
                ])) {
                    continue;
                }

                $subject = get_config(
                    'local_inactive_user_reminders',
                    'email_subject'
                );
                $body = get_config(
                    'local_inactive_user_reminders',
                    'email_body'
                );

                $replacements = [
                    '{$firstname}' => $user->firstname,
                    '{$lastname}'  => $user->lastname,
                    '{$email}'     => $user->email,
                    '{$days}'      => $days,
                    '{$sitename}'  => format_string($SITE->fullname),
                ];

                $subject = str_replace(
                    array_keys($replacements),
                    array_values($replacements),
                    $subject
                );

                $body = str_replace(
                    array_keys($replacements),
                    array_values($replacements),
                    $body
                );

                email_to_user(
                    $user,
                    \core_user::get_support_user(),
                    $subject,
                    html_to_text($body),
                    $body
                );

                $DB->insert_record('local_iur_log', [
                    'userid' => $user->id,
                    'daysbefore' => $days,
                    'timecreated' => $now
                ]);
            }
        }

        // Integrazione con Inactive user CleanUp:
        // se l'utente supera la soglia di inattività, lo eliminiamo.
        $cutoff = $now - ($cleanupdays * DAYSECS);
        $usersfordeletion = $DB->get_records_sql(
            "SELECT u.*
               FROM {user} u
              WHERE u.deleted = 0
                AND u.suspended = 0
                AND u.lastaccess > 0
                AND u.lastaccess <= :cutoff",
            ['cutoff' => $cutoff]
        );

        foreach ($usersfordeletion as $candidate) {
            if (is_siteadmin($candidate->id)) {
                continue;
            }

            \delete_user($candidate);
        }
    }
}