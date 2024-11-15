<?php

// Fetch data from host_summary_log table
$sql = "SELECT *
        FROM ".$dbname.".innodb_log
        ORDER BY `timestamp` DESC 
        LIMIT ". $limits["innodb"];
$result = $conn->query($sql);

$data = [
    "timestamps" => [],
    "innodb_buffer_pool_wait_free" => [],
    "innodb_buffer_pool_pages_data" => [],
    "innodb_buffer_pool_bytes_data" => [],
    "innodb_buffer_pool_pages_dirty" => [],
    "innodb_buffer_pool_bytes_dirty" => [],
    "innodb_buffer_pool_pages_flushed" => [],
    "innodb_buffer_pool_read_requests" => [],
    "innodb_buffer_pool_reads" => [],
    "innodb_data_reads" => [],
    "innodb_data_writes" => [],
    "innodb_data_fsyncs" => [],
    "innodb_rows_inserted" => [],
    "innodb_rows_updated" => [],
    "innodb_rows_deleted" => [],
    "innodb_rows_read" => [],
    "innodb_log_write_requests" => [],
    "innodb_log_writes" => [],
    "innodb_os_log_written" => [],
    "innodb_row_lock_waits" => [],
    "innodb_row_lock_time" => [],
];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data["timestamps"][] = $row["timestamp"];
        $data["innodb_buffer_pool_wait_free"][] = is_numeric($row["innodb_buffer_pool_wait_free"]) ? $row["innodb_buffer_pool_wait_free"] : 0;
        $data["innodb_buffer_pool_pages_data"][] = is_numeric($row["innodb_buffer_pool_pages_data"]) ? $row["innodb_buffer_pool_pages_data"] : 0;
        $data["innodb_buffer_pool_bytes_data"][] = is_numeric($row["innodb_buffer_pool_bytes_data"]) ? $row["innodb_buffer_pool_bytes_data"] : 0;
        $data["innodb_buffer_pool_pages_dirty"][] = is_numeric($row["innodb_buffer_pool_pages_dirty"]) ? $row["innodb_buffer_pool_pages_dirty"] : 0;
        $data["innodb_buffer_pool_bytes_dirty"][] = is_numeric($row["innodb_buffer_pool_bytes_dirty"]) ? $row["innodb_buffer_pool_bytes_dirty"] : 0;
        $data["innodb_buffer_pool_pages_flushed"][] = is_numeric($row["innodb_buffer_pool_pages_flushed"]) ? $row["innodb_buffer_pool_pages_flushed"] : 0;
        $data["innodb_buffer_pool_read_requests"][] = is_numeric($row["innodb_buffer_pool_read_requests"]) ? $row["innodb_buffer_pool_read_requests"] : 0;
        $data["innodb_buffer_pool_reads"][] = is_numeric($row["innodb_buffer_pool_reads"]) ? $row["innodb_buffer_pool_reads"] : 0;
        $data["innodb_data_reads"][] = is_numeric($row["innodb_data_reads"]) ? $row["innodb_data_reads"] : 0;
        $data["innodb_data_writes"][] = is_numeric($row["innodb_data_writes"]) ? $row["innodb_data_writes"] : 0;
        $data["innodb_data_fsyncs"][] = is_numeric($row["innodb_data_fsyncs"]) ? $row["innodb_data_fsyncs"] : 0;
        $data["innodb_rows_inserted"][] = is_numeric($row["innodb_rows_inserted"]) ? $row["innodb_rows_inserted"] : 0;
        $data["innodb_rows_updated"][] = is_numeric($row["innodb_rows_updated"]) ? $row["innodb_rows_updated"] : 0;
        $data["innodb_rows_deleted"][] = is_numeric($row["innodb_rows_deleted"]) ? $row["innodb_rows_deleted"] : 0;
        $data["innodb_rows_read"][] = is_numeric($row["innodb_rows_read"]) ? $row["innodb_rows_read"] : 0;
        $data["innodb_log_write_requests"][] = is_numeric($row["innodb_log_write_requests"]) ? $row["innodb_log_write_requests"] : 0;
        $data["innodb_log_writes"][] = is_numeric($row["innodb_log_writes"]) ? $row["innodb_log_writes"] : 0;
        $data["innodb_os_log_written"][] = is_numeric($row["innodb_os_log_written"]) ? $row["innodb_os_log_written"] : 0;
        $data["innodb_row_lock_waits"][] = is_numeric($row["innodb_row_lock_waits"]) ? $row["innodb_row_lock_waits"] : 0;
        $data["innodb_row_lock_time"][] = is_numeric($row["innodb_row_lock_time"]) ? $row["innodb_row_lock_time"] : 0;
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

