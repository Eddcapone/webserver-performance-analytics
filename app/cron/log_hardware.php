<?php

require_once __DIR__ . '/../etc/env.php';
require_once __DIR__ . '/../global_vars.php';
require_once __DIR__ . '/includes/lockHandler.php';

checkLock('/tmp/log_hardware_cron_lock', '/tmp/log_hardware.log');

// PDO connection
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Could not connect to the database $dbname :" . $e->getMessage());
}

function getTopProcesses()
{
    // Use the constant MAX_PROCESSES to define how many lines to get, adding one for the header
    $command = 'ps -eo pid,%cpu,cmd --sort=-%cpu -ww | head -n ' . (LOG_MAX_PROCESSES + 1);
    exec($command, $output);

    $processes = [];
    foreach ($output as $i => $line) {
        // Skip header line if present
        if ($i === 0 && stripos($line, 'PID') !== false) {
            continue;
        }

        // Use a regex to split the line into PID, %CPU, and CMD, handling extra spaces robustly
        if (preg_match('/^\s*(\d+)\s+([\d.]+)\s+(.+)$/', $line, $matches)) {
            $processes[] = [
                'pid'       => $matches[1],
                'cpu_usage' => (float)$matches[2],
                'command'   => $matches[3],
            ];
        }
    }

    // Sort the processes by CPU usage in descending order to ensure correct ordering
    usort($processes, function ($a, $b) {
        return $b['cpu_usage'] <=> $a['cpu_usage'];
    });

    return $processes;
}

function logTopProcesses()
{
    $topProcesses = getTopProcesses();
    $logFilePath = __DIR__ . '/../../var/log/top_processes.log'; // Ensure the directory path is correct

    // Prepare log entry with current date and time
    $logEntry = date('Y-m-d H:i:s') . " - Top CPU Processes:\n";

    // Append each process information to the log entry
    foreach ($topProcesses as $process) {
        $logEntry .= sprintf("PID: %s, CPU Usage: %s%%, Command: %s\n",
                             $process['pid'], $process['cpu_usage'], $process['command']);
    }
    $logEntry .= "\n";

    // Write to log file
    file_put_contents($logFilePath, $logEntry, FILE_APPEND);
}

// Function to get CPU usage and frequency
function getCpuMetrics()
{
    $load = sys_getloadavg();
    $cpuUsage = $load[0];

    if ($cpuUsage > LOG_THRESHOLD_LOAD_AVERAGE) {
        logTopProcesses();
    }
    $frequency = shell_exec("lscpu | grep 'MHz' | awk '{print $3}'");
    return array('cpuUsage' => $cpuUsage, 'cpuFrequency' => trim($frequency));
}

// Function to execute mpstat once and parse results for both core utilization and I/O wait
function getMoreCpuMetrics()
{
    $output = shell_exec("mpstat -P ALL 90 1");    // collect for X seconds and return average values
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

