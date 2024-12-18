# Web Server Performance Analytics

This repository hosts a PHP-based tool designed to log various server performance metrics, including hardware metrics, InnoDB statistics, and query cache statistics. It uses a cron job setup to gather data periodically, storing it for analysis.

## Installation

Clone the repository and navigate to the project directory:
```bash
git clone https://github.com/Eddcapone/webserver-performance-analytics
cd webserver-performance-analytics
```

## Usage
First, ensure the cron jobs are set up to log the data:

### Set up the cron jobs from the app/cron directory
`crontab -e`  # Add your cron jobs here

Then, you can view the logged data by running the PHP server and accessing index.php:

`php -S localhost:8000`

Open your web browser and go to `http://localhost:8000/index.php`