<h2 class="title">MariaDB - InnoDB Summary Graphs</h2>

<div class="chart-container size-4">
    <canvas id="innodb_buffer_pool_pages_data"></canvas>
    <canvas id="innodb_buffer_pool_bytes_data"></canvas>
    <canvas id="innodb_buffer_pool_pages_dirty"></canvas>
    <canvas id="innodb_buffer_pool_bytes_dirty"></canvas>
</div>

<div class="chart-container size-3">
    <canvas id="innodb_buffer_pool_pages_flushed"></canvas>
    <canvas id="innodb_buffer_pool_read_requests"></canvas>
    <canvas id="innodb_buffer_pool_reads"></canvas>
</div>

<div class="chart-container size-2">
    <canvas id="innodb_data_reads"></canvas>
    <canvas id="innodb_data_writes"></canvas>
</div>

<div class="chart-container size-4">
    <canvas id="innodb_rows_inserted"></canvas>
    <canvas id="innodb_rows_updated"></canvas>
    <canvas id="innodb_rows_deleted"></canvas>
    <canvas id="innodb_rows_read"></canvas>
</div>

<div class="chart-container size-3">
    <canvas id="innodb_log_write_requests"></canvas>
    <canvas id="innodb_log_writes"></canvas>
    <canvas id="innodb_os_log_written"></canvas>
</div>

<div class="chart-container size-2">
    <canvas id="innodb_row_lock_waits"></canvas>
    <canvas id="innodb_row_lock_time"></canvas>
</div>

<div class="chart-container size-2">
    <canvas id="innodb_data_fsyncs"></canvas>
    <canvas id="innodb_buffer_pool_wait_free"></canvas>
</div>

