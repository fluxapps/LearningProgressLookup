<?php
require_once __DIR__ . "/../vendor/autoload.php";
use srag\DIC\LearningProgressLookup\DICTrait;

/**
 * GUI-Class ilLearningProgressLookupGUI
 *
 * @author            Michael Herren <mh@studer-raimann.ch>
 * @version           $Id:
 *
 * @ilCtrl_IsCalledBy ilLearningProgressLookupGUI: ilRouterGUI, ilUIPluginRouterGUI
 * @ilCtrl_Calls      ilLearningProgressLookupGUI: srLearningProgressLookupCourseGUI
 * @ilCtrl_Calls      ilLearningProgressLookupGUI: srLearningProgressLookupStatusGUI
 */
class ilLearningProgressLookupGUI {

    use DICTrait;
    
    const PLUGIN_CLASS_NAME = ilLearningProgressLookupPlugin::class;

	const RELOAD_LANGUAGES = false;


    /**
     * ilLearningProgressLookupGUI constructor.
     * @throws \srag\DIC\LearningProgressLookup\Exception\DICException
     */
    public function __construct() {
		if (self::RELOAD_LANGUAGES OR $_GET['rl'] == 'true') {
			self::plugin()->getPluginObject()->updateLanguages();
		}
	}


    /**
     * @return bool
     * @throws \srag\DIC\LearningProgressLookup\Exception\DICException
     * @throws ilCtrlException
     * @throws ilException
     */
    public function executeCommand() {
		self::dic()->ui()->mainTemplate()->getStandardTemplate();
		self::dic()->ui()->mainTemplate()->addCss(self::plugin()->getPluginObject()->getStyleSheetLocation("default/learning_progress_lookup.css"));

		$next_class = self::dic()->ctrl()->getNextClass($this);
		if (!$this->accessCheck($next_class)) {
			ilUtil::sendFailure(self::dic()->language()->txt("no_permission"), true);
			ilUtil::redirect("");

			return false;
		}

		switch ($next_class) {
			case '':
			case 'srlearningprogresslookupcoursegui':
				$gui = new srLearningProgressLookupCourseGUI();
				self::dic()->ctrl()->forwardCommand($gui);
				break;
			case 'srlearningprogresslookupstatusgui':
				$gui = new srLearningProgressLookupStatusGUI();
				self::dic()->ctrl()->forwardCommand($gui);
				break;
			default:
				require_once(self::dic()->ctrl()->lookupClassPath($next_class));
				$gui = new $next_class();
				self::dic()->ctrl()->forwardCommand($gui);
				break;
		}

		self::dic()->ui()->mainTemplate()->show();

		return true;
	}


    /**
     * @param $next_class
     * @return bool
     * @throws \srag\DIC\LearningProgressLookup\Exception\DICException
     */
    protected function accessCheck($next_class) {
		switch ($next_class) {
			case '':
			case 'srlearningprogresslookupcoursegui':
			case 'srlearningprogresslookupstatusgui':
				return self::plugin()->getPluginObject()->getAccessManager()->hasCurrentUserViewPermission();
				break;
		}

		return false;
	}
}

?>