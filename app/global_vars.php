<?php

$limits = [
    "general" => 1000,
	"hardware" => 1000,
	"innodb" => 1000,
	"qcache" => 1000
];

// Hardware Logging
define('LOG_MAX_PROCESSES', 10);  // Change this value as needed for different projects
define("LOG_THRESHOLD_LOAD_AVERAGE", 10);	 // If the load average is above this value, then log LOG_MAX_PROCESSES amount of processes

?>