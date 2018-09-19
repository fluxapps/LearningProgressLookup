<?php
use srag\DIC\DICTrait;

/**
 * GUI-Class Table srLearningProgressLookupStatusGUI
 *
 * @author            Michael Herren <mh@studer-raimann.ch>
 *
 * @ilCtrl_IsCalledBy srLearningProgressLookupStatusGUI: ilLearningProgressLookupGUI
 */
class srLearningProgressLookupStatusGUI {

    use DICTrait;
    const PLUGIN_CLASS_NAME = ilLearningProgressLookupPlugin::class;

	const CMD_DEFAULT = 'index';
	const CMD_RESET_FILTER = 'resetFilter';
	const CMD_APPLY_FILTER = 'applyFilter';
	/**
	 * @var  ilTable2GUI
	 */
	protected $table;

	protected $ref_id;


    /**
     * srLearningProgressLookupStatusGUI constructor.
     * @throws ilException
     */
    function __construct() {
		if (!isset($_GET['course_ref_id'])) {
			throw new ilException("No course ref-ID set!");
		}

		$this->ref_id = (int)$_GET['course_ref_id'];

		self::dic()->template()->setTitle(self::plugin()->translate('plugin_title'));
	}


    /**
     * @return bool
     * @throws ilException
     */
    protected function checkAccessOrFail() {
		if (self::plugin()->getPluginObject()->getAccessManager()->hasCurrentUserViewPermission() && self::plugin()->getPluginObject()->getAccessManager()->hasCurrentUserStatusPermission($this->ref_id)) {
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

		self::dic()->ctrl()->saveParameter($this, 'course_ref_id');

		self::dic()->template()->getStandardTemplate();

		self::dic()->tabs()->clearTargets();
		self::dic()->tabs()->tabs = null;
		/*self::dic()->tabs()->addTab("status_gui", sprintf(self::plugin()->translate('title_search_users'), ilObject::_lookupTitle(ilObject::_lookupObjectId($this->ref_id))), self::dic()->ctrl()->getLinkTarget($this));*/
		self::dic()->tabs()->setBackTarget(self::plugin()->translate('back_to_course_search'), self::dic()->ctrl()->getLinkTargetByClass(array(
			'illearningprogresslookupgui',
			'srlearningprogresslookupcoursegui',
		)));

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

		// display legend
		self::dic()->language()->loadLanguageModule('trac');
		$content .= '<div class="lookup_legend">' . ilLearningProgressBaseGUI::__getLegendHTML() . '</div>';

		self::dic()->template()->setContent($content);
	}


    /**
     *
     */
    public function index() {
		$this->table = new srLearningProgressLookupStatusTableGUI($this, $this->getRefId());
	}


    /**
     *
     */
    public function applyFilter() {
		$this->table = new srLearningProgressLookupStatusTableGUI($this, $this->getRefId(), self::CMD_APPLY_FILTER);
		$this->table->writeFilterToSession();
		$this->table->resetOffset();
		$this->index();
	}


    /**
     *
     */
    public function resetFilter() {
		$this->table = new srLearningProgressLookupStatusTableGUI($this, $this->getRefId(), self::CMD_RESET_FILTER);
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
	 * @return int
	 */
	public function getRefId() {
		return $this->ref_id;
	}
}

?>
