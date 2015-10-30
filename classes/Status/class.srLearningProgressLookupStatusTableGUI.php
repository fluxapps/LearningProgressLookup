<?php
require_once('./Services/Table/classes/class.ilTable2GUI.php');

require_once('./Services/Form/classes/class.ilTextInputGUI.php');
require_once('./Services/Form/classes/class.ilSelectInputGUI.php');
require_once('./Services/Form/classes/class.ilDateTimeInputGUI.php');
require_once("./Services/Form/classes/class.ilCombinationInputGUI.php");
require_once("./Services/Form/classes/class.ilCheckboxInputGUI.php");

require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/LearningProgressLookup/classes/class.ilLearningProgressLookupPlugin.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/LearningProgressLookup/classes/Course/class.srLearningProgressLookupCourseTableGUI.php');

/**
 * Class srLearningProgressLookupCourseTableGUI
 *
 * @author  Michael Herren <mh@studer-raimann.ch>
 * @version 1.0.0
 */
class srLearningProgressLookupStatusTableGUI extends ilTable2GUI {

	/**
	 * @var ilCtrl $ctrl
	 */
	protected $ctrl;
	/** @var  array $filter */
	protected $filter = array();
	protected $access;
	protected $ref_id;

	protected $ignored_cols;

	/** @var bool  */
	protected  $show_default_filter = false;

	/** @var array  */
	protected  $numeric_fields = array("");


	/**
	 * @param srLearningProgressLookupCourseGUI  $parent_obj
	 * @param string                        $parent_cmd
	 */
	public function __construct($parent_obj, $ref_id, $parent_cmd = "index") {
		/** @var $ilCtrl ilCtrl */
		/** @var ilToolbarGUI $ilToolbar */
		global $ilCtrl, $ilToolbar;

		$this->ctrl = $ilCtrl;
		$this->pl = ilLearningProgressLookupPlugin::getInstance();
		$this->access = $this->pl->getAccessManager();
		$this->toolbar = $ilToolbar;
		$this->ref_id = $ref_id;

		if(!$ilCtrl->getCmd()) {
			$this->setShowDefaultFilter(true);
		}

		$this->setPrefix('sr_xlpl_status_');
		$this->setId('xlpl_status');

		parent::__construct($parent_obj, $parent_cmd, '');

		$this->setRowTemplate('tpl.status_row.html', $this->pl->getDirectory());
		$this->setFormAction($this->ctrl->getFormAction($parent_obj));
		$this->setFormName('xlpl_status_table');
		//$this->setDefaultOrderField('Datetime');
		$this->setDefaultOrderDirection('desc');

		$this->setShowRowsSelector(false);

		$this->setEnableTitle(true);
		$this->setDisableFilterHiding(true);
		$this->setEnableNumInfo(true);

		$this->setIgnoredCols(array(''));
		$this->setTitle(sprintf($this->pl->txt('title_search_users'), ilObject::_lookupTitle(ilObject::_lookupObjectId($this->ref_id))));
		$this->setEnableHeader(false);

		//$this->setExportFormats(array(self::EXPORT_EXCEL,self::EXPORT_CSV));

		$this->initFilter();
		$this->addColumns();

        if (!in_array($parent_cmd, array('applyFilter', 'resetFilter'))) {
            $this->parseData();
        }
	}


