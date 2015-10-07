<?php
require_once('./Services/Table/classes/class.ilTable2GUI.php');

require_once('./Services/Form/classes/class.ilTextInputGUI.php');
require_once('./Services/Form/classes/class.ilSelectInputGUI.php');
require_once('./Services/Form/classes/class.ilDateTimeInputGUI.php');
require_once("./Services/Form/classes/class.ilCombinationInputGUI.php");

require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/LearningProgressLookup/classes/class.ilLearningProgressLookupPlugin.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/LearningProgressLookup/classes/Course/class.srLearningProgressLookupCourseTableGUI.php');

/**
 * Class srLearningProgressLookupCourseTableGUI
 *
 * @author  Michael Herren <mh@studer-raimann.ch>
 * @version 1.0.0
 */
class srLearningProgressLookupCourseTableGUI extends ilTable2GUI {

	/**
	 * @var ilCtrl $ctrl
	 */
	protected $ctrl;
	/** @var  array $filter */
	protected $filter = array();
	protected $access;

	protected $ignored_cols;

	/** @var bool  */
	protected  $show_default_filter = false;

	/** @var array  */
	protected  $numeric_fields = array("course_id");


	/**
	 * @param srLearningProgressLookupCourseGUI  $parent_obj
	 * @param string                        $parent_cmd
	 */
	public function __construct($parent_obj, $parent_cmd = "index") {
		/** @var $ilCtrl ilCtrl */
		/** @var ilToolbarGUI $ilToolbar */
		global $ilCtrl, $ilToolbar;

		$this->ctrl = $ilCtrl;
		$this->pl = ilLearningProgressLookupPlugin::getInstance();
		$this->access = $this->pl->getAccessManager();
		$this->toolbar = $ilToolbar;

		if(!$ilCtrl->getCmd()) {
			$this->setShowDefaultFilter(true);
		}

		$this->setPrefix('sr_xlpl_courses_');
		$this->setFormName('sr_xlpl_courses');
		$this->setId('xlpl_courses');

		parent::__construct($parent_obj, $parent_cmd, '');

		$this->setRowTemplate('tpl.default_row.html', $this->pl->getDirectory());
		$this->setFormAction($this->ctrl->getFormAction($parent_obj));
		//$this->setDefaultOrderField('Datetime');
		$this->setDefaultOrderDirection('desc');

		$this->setShowRowsSelector(false);

		$this->setEnableTitle(true);
		$this->setDisableFilterHiding(true);
		$this->setEnableNumInfo(true);

		$this->setIgnoredCols(array(''));
		$this->setTitle($this->pl->txt('title_search_course'));

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

		$options = array(
			'filters' => $this->filter,
			'limit' => array(),
			'count' => true,
			'sort' => array( 'field' => $this->getOrderField(), 'direction' => $this->getOrderDirection() ),
		);
		$count = srLearningProgressLookupModel::getCourses($options);

		$options['limit'] = array( 'start' => (int)$this->getOffset(), 'end' => (int)$this->getLimit() );
		$options['count'] = false;
		$data = srLearningProgressLookupModel::getCourses($options);

		$this->setMaxCount($count);

		$rows = array();
		/** @var $courseRecord */
		foreach ($data as $courseRecord) {
			$row = array();
			$row = $courseRecord;

			$rows[] = $row;
		}
		$this->setData($rows);
	}

	public function initFilter() {
		// Course
		$item = new ilTextInputGUI($this->pl->txt('course_title'), 'course_title');
		$this->addFilterItem($item);
		$item->readFromSession();
		$this->filter['obj.title'] = $item->getValue();
	}

	public function getTableColumns() {
		$cols = array();

		$cols['course_title'] = array( 'txt' => $this->pl->txt('table_label_title'), 'default' => true, 'width' => 'auto', 'sort_field' => 'course_title' );
		$cols['path'] = array( 'txt' => $this->pl->txt('table_label_path'), 'default' => true, 'width' => 'auto');
		$cols['online'] = array( 'txt' => $this->pl->txt('table_label_online'), 'default' => true, 'width' => 'auto');

		return $cols;
	}


	/**
	 * @return array
	 */
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
		$this->addColumn($this->pl->txt('table_label_action'));
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
		foreach ($this->getTableColumns() as $k => $v) {
				switch($k) {
					case 'online':
						$this->tpl->setCurrentBlock('td');
						$this->tpl->setVariable('VALUE', ilUtil::img(srLearningProgressLookupModel::getOfflineStatusImageTag($a_set['online']), $this->pl->txt("online_status_".$a_set['online'], 18)));
						$this->tpl->parseCurrentBlock();
						break;
					default:
						if ($a_set[$k] != '') {
							$this->tpl->setCurrentBlock('td');
							$this->tpl->setVariable('VALUE', (is_array($a_set[$k]) ? implode(", ", $a_set[$k]) : $a_set[$k]));
							$this->tpl->parseCurrentBlock();
						} else {
							$this->tpl->setCurrentBlock('td');
							$this->tpl->setVariable('VALUE', '&nbsp;');
							$this->tpl->parseCurrentBlock();
						}
						break;
				}
		}


		$this->ctrl->setParameterByClass('srlearningprogresslookupstatusgui', 'ref_id', $a_set['ref_id']);
        $link_target = $this->ctrl->getLinkTargetByClass('srlearningprogresslookupstatusgui');

		$this->tpl->setCurrentBlock("cmd");
		$this->tpl->setVariable("CMD", $link_target);
		$this->tpl->setVariable("CMD_TXT", $this->pl->txt('table_label_lookup'));
		$this->tpl->parseCurrentBlock();

		//$this->tpl->setVariable('ACTIONS', '&nbsp;');
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