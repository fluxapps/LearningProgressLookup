<?php

require_once("./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/LearningProgressLookup/classes/class.ilLearningProgressLookupPlugin.php");
require_once("./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/LearningProgressLookup/classes/Course/class.srLearningProgressLookupCourseTableGUI.php");
require_once("./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/LearningProgressLookup/classes/class.srLearningProgressLookupModel.php");

/**
 * GUI-Class Table srLearningProgressLookupCourseGUI
 *
 * @author Michael Herren <mh@studer-raimann.ch>
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
		 * @var ilTemplate      $tpl
		 * @var ilCtrl          $ilCtrl
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
		if($this->access->hasCurrentUserViewPermission()) {
			return true;
		}

		throw new ilException("You have no permission to access this GUI!");
	}


	public function executeCommand() {
		$cmd = $this->ctrl->getCmd();

		$this->checkAccessOrFail();

		$this->tpl->getStandardTemplate();

		switch ($cmd) {
			case self::CMD_RESET_FILTER:
			case self::CMD_APPLY_FILTER:
				$this->$cmd();
				break;
			default:
				$this->index();
				break;
		}

		$this->tpl->setContent($this->table->getHTML());
	}


	public function index() {
		//$this->toolbar->addButton($this->pl->txt('new_role'), $this->ctrl->getLinkTargetByClass("ilTrainingProgramRoleGUI", 'newRole'));
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


}

?>
