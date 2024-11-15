<?php
// Fetch data from host_summary_log table
$sql = "SELECT *
        FROM ". $dbname .".performance_log
        ORDER BY `timestamp` DESC 
        LIMIT ". $limits["general"];
$result = $conn->query($sql);

$data = [
    "timestamps" => [],
    "threads_connected" => [],
];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data["timestamps"][] = $row["timestamp"];
        $data["threads_connected"][] = is_numeric($row["threads_connected"]) ? $row["threads_connected"] : 0;
    }
}
?>

<div class="chart-container">
    <canvas id="threads_connected"></canvas>
</div>

<script>
    (function() {
        const timestamps = <?= json_encode(array_reverse($data["timestamps"])); ?>;
        const threads_connected = <?= json_encode(array_reverse($data["threads_connected"])); ?>;
        
        createNewChart("threads_connected",threads_connected ,"orange", "", "Active connections");

        <?php include "partials/createNewChart.html"; ?>
    })();
</script>

