<?php

$limits = [
    "general" => 10000,
	"hardware" => 500000,
	"innodb" => 10000,
	"qcache" => 10000,
];

// Hardware Logging
define('LOG_MAX_PROCESSES', 10);  // Change this value as needed for different projects
define("LOG_THRESHOLD_LOAD_AVERAGE", 10);	 // If the load average is above this value, then log LOG_MAX_PROCESSES amount of processes

?>