	protected function parseData() {
		global $ilUser;
		$this->setExternalSorting(true);
		$this->setExternalSegmentation(true);
		$this->setDefaultOrderField($this->columns[0]);

		$this->determineLimit();
		$this->determineOffsetAndOrder();

		// load users
		$options = array(
			'filters' => $this->filter,
			'limit' => array(),
			'count' => true,
			'sort' => array( 'field' => $this->getOrderField(), 'direction' => $this->getOrderDirection() ),
		);
		$count = srLearningProgressLookupModel::getCourseUsers($this->ref_id, $options);

		$options['limit'] = array( 'start' => (int)$this->getOffset(), 'end' => (int)$this->getLimit() );
		$options['count'] = false;
		$data = srLearningProgressLookupModel::getCourseUsers($this->ref_id, $options);

		// load modules and progress
		$options = array(
			'filters' => $this->filter,
			'limit' => array( 'start' => (int)$this->getOffset(), 'end' => (int)$this->getLimit() ),
			'count' => false,
			'sort' => array( 'field' => $this->getOrderField(), 'direction' => $this->getOrderDirection() ),
		);

		$module_data = srLearningProgressLookupModel::getCourseModules($this->ref_id, $options);
		$module_status_data = srLearningProgressLookupModel::getUserProgresses($this->ref_id, array_keys($data));

		$this->setMaxCount($count);

		$rows = array();
		/** @var $courseRecord */
		foreach ($data as $courseRecord) {
			$row = array();
			$row = $courseRecord;

			//$row['course_status'] = srLearningProgressLookupModel::getProgressStatusRepresentation($courseRecord['offline']);
			$row['modules'] = $module_data;
			$row['user_progresses'] = $module_status_data[$row['usr_id']];
			$rows[] = $row;
		}

		$this->setData($rows);
	}

	public function initFilter() {
		global $ilUser;
		// Login
		$login_item = new ilTextInputGUI($this->pl->txt('filter_label_login'), 'login');
		$this->addFilterItem($login_item);
		$login_item->readFromSession();
		$this->filter['login'] = $login_item->getValue();

		// Offline Courses
		$offline_item = new ilCheckboxInputGUI($this->pl->txt('filter_label_offline'), 'offline');
		$this->addFilterItem($offline_item);
		$offline_item->readFromSession();
		$this->filter['offline'] = $offline_item->getChecked();
	}


	/**
	 * @return array
	 */
	public function getTableColumns() {
		$cols = array();

		$cols['title'] = array( 'txt' => $this->pl->txt('title'), 'default' => true, 'width' => 'auto');
		$cols['status'] = array( 'txt' => $this->pl->txt('status'), 'default' => true, 'width' => 'auto');

		return $cols;
	}

	public function getSelectableColumns() {
		return array();
	}


	private function addColumns() {
		foreach ($this->getTableColumns() as $k => $v) {
			//if ($this->isColumnSelected($k)) {
				if (isset($v['sort_field'])) {
					$sort = $v['sort_field'];
				} else {
					$sort = NULL;
				}
				$this->addColumn($v['txt'], $sort, $v['width']);
			//}
		}
	}


	/**
	 * @param array $formats
	 */
	public function setExportFormats(array $formats) {

		parent::setExportFormats($formats);

		$custom_fields = array_diff($formats, $this->export_formats);

		foreach ($custom_fields as $format_key) {
			if (isset($this->custom_export_formats[$format_key])) {
				$this->export_formats[$format_key] = $this->pl->getPrefix() . "_" . $this->custom_export_formats[$format_key];
			}
		}
	}


	public function exportData($format, $send = false) {
		if (array_key_exists($format, $this->custom_export_formats)) {
			if ($this->dataExists()) {

				foreach ($this->custom_export_generators as $export_format => $generator_config) {
					if ($this->getExportMode() == $export_format) {
						$generator_config['generator']->generate();
					}
				}
			}
		} else {
			parent::exportData($format, $send);
		}
	}


