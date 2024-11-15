<?php

sleep(35);

include "../etc/env.php";

// Connect to MariaDB
$conn = new mysqli($host, $user, $pass, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch relevant status variables
$sql = "SHOW STATUS LIKE 'Qcache%'";
$result = $conn->query($sql);

$statusValues = []; // Store status values with keys as variable names
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $statusValues[$row['Variable_name']] = $row['Value'];
    }
}

// Prepare the insert statement for logging
$insert_sql = $conn->prepare("INSERT INTO blizz_performance_analyse_ef.qcache_log
    (
        Qcache_free_blocks,
        Qcache_free_memory,
        Qcache_hits,
        Qcache_inserts,
        Qcache_lowmem_prunes,
        Qcache_not_cached,
        Qcache_queries_in_cache,
        Qcache_total_blocks
    )
    VALUES (?,?,?,?,?,?,?,?)"
);

// Bind values to the insert statement using the associative array
$insert_sql->bind_param("iiiiiiii",
    $statusValues['Qcache_free_blocks'],
    $statusValues['Qcache_free_memory'],
    $statusValues['Qcache_hits'],
    $statusValues['Qcache_inserts'],
    $statusValues['Qcache_lowmem_prunes'],
    $statusValues['Qcache_not_cached'],
    $statusValues['Qcache_queries_in_cache'],
    $statusValues['Qcache_total_blocks'],
);

// Execute the insert
$insert_sql->execute();

// Close connections
$insert_sql->close();
$conn->close();
?>

