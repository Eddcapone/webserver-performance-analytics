<?php
// Fetch data from host_summary_log table
$data = getData($conn, $dbname, $limits["hardware"]);
$avgData = getAverageData($conn, $dbname, "all");
$avgData24H = getAverageData($conn, $dbname, "24H");

// Store Core Utilisation Data in Array
$coreDataAll = [];
$coreData = [];
$coreUtilRaw = $data["core_utilization"];

foreach($coreUtilRaw as $item) {
    $_data = explode(",", $item);
    $coreDataAll[] = $_data;

    $cpu = 0;
    foreach($_data as $record) {
        $coreData[$cpu++][] = $record;
    }
}
?>

<style>
    body {
        background: white;
    }
    .title {
        color: black;
        text-align: left;
        font-size: 40px;
    }

    h2 {
        text-align: center;
        text-decoration: underline;
        color: black;
        font-size: 36px;
    }
    table, th, td {
        border: 1px solid;
        width: 250px;
    }
    table.average-metrics td:first-child {
        font-weight: 700;
    }
    table.average-metrics td:nth-child(2) {
        text-align: right;
    }
    table.average-metrics td:nth-child(3) {
        text-align: right;
    }
    .flex-center {
        display: flex;
        justify-content: center;
    }
    #average-metrics-container table {
        padding: 0 10px;
        border: none;
    }
</style>

<h1 class="title">Hardware Performance Graphs</h2>

<h2>Average Values:</h2>

<div id="average-metrics-container" class="flex-center">
    <div>
        <h4 class="flex-center">All time</h4>
        <table class="average-metrics">
            <tbody>
                <?php loadTableData($avgData); ?>
            </tbody>
        </table>
    </div>
    <div>
        <h4 class="flex-center">Last 24 Hours</h4>
        <table class="average-metrics">
            <tbody>
                <?php loadTableData($avgData24H); ?>
            </tbody>
        </table>
    </div>
</div>

<h2>CPU:</h2>

<div class="chart-container">
    <canvas id="cpu_usage"></canvas>
</div> 

<div class="chart-container">
    <canvas id="real_cpu_usage"></canvas>
</div> 

<div class="chart-container">
    <canvas id="context_switches"></canvas>
</div> 

<div class="chart-container size-2">
    <canvas id="steal"></canvas>
    <canvas id="cpu_frequency"></canvas>
</div> 

<div class="chart-container size-2">
    <canvas id="idle"></canvas>
    <canvas id="io_wait"></canvas>
</div>   

<?php
    $text = [];
    createChartContainer($coreDataAll, 0, 4);
?>

<h2>RAM / Disk:</h2>
<div class="chart-container size-2">
    <canvas id="memory_usage"></canvas>
    <canvas id="disk_usage"></canvas>
</div>

<h2>Network Traffic:</h2>
<div class="chart-container size-2">
    <canvas id="tx_rate"></canvas>
    <canvas id="rx_rate"></canvas>
</div>

