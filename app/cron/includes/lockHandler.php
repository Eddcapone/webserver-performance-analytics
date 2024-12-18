<?php

function checkLock($lock_file, $log_file)
{
    $reboot_record = '/tmp/last_reboot_time.record';

    $last_recorded_reboot_time = @file_get_contents($reboot_record);
    $current_reboot_time = trim(shell_exec('uptime -s'));

    if ($current_reboot_time != $last_recorded_reboot_time) {
        file_put_contents($reboot_record, $current_reboot_time);
        if (file_exists($lock_file)) {
            unlink($lock_file);
        }
    }

    if (!file_exists($lock_file)) {
        file_put_contents($lock_file, "running");
        register_shutdown_function(function() use ($lock_file) {
            unlink($lock_file);
        });
        return true;
    } else {
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - The previous script is still running.\n", FILE_APPEND);
        exit("The previous script is still running.");
    }
}
?>
