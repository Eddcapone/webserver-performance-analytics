<?php
// Fetch data from host_summary_log table
$sql = "SELECT *
        FROM blizz_performance_analyse_ef.qcache_log
        ORDER BY `timestamp` DESC 
        LIMIT ". $limits["qcache"];
$result = $conn->query($sql);

$data = [
    "timestamps" => [],
    "Qcache_free_blocks" => [],
    "Qcache_free_memory" => [],
    "Qcache_hits" => [],
    "Qcache_inserts" => [],
    "Qcache_lowmem_prunes" => [],
    "Qcache_not_cached" => [],
    "Qcache_queries_in_cache" => [],
    "Qcache_total_blocks" => [],
];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data["timestamps"][] = $row["timestamp"];
        $data["Qcache_free_blocks"][] = is_numeric($row["Qcache_free_blocks"]) ? $row["Qcache_free_blocks"] : 0;
        $data["Qcache_free_memory"][] = is_numeric($row["Qcache_free_memory"]) ? $row["Qcache_free_memory"] : 0;
        $data["Qcache_hits"][] = is_numeric($row["Qcache_hits"]) ? $row["Qcache_hits"] : 0;
        $data["Qcache_inserts"][] = is_numeric($row["Qcache_inserts"]) ? $row["Qcache_inserts"] : 0;
        $data["Qcache_lowmem_prunes"][] = is_numeric($row["Qcache_lowmem_prunes"]) ? $row["Qcache_lowmem_prunes"] : 0;
        $data["Qcache_not_cached"][] = is_numeric($row["Qcache_not_cached"]) ? $row["Qcache_not_cached"] : 0;
        $data["Qcache_queries_in_cache"][] = is_numeric($row["Qcache_queries_in_cache"]) ? $row["Qcache_queries_in_cache"] : 0;
        $data["Qcache_total_blocks"][] = is_numeric($row["Qcache_total_blocks"]) ? $row["Qcache_total_blocks"] : 0;
    }
}
?>

<style>
    body {
        background: white;
    }
    .title {
        color: black;
        text-align: center;
    }
</style>

<h2 class="title">MariaDB - qCache Summary Graphs</h2>

<div class="chart-container">
    <canvas id="Qcache_hits"></canvas>
</div>

<div class="chart-container size-2">
    <canvas id="Qcache_free_blocks"></canvas>
    <canvas id="Qcache_free_memory"></canvas>
</div>


<div class="chart-container size-3">
    <canvas id="Qcache_inserts"></canvas>
    <canvas id="Qcache_lowmem_prunes"></canvas>
</div>

<div class="chart-container size-3">
    <canvas id="Qcache_not_cached"></canvas>
    <canvas id="Qcache_queries_in_cache"></canvas>
    <canvas id="Qcache_total_blocks"></canvas>
</div>

<script>
    (function() {
        const timestamps = <?php echo json_encode(array_reverse($data["timestamps"])); ?>;
        const Qcache_free_blocks = <?php echo json_encode(array_reverse($data["Qcache_free_blocks"])); ?>;
        const Qcache_free_memory = <?php echo json_encode(array_reverse($data["Qcache_free_memory"])); ?>;
        const Qcache_hits = <?php echo json_encode(array_reverse($data["Qcache_hits"])); ?>;
        const Qcache_inserts = <?php echo json_encode(array_reverse($data["Qcache_inserts"])); ?>;
        const Qcache_lowmem_prunes = <?php echo json_encode(array_reverse($data["Qcache_lowmem_prunes"])); ?>;
        const Qcache_not_cached = <?php echo json_encode(array_reverse($data["Qcache_not_cached"])); ?>;
        const Qcache_queries_in_cache = <?php echo json_encode(array_reverse($data["Qcache_queries_in_cache"])); ?>;
        const Qcache_total_blocks = <?php echo json_encode(array_reverse($data["Qcache_total_blocks"])); ?>;

        
        createNewChart("Qcache_free_blocks", Qcache_free_blocks,"green");
        createNewChart("Qcache_free_memory", Qcache_free_memory,"green");
        createNewChart("Qcache_hits", Qcache_hits,"limegreen");
        createNewChart("Qcache_inserts", Qcache_inserts,"orange");
        createNewChart("Qcache_lowmem_prunes", Qcache_lowmem_prunes,"red");
        createNewChart("Qcache_not_cached", Qcache_not_cached, "red");
        createNewChart("Qcache_queries_in_cache", Qcache_queries_in_cache, "limegreen");
        createNewChart("Qcache_total_blocks", Qcache_total_blocks, "yellow");

        <?php include "partials/createNewChart.html"; ?>
    })();
</script>
