<?php

sleep(30);

// Database credentials
include '../etc/env.php';

// Connect to MariaDB
$conn = new mysqli($host, $user, $pass, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch relevant status variables
$sql = "SHOW GLOBAL STATUS WHERE Variable_name IN (
    'Innodb_buffer_pool_wait_free',
    'Innodb_buffer_pool_pages_data',
    'Innodb_buffer_pool_bytes_data',
    'Innodb_buffer_pool_pages_dirty',
    'Innodb_buffer_pool_bytes_dirty',
    'Innodb_buffer_pool_pages_flushed',
    'Innodb_buffer_pool_read_requests',
    'Innodb_buffer_pool_reads',
    'Innodb_data_reads',
    'Innodb_data_writes',
    'Innodb_data_fsyncs',
    'Innodb_rows_inserted',
    'Innodb_rows_updated',
    'Innodb_rows_deleted',
    'Innodb_rows_read',
    'Innodb_log_write_requests',
    'Innodb_log_writes',
    'Innodb_os_log_written',
    'Innodb_row_lock_waits',
    'Innodb_row_lock_time'
)";
$result = $conn->query($sql);

$statusValues = []; // Store status values with keys as variable names
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $statusValues[$row['Variable_name']] = $row['Value'];
    }
}

// Prepare the insert statement for logging
$insert_sql = $conn->prepare("INSERT INTO ".$dbname.".innodb_log
    (
        innodb_buffer_pool_wait_free,
        innodb_buffer_pool_pages_data,
        innodb_buffer_pool_bytes_data,
        innodb_buffer_pool_pages_dirty,
        innodb_buffer_pool_bytes_dirty,
        innodb_buffer_pool_pages_flushed,
        innodb_buffer_pool_read_requests,
        innodb_buffer_pool_reads,
        innodb_data_reads,
        innodb_data_writes,
        innodb_data_fsyncs,
        innodb_rows_inserted,
        innodb_rows_updated,
        innodb_rows_deleted,
        innodb_rows_read,
        innodb_log_write_requests,
        innodb_log_writes,
        innodb_os_log_written,
        innodb_row_lock_waits,
        innodb_row_lock_time
    )
    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
);

// Bind values to the insert statement using the associative array
$insert_sql->bind_param("iiiiiiiiiiiiiiiiiiii",
    $statusValues['Innodb_buffer_pool_wait_free'],
    $statusValues['Innodb_buffer_pool_pages_data'],
    $statusValues['Innodb_buffer_pool_bytes_data'],
    $statusValues['Innodb_buffer_pool_pages_dirty'],
    $statusValues['Innodb_buffer_pool_bytes_dirty'],
    $statusValues['Innodb_buffer_pool_pages_flushed'],
    $statusValues['Innodb_buffer_pool_read_requests'],
    $statusValues['Innodb_buffer_pool_reads'],
    $statusValues['Innodb_data_reads'],
    $statusValues['Innodb_data_writes'],
    $statusValues['Innodb_data_fsyncs'],
    $statusValues['Innodb_rows_inserted'],
    $statusValues['Innodb_rows_updated'],
    $statusValues['Innodb_rows_deleted'],
    $statusValues['Innodb_rows_read'],
    $statusValues['Innodb_log_write_requests'],
    $statusValues['Innodb_log_writes'],
    $statusValues['Innodb_os_log_written'],
    $statusValues['Innodb_row_lock_waits'],
    $statusValues['Innodb_row_lock_time']
);

// Execute the insert
$insert_sql->execute();

// Close connections
$insert_sql->close();
$conn->close();
?>