<script>
    (function() {

        const timestamps = <?php echo json_encode(array_reverse($data["timestamps"])); ?>;

        <?php
            $i = 0;
            foreach($coreDataAll[0] as $record) {
                
                    // Skip first 
                    if ($i == 0) {
                        $i++;
                        continue;
                    }
                
                $dataset = json_encode(array_reverse($coreData[$i]));
                echo "const core_$i = ". $dataset . ";" . PHP_EOL;
                echo "createNewChart('core_$i',core_$i ,'blue', '');" . PHP_EOL;
                $i++;
            }
        ?>

        <?php
            // Calc real CPU Usage
            $realCpuUsage = [];

            foreach($data["idle"] as $record) {
                $realCpuUsage[] = 100 - $record;
            }
        ?>

        const cpu_usage = <?php echo json_encode(array_reverse($data["cpu_usage"])); ?>;
        const cpu_frequency = <?php echo json_encode(array_reverse($data["cpu_frequency"])); ?>;
        const idle = <?php echo json_encode(array_reverse($data["idle"])); ?>;
        const io_wait = <?php echo json_encode(array_reverse($data["io_wait"])); ?>;
        const steal = <?php echo json_encode(array_reverse($data["steal"])); ?>;
        const realCpuUsage = <?php echo json_encode(array_reverse($realCpuUsage)); ?>;
        const memory_usage = <?php echo json_encode(array_reverse($data["memory_usage"])); ?>;
        const disk_usage = <?php echo json_encode(array_reverse($data["disk_usage"])); ?>;
        const tx_rate = <?php echo json_encode(array_reverse($data["tx_rate"])); ?>;
        const rx_rate = <?php echo json_encode(array_reverse($data["rx_rate"])); ?>;
        const context_switches = <?php echo json_encode(array_reverse($data["context_switches"])); ?>;
        
        createNewChart("real_cpu_usage",realCpuUsage ,"blue", "CPU Usage");
        createNewChart("cpu_usage",cpu_usage ,"blue", "Load Average");
        createNewChart("cpu_frequency",cpu_frequency ,"purple", "");
        createNewChart("idle", idle, "purple", "", "Percentage of time the CPU was idle and not waiting for I/O.");
        createNewChart("io_wait", io_wait, "purple", "", "Percentage of time the CPU was waiting for I/O operations to complete.");
        createNewChart("steal", steal, "purple", "", "A high value can indicate that the physical CPU is overbooked other virtual machines are 'stealing' CPU time. Steal time indicates the percentage of time a virtual CPU waits for a real CPU while the hypervisor is servicing another virtual processor. High steal time can contribute to a high number of context switches, especially if processes frequently wait for CPU time.");
        createNewChart("memory_usage", memory_usage, "orange", "", "Total RAM used.", "RAM");
        createNewChart("disk_usage", disk_usage, "orange", "", "Percentage of disk space used.");
        createNewChart("tx_rate", tx_rate, "blue", "", "Transmission rate in MB/s second.");
        createNewChart("rx_rate", rx_rate, "limegreen", "", "Reception rate in MB/s second.");
        createNewChart("context_switches", context_switches, "blue", "", "In order to run several programs or threads in parallel the operating system must regularly save the \"context\" of a process and load the context of a different process. This exchange process is referred to as the \"change of context\".");

        <?php include "partials/createNewChart.html"; ?>
    })();
</script>

<?php include "connections.php";  // view/templates/graphs/connections.php würde auch funktionieren ?>

<?php
function getAverageData($conn, $dbname, $mode="all")
{
    $sql = "SELECT AVG(cpu_frequency) as cpu_frequency,
                   AVG(cpu_usage) as cpu_usage, 
                   AVG(idle) as idle,
                   AVG(context_switches) as context_switches,
                   AVG(io_wait) as io_wait,
                   AVG(steal) as steal,
                   AVG(memory_usage) as memory_usage,
                   AVG(disk_usage) as disk_usage,
                   AVG(rx_rate) as rx_rate,
                   AVG(tx_rate) as tx_rate
            FROM $dbname.hardware_log"
    ;
    if ($mode == "24H") {
        $sql .= " WHERE timestamp >= NOW() - INTERVAL 24 HOUR;";
    }

    $result = $conn->query($sql);
    $data = [];

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $data["cpu_frequency"] = is_numeric($row["cpu_frequency"]) ? $row["cpu_frequency"] : 0;
            $data["cpu_load"] = is_numeric($row["cpu_usage"]) ? $row["cpu_usage"] : 0;
            $data["cpu_usage"] = 100 - $row["idle"];
            $data["idle"] = is_numeric($row["idle"]) ? $row["idle"] : 0;
            $data["context_switches"] = is_numeric($row["context_switches"]) ? $row["context_switches"] : 0;
            $data["io_wait"] = is_numeric($row["io_wait"]) ? $row["io_wait"] : 0;
            $data["steal"] = is_numeric($row["steal"]) ? $row["steal"] : 0;
            $data["memory_usage"] = is_numeric($row["memory_usage"]) ? $row["memory_usage"] : 0;
            $data["disk_usage"] = is_numeric($row["disk_usage"]) ? $row["disk_usage"] : 0;
            $data["tx_rate"] = is_numeric($row["tx_rate"]) ? $row["tx_rate"] : 0;
            $data["rx_rate"] = is_numeric($row["rx_rate"]) ? $row["rx_rate"] : 0;
        }
    }

    return $data;
}

