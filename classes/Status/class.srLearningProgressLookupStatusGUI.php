<?php

require_once("./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/LearningProgressLookup/classes/class.ilLearningProgressLookupPlugin.php");
require_once("./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/LearningProgressLookup/classes/Status/class.srLearningProgressLookupStatusTableGUI.php");
require_once("./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/LearningProgressLookup/classes/class.srLearningProgressLookupModel.php");

require_once("./Services/Tracking/classes/class.ilLearningProgressBaseGUI.php");
/**
 * GUI-Class Table srLearningProgressLookupStatusGUI
 *
 * @author Michael Herren <mh@studer-raimann.ch>
 *
 * @ilCtrl_IsCalledBy srLearningProgressLookupStatusGUI: ilLearningProgressLookupGUI
 */
class srLearningProgressLookupStatusGUI {

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
	protected $lng;

	protected $ref_id;

	function __construct() {
		if(!isset($_GET['ref_id'])) {
			throw new ilException("No ref-ID set!");
		}

		global $tpl, $ilCtrl, $ilAccess, $lng, $ilToolbar, $ilTabs;
		/**
		 * @var ilTemplate      $tpl
		 * @var ilCtrl          $ilCtrl
		 * @var ilAccessHandler $ilAccess
		 * @var ilToolbarGUI $ilToolbar;
		 * @var ilTabsGUI $ilTabs;
		 */
		$this->pl = ilLearningProgressLookupPlugin::getInstance();
		$this->tpl = $tpl;
		$this->ctrl = $ilCtrl;
		$this->toolbar = $ilToolbar;
		$this->tabs = $ilTabs;
		$this->lng = $lng;
		$this->access = $this->pl->getAccessManager();

		$this->ref_id = (int) $_GET['ref_id'];

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

		$this->ctrl->saveParameter($this, 'ref_id');

		$this->tpl->getStandardTemplate();

		/*$this->tabs->clearTargets();
		$this->tabs->addTab("status_gui", sprintf($this->pl->txt('title_search_users'), ilObject::_lookupTitle(ilObject::_lookupObjectId($this->ref_id))), $this->ctrl->getLinkTarget($this));*/
		$this->tabs->setBackTarget($this->pl->txt('back_to_course_search'), $this->ctrl->getLinkTargetByClass(array('illearningprogresslookupgui', 'srlearningprogresslookupcoursegui')));

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
		$this->lng->loadLanguageModule('trac');
		$content .= '<div class="lookup_legend">'.ilLearningProgressBaseGUI::__getLegendHTML().'</div>';

		$this->tpl->setContent($content);
	}


	public function index() {
        $this->table = new srLearningProgressLookupStatusTableGUI($this, $this->getRefId());
		$this->tpl->setContent($this->table->getHTML());
	}


	public function applyFilter() {
        $this->table = new srLearningProgressLookupStatusTableGUI($this,  $this->getRefId(), self::CMD_APPLY_FILTER);
        $this->table->writeFilterToSession();
		$this->table->resetOffset();
		$this->index();
	}


	public function resetFilter() {
        $this->table = new srLearningProgressLookupStatusTableGUI($this,  $this->getRefId(), self::CMD_RESET_FILTER);
		$this->table->resetOffset();
		$this->table->resetFilter();
		$this->index();
	}


	public function cancel() {
		$this->ctrl->redirect($this);
	}

	/**
	 * @return int
	 */
	public function getRefId() {
		return $this->ref_id;
	}

}

?>
