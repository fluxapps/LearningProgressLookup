<?php

require_once("./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/LearningProgressLookup/classes/class.ilLearningProgressLookupPlugin.php");
require_once("./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/LearningProgressLookup/classes/Course/class.srLearningProgressLookupCourseTableGUI.php");
require_once("./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/LearningProgressLookup/classes/class.srLearningProgressLookupModel.php");
require_once('./Services/Object/classes/class.ilObjectLP.php');

/**
 * GUI-Class Table srLearningProgressLookupCourseGUI
 *
 * @author            Michael Herren <mh@studer-raimann.ch>
 *
 * @ilCtrl_IsCalledBy srLearningProgressLookupCourseGUI: ilLearningProgressLookupGUI
 */
class srLearningProgressLookupCourseGUI {

	const CMD_DEFAULT = 'index';
	const CMD_RESET_FILTER = 'resetFilter';
	const CMD_APPLY_FILTER = 'applyFilter';
	/**
	 * @var  ilTable2GUI
	 */
	protected $table;
	protected $tpl;
	protected $ctrl;
	protected $pl;
	protected $toolbar;
	protected $tabs;
	protected $access;


	function __construct() {
		global $tpl, $ilCtrl, $ilAccess, $lng, $ilToolbar, $ilTabs;
		/**
		 * @var ilTemplate $tpl
		 * @var ilCtrl $ilCtrl
		 * @var ilAccessHandler $ilAccess
		 */
		$this->pl = ilLearningProgressLookupPlugin::getInstance();
		$this->tpl = $tpl;
		$this->ctrl = $ilCtrl;
		$this->toolbar = $ilToolbar;
		$this->tabs = $ilTabs;
		$this->access = $this->pl->getAccessManager();

		$this->tpl->setTitle($this->pl->txt('plugin_title'));
	}


	protected function checkAccessOrFail() {
		if ($this->access->hasCurrentUserViewPermission()) {
			return true;
		}

		throw new ilException("You have no permission to access this GUI!");
	}


	public function executeCommand() {
		$cmd = $this->ctrl->getCmd();

		$this->checkAccessOrFail();

		$this->tpl->getStandardTemplate();
		//$this->tabs->addTab("course_gui", $this->pl->txt('title_search_course'), $this->ctrl->getLinkTarget($this));

		switch ($cmd) {
			case self::CMD_RESET_FILTER:
			case self::CMD_APPLY_FILTER:
				$this->$cmd();
				break;
			default:
				$this->index();
				break;
		}

		$content = $this->table->getHTML();
		$content .= '<div class="lookup_legend">' . $this->__getLegendHTML() . '</div>';

		$this->tpl->setContent($content);
	}


	public function index() {
		$this->table = new srLearningProgressLookupCourseTableGUI($this);
		$this->tpl->setContent($this->table->getHTML());
	}


	public function applyFilter() {
		$this->table = new srLearningProgressLookupCourseTableGUI($this, self::CMD_APPLY_FILTER);
		$this->table->writeFilterToSession();
		$this->table->resetOffset();
		$this->index();
	}


	public function resetFilter() {
		$this->table = new srLearningProgressLookupCourseTableGUI($this, self::CMD_RESET_FILTER);
		$this->table->resetOffset();
		$this->table->resetFilter();
		$this->index();
	}


	public function cancel() {
		$this->ctrl->redirect($this);
	}


	public function __getLegendHTML() {
		$tpl = new ilTemplate("tpl.offline_legend.html", true, true, "./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/LearningProgressLookup");
		$tpl->setVariable("IMG_COMPLETED", ilUtil::getImagePath("scorm/completed.svg"));
		$tpl->setVariable("IMG_FAILED", ilUtil::getImagePath("scorm/failed.svg"));
		$tpl->setVariable("TXT_COMPLETED", $this->pl->txt("online_status_1"));
		$tpl->setVariable("TXT_FAILED", $this->pl->txt("online_status_0"));

		return $tpl->get();
	}
}

?>
