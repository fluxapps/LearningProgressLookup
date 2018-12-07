<?php
use srag\DIC\LearningProgressLookup\DICTrait;

/**
 * GUI-Class Table srLearningProgressLookupCourseGUI
 *
 * @author            Michael Herren <mh@studer-raimann.ch>
 *
 * @ilCtrl_IsCalledBy srLearningProgressLookupCourseGUI: ilLearningProgressLookupGUI
 */
class srLearningProgressLookupCourseGUI {

    use DICTrait;
    const PLUGIN_CLASS_NAME = ilLearningProgressLookupPlugin::class;


	const CMD_DEFAULT = 'index';
	const CMD_RESET_FILTER = 'resetFilter';
	const CMD_APPLY_FILTER = 'applyFilter';

	/**
	 * @var  ilTable2GUI
	 */
	protected $table;


    /**
     * srLearningProgressLookupCourseGUI constructor.
     */
    function __construct() {
		self::dic()->ui()->mainTemplate()->setTitle(self::plugin()->translate('plugin_title'));
	}


    /**
     * @return bool
     * @throws ilException
     */
    protected function checkAccessOrFail() {
		if (self::plugin()->getPluginObject()->getAccessManager()->hasCurrentUserViewPermission()) {
			return true;
		}

		throw new ilException("You have no permission to access this GUI!");
	}


    /**
     * @throws ilException
     */
    public function executeCommand() {
		$cmd = self::dic()->ctrl()->getCmd();

		$this->checkAccessOrFail();

		self::dic()->ui()->mainTemplate()->getStandardTemplate();
		//self::dic()->tabs()->addTab("course_gui", self::plugin()->translate('title_search_course'), self::dic()->ctrl()->getLinkTarget($this));

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

		self::dic()->ui()->mainTemplate()->setContent($content);
	}


    /**
     *
     */
    public function index() {
		$this->table = new srLearningProgressLookupCourseTableGUI($this);
	}


    /**
     *
     */
    public function applyFilter() {
		$this->table = new srLearningProgressLookupCourseTableGUI($this, self::CMD_APPLY_FILTER);
		$this->table->writeFilterToSession();
		$this->table->resetOffset();
		$this->index();
	}


    /**
     *
     */
    public function resetFilter() {
		$this->table = new srLearningProgressLookupCourseTableGUI($this, self::CMD_RESET_FILTER);
		$this->table->resetOffset();
		$this->table->resetFilter();
		$this->index();
	}


    /**
     *
     */
    public function cancel() {
		self::dic()->ctrl()->redirect($this);
	}


    /**
     * @return string
     * @throws ilTemplateException
     */
    public function __getLegendHTML() {
		$tpl = new ilTemplate("tpl.offline_legend.html", true, true, "./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/LearningProgressLookup");
		$tpl->setVariable("IMG_COMPLETED", ilUtil::getImagePath("scorm/completed.svg"));
		$tpl->setVariable("IMG_FAILED", ilUtil::getImagePath("scorm/failed.svg"));
		$tpl->setVariable("TXT_COMPLETED", self::plugin()->translate("online_status_1"));
		$tpl->setVariable("TXT_FAILED", self::plugin()->translate("online_status_0"));

		return $tpl->get();
	}
}

?>