<script>
    (function() {
        const timestamps = <?php echo json_encode(array_reverse($data["timestamps"])); ?>;
        const innodb_buffer_pool_wait_free = <?php echo json_encode(array_reverse($data["innodb_buffer_pool_wait_free"])); ?>;
        const innodb_buffer_pool_pages_data = <?php echo json_encode(array_reverse($data["innodb_buffer_pool_pages_data"])); ?>;
        const innodb_buffer_pool_bytes_data = <?php echo json_encode(array_reverse($data["innodb_buffer_pool_bytes_data"])); ?>;
        const innodb_buffer_pool_pages_dirty = <?php echo json_encode(array_reverse($data["innodb_buffer_pool_pages_dirty"])); ?>;
        const innodb_buffer_pool_bytes_dirty = <?php echo json_encode(array_reverse($data["innodb_buffer_pool_bytes_dirty"])); ?>;
        const innodb_buffer_pool_pages_flushed = <?php echo json_encode(array_reverse($data["innodb_buffer_pool_pages_flushed"])); ?>;
        const innodb_buffer_pool_read_requests = <?php echo json_encode(array_reverse($data["innodb_buffer_pool_read_requests"])); ?>;
        const innodb_buffer_pool_reads = <?php echo json_encode(array_reverse($data["innodb_buffer_pool_reads"])); ?>;
        const innodb_data_reads = <?php echo json_encode(array_reverse($data["innodb_data_reads"])); ?>;
        const innodb_data_writes = <?php echo json_encode(array_reverse($data["innodb_data_writes"])); ?>;
        const innodb_data_fsyncs = <?php echo json_encode(array_reverse($data["innodb_data_fsyncs"])); ?>;
        const innodb_rows_inserted = <?php echo json_encode(array_reverse($data["innodb_rows_inserted"])); ?>;
        const innodb_rows_updated = <?php echo json_encode(array_reverse($data["innodb_rows_updated"])); ?>;
        const innodb_rows_deleted = <?php echo json_encode(array_reverse($data["innodb_rows_deleted"])); ?>;
        const innodb_rows_read = <?php echo json_encode(array_reverse($data["innodb_rows_read"])); ?>;
        const innodb_log_write_requests = <?php echo json_encode(array_reverse($data["innodb_log_write_requests"])); ?>;
        const innodb_log_writes = <?php echo json_encode(array_reverse($data["innodb_log_writes"])); ?>;
        const innodb_os_log_written = <?php echo json_encode(array_reverse($data["innodb_os_log_written"])); ?>;
        const innodb_row_lock_waits = <?php echo json_encode(array_reverse($data["innodb_row_lock_waits"])); ?>;
        const innodb_row_lock_time = <?php echo json_encode(array_reverse($data["innodb_row_lock_time"])); ?>;
        
        createNewChart("innodb_buffer_pool_wait_free", innodb_buffer_pool_wait_free, "blue", "", "Times that a free page was not immediately available from the buffer pool.");
        createNewChart("innodb_buffer_pool_pages_data", innodb_buffer_pool_pages_data, "red", "", "Number of pages containing data (dirty or clean).");
        createNewChart("innodb_buffer_pool_bytes_data", innodb_buffer_pool_bytes_data, "orange", "", "Amount of data in the buffer pool, in bytes.");
        createNewChart("innodb_buffer_pool_pages_dirty", innodb_buffer_pool_pages_dirty, "orange", "", "Current number of dirty pages in the buffer pool.");
        createNewChart("innodb_buffer_pool_bytes_dirty", innodb_buffer_pool_bytes_dirty, "red", "", "Amount of dirty data in the buffer pool, in bytes.");
        createNewChart("innodb_buffer_pool_pages_flushed", innodb_buffer_pool_pages_flushed, "yellow", "","Number of buffer pool pages flushed.");
        createNewChart("innodb_buffer_pool_read_requests", innodb_buffer_pool_read_requests, "yellow", "","Number of logical read requests.");
        createNewChart("innodb_buffer_pool_reads", innodb_buffer_pool_reads, "red", "", "Number of logical reads that InnoDB could not satisfy from the buffer pool,and had to read directly from disk.");
        createNewChart("innodb_data_reads", innodb_data_reads, "red", "","The total number of data reads.");
        createNewChart("innodb_data_writes", innodb_data_writes, "red", "","The total number of data writes.");
        createNewChart("innodb_data_fsyncs", innodb_data_fsyncs, "red", "","Number of fsync() operations.");
        createNewChart("innodb_rows_inserted", innodb_rows_inserted, "green", "","Number of rows inserted into InnoDB tables.");
        createNewChart("innodb_rows_updated", innodb_rows_updated, "blue", "","Number of rows updated in InnoDB tables.");
        createNewChart("innodb_rows_deleted", innodb_rows_deleted, "red", "","Number of rows deleted from InnoDB tables.");
        createNewChart("innodb_rows_read", innodb_rows_read, "orange", "","Number of rows read from InnoDB tables.");
        createNewChart("innodb_log_write_requests", innodb_log_write_requests, "orange", "","Number of log write requests.");
        createNewChart("innodb_log_writes", innodb_log_writes, "orange", "","Number of physical writes to the log file.");
        createNewChart("innodb_os_log_written", innodb_os_log_written, "orange", "","Amount of log data written in bytes.");
        createNewChart("innodb_row_lock_waits", innodb_row_lock_waits, "orange", "","Number of times a row lock had to be waited for.");
        createNewChart("innodb_row_lock_time", innodb_row_lock_time, "orange", "","Total time spent in acquiring row locks, measured in milliseconds.");

        <?php include "partials/createNewChart.html"; ?>
    })();
</script>