function getData($conn, $dbname, $limit)
{
    $sql = "SELECT *
        FROM ". $dbname .".hardware_log
        ORDER BY `timestamp` DESC 
        LIMIT ". $limit;
    
    $result = $conn->query($sql);

    $data = [
        "timestamps" => [],
        "cpu_usage" => [],
        "cpu_frequency" => [],
        "idle" => [],
        "io_wait" => [],
        "steal" => [],
        "memory_usage" => [],
        "disk_usage" => [],
        "tx_rate" => [],
        "rx_rate" => [],
        "core_utilization" => [],
        "context_switches" => [],
    ];

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $data["timestamps"][] = $row["timestamp"];
            $data["cpu_usage"][] = is_numeric($row["cpu_usage"]) ? $row["cpu_usage"] : 0;
            $data["cpu_frequency"][] = is_numeric($row["cpu_frequency"]) ? $row["cpu_frequency"] : 0;
            $data["idle"][] = is_numeric($row["idle"]) ? $row["idle"] : 0;
            $data["io_wait"][] = is_numeric($row["io_wait"]) ? $row["io_wait"] : 0;
            $data["steal"][] = is_numeric($row["steal"]) ? $row["steal"] : 0;
            $data["memory_usage"][] = is_numeric($row["memory_usage"]) ? $row["memory_usage"] : 0;
            $data["disk_usage"][] = is_numeric($row["disk_usage"]) ? $row["disk_usage"] : 0;
            $data["tx_rate"][] = is_numeric($row["tx_rate"]) ? $row["tx_rate"] : 0;
            $data["rx_rate"][] = is_numeric($row["rx_rate"]) ? $row["rx_rate"] : 0;
            $data["core_utilization"][] = $row["core_utilization"];
            $data["context_switches"][] = is_numeric($row["context_switches"]) ? $row["context_switches"] : 0;
        }
    }

    return $data;
}

function createChartContainer($coreDataAll, $carriedIndex=0, $maxChartsPerContainer=4)
{
    $i = 1;

    echo  PHP_EOL . "<div class='chart-container size-4'>" . PHP_EOL;
    $maxSize = count($coreDataAll[0]);

    foreach($coreDataAll[0] as $cpuData) {
        
        // Skip first 
        if ($carriedIndex == 0) {
            $carriedIndex++;
            continue;
        }
        
        echo "<canvas id='core_". $carriedIndex++ ."'></canvas>"  . PHP_EOL;

        if ($carriedIndex >= $maxSize) { break; }

        if ($i == $maxChartsPerContainer) {
            echo "</div>"  . PHP_EOL  . PHP_EOL;
            return createChartContainer($coreDataAll, $carriedIndex, $maxChartsPerContainer);
        }

        $i++;
    }
    echo "</div>"  . PHP_EOL  . PHP_EOL;
}

function loadTableData($data)
{
    foreach ($data as $key => $value) {
        $v = number_format((float) $value, 2, ".", "");

        echo "<tr>";
        echo "  <td>$key</td>";
        echo "  <td>$v</td>";
        echo "  <td>". getUnit($key) ."</td>";
        echo "</tr>";
    }
}

function getUnit($key)
{
    $unit = "";
    switch($key) {
        case "cpu_usage":
        case "idle":
        case "io_wait":
        case "steal":
            $unit = "%";
            break;
        case "cpu_frequency":
            $unit = "MHz";
            break;
        case "memory_usage":
            $unit = "MB";
            break;
        case "disk_usage":
            $unit = "GB";
            break;
        case "tx_rate":
        case "rx_rate":
            $unit = "MB/s";
            break;
    }
    return $unit;
}

?>
