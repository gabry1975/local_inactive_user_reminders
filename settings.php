<?php
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {

    $settings = new admin_settingpage(
        'local_inactive_user_reminders',
        get_string('pluginname', 'local_inactive_user_reminders')
    );

    $settings->add(new admin_setting_configtext(
        'local_inactive_user_reminders/reminder_days',
        get_string('reminderdays', 'local_inactive_user_reminders'),
        get_string('reminderdays_desc', 'local_inactive_user_reminders'),
        '30,15,7,1'
    ));

    $settings->add(new admin_setting_configtext(
        'local_inactive_user_reminders/email_subject',
        get_string('emailsubject', 'local_inactive_user_reminders'),
        '',
        'Your account will be deactivated in {$days} days'
    ));

    $settings->add(new admin_setting_confightmleditor(
        'local_inactive_user_reminders/email_body',
        get_string('emailbody', 'local_inactive_user_reminders'),
        get_string('emailbody_desc', 'local_inactive_user_reminders'),
        '<p>Hello {$firstname},</p>
<p>Your account has been inactive and will be deactivated in <strong>{$days}</strong> days.</p>
<p>Please log in to keep your account active.</p>
<p>{$sitename}</p>'
    ));

    $ADMIN->add('localplugins', $settings);
}