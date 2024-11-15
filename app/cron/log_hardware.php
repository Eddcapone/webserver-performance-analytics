<?php

sleep(10);

checkLock();

include "../etc/env.php";

// PDO connection
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Could not connect to the database $dbname :" . $e->getMessage());
}

// Function to get CPU usage and frequency
function getCpuMetrics()
{
    $load = sys_getloadavg();
    $cpuUsage = $load[0];
    $frequency = shell_exec("lscpu | grep 'MHz' | awk '{print $3}'");
    return array('cpuUsage' => $cpuUsage, 'cpuFrequency' => trim($frequency));
}

// Function to execute mpstat once and parse results for both core utilization and I/O wait
function getMoreCpuMetrics()
{
    $output = shell_exec("mpstat -P ALL 100 1");    // collect for X seconds and return average values
    $lines = explode("\n", $output);
    $coreUtilizations = [];
    $steal = 0;
    $idle = 0;

    foreach ($lines as $line) {

        if (empty($line)) continue;

        if (preg_match('/^Average:\s+all/', $line)) {
            $parts = preg_split('/\s+/', $line);
            $ioWait = (float) $parts[5]; // Assuming column 5 is the iowait
            $steal = (float) $parts[8];
            $idle = (float) $parts[11];
        } elseif (preg_match('/^Average:/', $line)
              && !preg_match('/^Average:\s+all/', $line)
              && !preg_match('/^Average:\s+CPU/', $line)
        ) {
            $parts = preg_split('/\s+/', $line);
            $coreUtilizations[] = $parts[2];
        }
    }

    return [
        'ioWait' => $ioWait,
        'steal' => $steal,
        'idle' => $idle,
        'coreUtilizations' => $coreUtilizations,
    ];
}

// Function to get context switches
function getContextSwitches()
{
    $vmstat = shell_exec("vmstat 1 2"); // Running vmstat twice to skip the first set of average data
    preg_match_all("/\d+\s+\d+\s+\d+\s+\d+\s+\d+\s+\d+\s+\d+\s+\d+\s+(\d+)/", $vmstat, $matches);
    return end($matches[1]); // Get the last captured value
}

// Function to get memory usage
function getMemoryUsage()
{
    $memInfo = file_get_contents("/proc/meminfo");
    preg_match_all('/(\w+):\s+(\d+)/', $memInfo, $matches);
    $memInfo = array_combine($matches[1], $matches[2]);
    $usedMemory = ($memInfo['MemTotal'] - $memInfo['MemFree'] - $memInfo['Buffers'] - $memInfo['Cached']) / 1024;
    return $usedMemory; // Return in MB
}

// Function to get disk usage
function getDiskUsage()
{
    $diskTotal = disk_total_space("/");
    $diskFree = disk_free_space("/");
    $diskUsed = $diskTotal - $diskFree;
    return ($diskUsed / $diskTotal) * 100; // Return as percentage
}

// Function to get network throughput in MBps
function getNetworkThroughput()
{
    $rx1 = file_get_contents("/sys/class/net/eth0/statistics/rx_bytes");
    $tx1 = file_get_contents("/sys/class/net/eth0/statistics/tx_bytes");
    sleep(1);
    $rx2 = file_get_contents("/sys/class/net/eth0/statistics/rx_bytes");
    $tx2 = file_get_contents("/sys/class/net/eth0/statistics/tx_bytes");

    $rxRateMBps = ($rx2 - $rx1) / 1048576; // in MBps
    $txRateMBps = ($tx2 - $tx1) / 1048576; // in MBps
    return array('rxRate' => $rxRateMBps, 'txRate' => $txRateMBps);
}

// Function to get CPU temperature (if available)
function getCpuTemperature()
{
    $temp = shell_exec("cat /sys/class/thermal/thermal_zone0/temp");
    return $temp / 1000; // Convert from millidegree to degree Celsius
}

// Capture all metrics
$metrics = getCpuMetrics();
$moreCpuMetrics = getMoreCpuMetrics();
$coreUtilization = implode(',', $moreCpuMetrics["coreUtilizations"]);
$io_wait = $moreCpuMetrics["ioWait"];
$idle = $moreCpuMetrics["idle"];
$steal = $moreCpuMetrics["steal"];
$contextSwitches = getContextSwitches();
$memoryUsage = getMemoryUsage();
$diskUsage = getDiskUsage();
$network = getNetworkThroughput();
$cpuTemperature = getCpuTemperature();

// SQL to insert log data
$sql = "INSERT INTO hardware_log (cpu_usage, cpu_frequency, idle, io_wait, steal, core_utilization, context_switches, memory_usage, disk_usage, rx_rate, tx_rate, cpu_temperature, timestamp)
                         VALUES (:cpu_usage,:cpu_frequency,:idle,:io_wait,:steal,:core_utilization,:context_switches,:memory_usage,:disk_usage,:rx_rate,:tx_rate,:cpu_temperature, NOW())";
$stmt = $pdo->prepare($sql);

// Bind values and execute
$stmt->bindValue(':cpu_usage', $metrics['cpuUsage']);
$stmt->bindValue(':cpu_frequency', $metrics['cpuFrequency']);
$stmt->bindValue(':idle', $idle);
$stmt->bindValue(':io_wait', $io_wait);
$stmt->bindValue(':steal', $steal);
$stmt->bindValue(':core_utilization', $coreUtilization);
$stmt->bindValue(':context_switches', $contextSwitches);
$stmt->bindValue(':memory_usage', $memoryUsage);
$stmt->bindValue(':disk_usage', $diskUsage);
$stmt->bindValue(':rx_rate', $network['rxRate']);
$stmt->bindValue(':tx_rate', $network['txRate']);
$stmt->bindValue(':cpu_temperature', $cpuTemperature);
$stmt->execute();

echo "Logged system metrics successfully.";


function checkLock()
{
    $lock_file = '/tmp/log_hardware_cron_lock';
    $log_file = '/tmp/log_hardware.log'; // Define the path to your log file
    
    // Check if the lock file exists
    if (file_exists($lock_file)) {
        // Log the message to the specified log file
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - The previous script is still running.\n", FILE_APPEND);
        exit("The previous script is still running"); // Exit if the previous script is still running
    }
    
    // Create a lock file
    file_put_contents($lock_file, "running");
    
    // Ensure the lock file is deleted when the script exits
    register_shutdown_function(function() use ($lock_file) {
        unlink($lock_file);
    });
}

?>

