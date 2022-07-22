<?php
require_once __DIR__ . "/../vendor/autoload.php";
use ILIAS\GlobalScreen\Scope\MainMenu\Provider\AbstractStaticPluginMainMenuProvider;
use srag\DIC\LearningProgressLookup\DICTrait;
use srag\Plugins\LearningProgressLookup\Menu\Menu;

/**
 * ilLearningProgressLookupPlugin
 *
 * @author  Michael Herren <mh@studer-raimann.ch>
 * @version $Id$
 *
 */
class ilLearningProgressLookupPlugin extends ilUserInterfaceHookPlugin {

    use DICTrait;

    const PLUGIN_CLASS_NAME = ilLearningProgressLookupPlugin::class;

    //const CRONJOB_AUTH_TOKEN = "8d641029d094c05947ed9b3566d5b959cc643136";

    protected static $instance;
    protected $access;


	/**
	 * @return ilLearningProgressLookupPlugin
	 */
	public static function getInstance() {
		if (!isset(self::$instance)) {
			self::$instance = new self();
		}

		return self::$instance;
	}


    /**
     * @throws Exception
     */
    public function init() {
		self::loadActiveRecord();
	}


	/**
	 * @return string
	 */
	public function getPluginName() {
		return 'LearningProgressLookup';
	}


	/**
	 * @return ilLearningProgressLookupAccess
	 */
	public function getAccessManager() {
		if (is_null($this->access)) {
			$this->access = new ilLearningProgressLookupAccess();
		}

		return $this->access;
	}


	/**
	 * Check ILIAS-Version
	 *
	 * @return bool
	 */
	public static function isIliasVersion5() {
		if (ilComponent::isVersionGreaterString(ILIAS_VERSION_NUMERIC, '4.9.999')) {
			return true;
		}

		return false;
	}


	/**
	 * Load ActiveRecords
	 *
	 * @throws Exception
	 */
	public static function loadActiveRecord() {
		if (is_file('./Customizing/global/plugins/Libraries/ActiveRecord/class.ActiveRecord.php')) {
			require_once('./Customizing/global/plugins/Libraries/ActiveRecord/class.ActiveRecord.php');
		} else {
			if (self::isIliasVersion5()) {
				require_once('./Services/ActiveRecord/class.ActiveRecord.php');
			} else {
				throw new Exception("Could not load ActiveRecord-Library in the LP-Lookup Plugin");
			}
		}
	}
	/*public function execCronjob(array $tasks = array()) {
		global $DIC;
		$ilLog = $DIC['ilLog'];
		$ilLog->write("ilTrainingProgram-Cron: Start");
		$ch = curl_init();

		$path_to_cron_script = $_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']).'/Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/TrainingProgram/cron.php?auth='.self::CRONJOB_AUTH_TOKEN;

		$path_to_cron_script .= '&tasks='.implode(':', $tasks);

		$ilLog->write("Calls cronjob with url: ".$path_to_cron_script);

		//var_dump($path_to_cron_script);

		curl_setopt($ch, CURLOPT_URL, $path_to_cron_script);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_USERPWD, 'campus:ilias#2014');
		//curl_setopt($ch, CURLOPT_USERPWD, "tester:test");
		
		// used for async requests
		curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
		// needs some time to be able to handle basic auth
		curl_setopt($ch, CURLOPT_TIMEOUT, 2);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);

		curl_exec($ch);

		// after 2000 miliseconds the authentification should be done and the script can continue (timeout is not an error)
		$curl_error = curl_errno($ch);
		if($curl_error != '' && $curl_error != 28) {
			$ilLog->write("ilTrainingProgram-Cron: Error in executing: ".curl_error($ch));
		}

		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if($curl_error != 28 && $http_code != 200) {
			$ilLog->write("ilTrainingProgram-Cron: Cron-Job AUTH didn't work! Connection-Info: ".print_r(curl_getinfo($ch), true));
		}

		curl_close($ch);
		$ilLog->write("ilTrainingProgram-Cron: End");

		/*$path_to_cron_script = '/usr/bin/php /var/www/ilias_dev/Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/TrainingProgram/cron.php';

		if($skip_complete_cleanup) {
			$path_to_cron_script .= '&skip_complete_cleanup=true';
		}

		//echo $exec;
		$re = array();
		exec($path_to_cron_script, $re);


		/*$cmd = "curl -X POST -H 'Content-Type: application/html'";
		$cmd.= " '" . $path_to_cron_script . "'";

		exec($cmd, $output, $exit);

		var_dump($cmd);
		var_dump($output);
		var_dump($exit);
	} */


	public function promoteGlobalScreenProvider() : AbstractStaticPluginMainMenuProvider
	{
		return new Menu(self::dic()->dic(), $this);
	}
}

?>
