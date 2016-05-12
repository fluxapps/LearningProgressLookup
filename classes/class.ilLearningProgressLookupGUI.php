<?php
require_once("./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/LearningProgressLookup/classes/class.ilLearningProgressLookupPlugin.php");

require_once("./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/LearningProgressLookup/classes/Course/class.srLearningProgressLookupCourseGUI.php");
require_once("./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/LearningProgressLookup/classes/Status/class.srLearningProgressLookupStatusGUI.php");

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

	const RELOAD_LANGUAGES = false;
	protected $tpl;
	protected $ctrl;
	protected $tabs;
	protected $lng;
	protected $access;


	public function __construct() {
		global $tpl, $ilCtrl, $ilTabs, $lng;
		/**
		 * @var $tpl    ilTemplate
		 * @var $ilCtrl ilCtrl
		 * @var $ilTabs ilTabsGUI
		 */
		$this->tpl = $tpl;
		$this->pl = ilLearningProgressLookupPlugin::getInstance();
		$this->ctrl = $ilCtrl;
		$this->tabs = $ilTabs;
		$this->lng = $lng;
		$this->access = $this->pl->getAccessManager();
		if (self::RELOAD_LANGUAGES OR $_GET['rl'] == 'true') {
			$this->pl->updateLanguages();
		}
	}


	public function executeCommand() {
		$cmd = $this->ctrl->getCmd();
		$this->tpl->getStandardTemplate();
		$this->tpl->addCss($this->pl->getStyleSheetLocation("default/learning_progress_lookup.css"));

		$next_class = $this->ctrl->getNextClass($this);
		if (!$this->accessCheck($next_class)) {
			ilUtil::sendFailure($this->lng->txt("no_permission"), true);
			ilUtil::redirect("");

			return false;
		}

		switch ($next_class) {
			case '':
			case 'srlearningprogresslookupcoursegui':
				$gui = new srLearningProgressLookupCourseGUI();
				$this->ctrl->forwardCommand($gui);
				break;
			case 'srlearningprogresslookupstatusgui':
				$gui = new srLearningProgressLookupStatusGUI();
				$this->ctrl->forwardCommand($gui);
				break;
			default:
				require_once($this->ctrl->lookupClassPath($next_class));
				$gui = new $next_class();
				$this->ctrl->forwardCommand($gui);
				break;
		}

		$this->tpl->show();

		return true;
	}


	protected function accessCheck($next_class) {
		switch ($next_class) {
			case '':
			case 'srlearningprogresslookupcoursegui':
			case 'srlearningprogresslookupstatusgui':
				return $this->access->hasCurrentUserViewPermission();
				break;
		}

		return false;
	}
}

?>