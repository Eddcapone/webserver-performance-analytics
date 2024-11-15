<?php
include "app/etc/env.php";
include "app/global_vars.php";

// Connect to MariaDB
$conn = new mysqli($host, $user, $pass, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Use Chart.js v3.9.1 and chartjs-adapter-moment for compatibility -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1"></script>
    <script src="https://cdn.jsdelivr.net/npm/moment@2.29.4"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-moment@1.0.0"></script>

    <title>Blizz-z | Performance Analyse</title>
    <style>
        /* Style for the fixed header */
        #header {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            background-color: #333;
            color: white;
            text-align: center;
            padding: 10px;
            z-index: 1000;
        }

        #header a {
            color: white;
            text-decoration: none;
            margin: 0 15px;
            padding: 5px 10px;
            border: 1px solid white;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        
        #header a.active {
            background-color: #555; /* Different background for the active link */
        }

        #header a:hover {
            background-color: #555;
        }

        /* Style for the content blocks */
        .graph-container {
            display: none; /* Hide all sections by default */
            margin-top: 60px; /* Add space for the fixed header */
            padding: 20px;
        }

        .graph-container.active {
            display: block; /* Show only the active section */
        }
        
        <?php include "app/view/style/chart.css" ?>
    </style>
</head>
<body>
    <!-- Fixed Header with Buttons -->
    <div id="header">
        <a href="#" data-target="hardware_graph" class="active">Hardware</a>
        <a href="#" data-target="innodb_graph">InnoDB</a>
        <a href="#" data-target="qcache_graph">qCache</a>
    </div>

    <!-- Graph Sections -->
    <div id="hardware_graph" class="graph-container active">
        <?php include "app/view/templates/graphs/hardware.php"; ?>
    </div>
    <div id="innodb_graph" class="graph-container">
        <?php include "app/view/templates/graphs/innodb.php"; ?>
    </div>
    <div id="qcache_graph" class="graph-container">
        <?php include "app/view/templates/graphs/qcache.php"; ?>
    </div>

    <script>
        // JavaScript to toggle visibility
        document.querySelectorAll('#header a').forEach(link => {
            link.addEventListener('click', event => {
                event.preventDefault(); // Prevent default link behavior

                // Remove active class from all links
                document.querySelectorAll('#header a').forEach(link => {
                    link.classList.remove('active');
                });

                // Add active class to the clicked link
                event.target.classList.add('active');

                // Hide all graph containers
                document.querySelectorAll('.graph-container').forEach(container => {
                    container.classList.remove('active');
                });

                // Show the target graph container
                const targetId = event.target.getAttribute('data-target');
                document.getElementById(targetId).classList.add('active');
            });
        });
    </script>
</body>
</html>
<?php
$conn->close();
?>
