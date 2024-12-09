<?php

sleep(40);

include "../etc/env.php";
include "../global_vars.php";

// Connect to MariaDB
$conn = new mysqli($host, $user, $pass, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch relevant status variables
$sql = "show status where variable_name = 'threads_connected'";
$result = $conn->query($sql);

$statusValues = []; // Store status values with keys as variable names
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $statusValues[$row['Variable_name']] = $row['Value'];
    }
}

// Prepare the insert statement for logging
$insert_sql = $conn->prepare("INSERT INTO ". $dbname .".performance_log
    (
        threads_connected
    )
    VALUES (?)"
);

// Bind values to the insert statement using the associative array
$insert_sql->bind_param("i",
    $statusValues['Threads_connected'],
);

const CONNECTION_ALARM_THRESHHOLD = 100;

if ($_envConnectionAlarmEnabled && $statusValues['Threads_connected'] >= CONNECTION_ALARM_THRESHHOLD)
{
    $to      = $_envEmailTo;
    $subject = 'Performance Analyse - Verbindungen';
    $message = 'Info: Es gibt aktuell mehr als 100 Verbindungen.';
    $headers = array(
        'From' => $_envEmailFrom,
        'Reply-To' => $_envEmailReplyTo,
        'X-Mailer' => 'PHP/' . phpversion()
    );

    mail($to, $subject, $message, $headers);
}


// Execute the insert
$insert_sql->execute();

// Close connections
$insert_sql->close();
$conn->close();
?>