	/**
	 * @param array $a_set
	 */
	public function fillRow($a_set) {
		$this->tpl->setVariable('USER', sprintf($this->pl->txt('user_status_line'), $a_set['login'], $a_set['firstname'], $a_set['lastname']));
		$this->tpl->setVariable('LAST_LOGIN', sprintf($this->pl->txt('user_last_login_line'), date("d.m.Y H:i", strtotime($a_set['last_login']))));

		$this->tpl->setCurrentBlock('module_tr');
		$this->tpl->setVariable('CSS_CLASS', 'status_line');
		$this->tpl->setVariable('COURSE', $this->pl->txt('course_title'));
		$this->tpl->setVariable('STATUS', $this->pl->txt('status_title'));
		$this->tpl->parseCurrentBlock();

		if(count($a_set['modules']) > 0) {
			$odd = true;
			foreach ($a_set['modules'] as $key => $module) {
				$this->tpl->setCurrentBlock('module_tr');

				$css_class = "status_list_entry ";
				$css_class .= ($odd) ? "odd " : "even ";
				$css_class .= ($module['offline']) ? "offline" : "";
				$this->tpl->setVariable('CSS_CLASS', $css_class);

				$this->tpl->setVariable('COURSE', ilUtil::img($module['icon'], "", 22) . $module['title']);

				$status = $a_set['user_progresses'][$module['obj_id']]['status'];
				$this->tpl->setVariable('STATUS', ilUtil::img(srLearningProgressLookupModel::getProgressStatusImageTag($status), srLearningProgressLookupModel::getProgressStatusRepresentation($status), 18));
				$this->tpl->parseCurrentBlock();
				$odd = !$odd;
			}
		} else {
			$this->tpl->setCurrentBlock('module_tr');
			$css_class = "status_list_entry ";
			$this->tpl->setVariable('CSS_CLASS', $css_class);
			$this->tpl->setVariable('COURSE', $this->pl->txt('no_modules_with_permission'));
			$this->tpl->parseCurrentBlock();
		}
	}


	/**
	 * @param object $a_worksheet
	 * @param int    $a_row
	 * @param array  $a_set
	 */
	protected function fillRowExcel($a_worksheet, &$a_row, $a_set) {
		$col = 0;

		foreach ($this->getSelectableColumns() as $k => $v) {
			if ($this->isColumnSelected($k)) {
				if (is_array($a_set[$k])) {
					$a_set[$k] = implode(', ', $a_set[$k]);
				}
				$a_worksheet->writeString($a_row, $col, strip_tags($a_set[$k]));
				$col ++;
			}
		}
	}

	protected function fillHeaderExcel($worksheet, &$a_row)
	{
		$col = 0;
		foreach ($this->getSelectableColumns() as $column_key => $column)
		{
			$title = strip_tags($column["txt"]);
			if(!in_array($column_key, $this->getIgnoredCols()) && $title != '')
			{
				if ($this->isColumnSelected($column_key)) {
					$worksheet->write($a_row, $col, $title);
					$col++;
				}
			}
		}
		$a_row++;
	}

	/**
	 * @param object $a_csv
	 * @param array  $a_set
	 */
	protected function fillRowCSV($a_csv, $a_set) {

		foreach ($this->getSelectableColumns() as $k => $v) {
			if ($this->isColumnSelected($k)) {
				if (is_array($a_set[$k])) {
					$a_set[$k] = implode(', ', $a_set[$k]);
				}
				$a_csv->addColumn(strip_tags($a_set[$k]));
			}
		}
		$a_csv->addRow();
	}


	/**
	 * @param array $custom_export_generators
	 */
	public function addCustomExportGenerator($export_format_key, $custom_export_generators, $params = array()) {
		$this->custom_export_generators[$export_format_key] = array( 'generator' => $custom_export_generators, 'params' => $params );
	}


	/**
	 * @param array $custom_export_formats
	 */
	public function addCustomExportFormat($custom_export_format_key, $custom_export_format_label) {
		$this->custom_export_formats[$custom_export_format_key] = $custom_export_format_label;
	}


	/**
	 * @return bool
	 */
	public function numericOrdering($sort_field) {
		return in_array($sort_field, array());
	}


	/**
	 * @param array $ignored_cols
	 */
	public function setIgnoredCols($ignored_cols) {
		$this->ignored_cols = $ignored_cols;
	}


	/**
	 * @return array
	 */
	public function getIgnoredCols() {
		return $this->ignored_cols;
	}

	/**
	 * @param boolean $default_filter
	 */
	public function setShowDefaultFilter($show_default_filter) {
		$this->show_default_filter = $show_default_filter;
	}


	/**
	 * @return boolean
	 */
	public function getShowDefaultFilter() {
		return $this->show_default_filter;
	}
}