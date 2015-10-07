<?php
/**
 * Run Crobjob
 *
 * @author Michael Herren <mh@studer-raimann.ch>
 */
/*ignore_user_abort(true);
set_time_limit(0);
error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);

$cron_path = dirname(__FILE__) . '/classes/class.ilTrainingProgramCron.php';

require_once($cron_path);



$cron = new ilTrainingProgramCron();
if(isset($_GET['tasks']) && strlen($_GET['tasks'])>0) {
	$tasks = explode(':', $_GET['tasks']);
} else {
	//tasks per cronjob?
	$tasks = (isset($_SERVER['argv'][4]) && strlen($_SERVER['argv'][4])>0)? explode(':',$_SERVER['argv'][4]) : array();
}

$cron->run($tasks);
?>*/