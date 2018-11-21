<?php
use srag\DIC\LearningProgressLookup\DICTrait;

/**
 * Class srLearningProgressLookupCourseTableGUI
 *
 * @author  Michael Herren <mh@studer-raimann.ch>
 * @version 1.0.0
 */
class srLearningProgressLookupStatusTableGUI extends ilTable2GUI {

    use DICTrait;
    const PLUGIN_CLASS_NAME = ilLearningProgressLookupPlugin::class;

	/** @var  array $filter */
	protected $filter = array();
	protected $ref_id;
	protected $ignored_cols;
	/** @var bool */
	protected $show_default_filter = false;
	/** @var array */
	protected $numeric_fields = array( "" );

    /**
     * srLearningProgressLookupStatusTableGUI constructor.
     * @param $parent_obj
     * @param $ref_id
     * @param string $parent_cmd
     * @throws \srag\DIC\LearningProgressLookup\Exception\DICException
     */
	public function __construct($parent_obj, $ref_id, $parent_cmd = "index") {
		$this->ref_id = $ref_id;

		if (!self::dic()->ctrl()->getCmd()) {
			$this->setShowDefaultFilter(true);
		}

		$this->setPrefix('sr_xlpl_status_');
		$this->setId('xlpl_status');

		parent::__construct($parent_obj, $parent_cmd, '');

		$this->setRowTemplate('tpl.status_row.html', self::plugin()->getPluginObject()->getDirectory());
		$this->setFormAction(self::dic()->ctrl()->getFormAction($parent_obj));
		$this->setFormName('xlpl_status_table');

		$this->setShowRowsSelector(false);

		$this->setEnableTitle(true);
		$this->setDisableFilterHiding(true);
		$this->setEnableNumInfo(true);

		$this->setIgnoredCols(array( '' ));
		$this->setTitle(self::plugin()->translate('title_search_users', '', [ilObject::_lookupTitle(ilObject::_lookupObjectId($this->ref_id))]));
		$this->setEnableHeader(false);

		//$this->setExportFormats(array(self::EXPORT_EXCEL,self::EXPORT_CSV));

		$this->initFilter();
		$this->addColumns();

		if (!in_array($parent_cmd, array( 'applyFilter', 'resetFilter' ))) {
			$this->parseData();
		}
	}


    /**
     *
     */
    protected function parseData() {
		$this->setExternalSorting(true);
		$this->setExternalSegmentation(true);

		$this->setDefaultOrderField('login');
		$this->setDefaultOrderDirection("ASC");

		$this->determineLimit();
		$this->determineOffsetAndOrder();

		$this->setOrderField('login');
		$this->setOrderDirection("ASC");

		// load users
		$options = array(
			'filters' => $this->filter,
			'limit'   => array(),
			'count'   => true,
			'sort'    => array( 'field' => $this->getOrderField(), 'direction' => $this->getOrderDirection() ),
		);
		$count = srLearningProgressLookupModel::getCourseUsers($this->ref_id, $options);

		$options['limit'] = array( 'start' => (int)$this->getOffset(), 'end' => (int)$this->getLimit() );
		$options['count'] = false;
		$data = srLearningProgressLookupModel::getCourseUsers($this->ref_id, $options);

		// load modules and progress
		$options = array(
			'filters' => $this->filter,
			'limit'   => array( 'start' => (int)$this->getOffset(), 'end' => (int)$this->getLimit() ),
			'count'   => false,
			'sort'    => array( 'field' => $this->getOrderField(), 'direction' => $this->getOrderDirection() ),
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


    /**
     * @throws \srag\DIC\LearningProgressLookup\Exception\DICException
     */
    public function initFilter() {
		// Login
		$login_item = new ilTextInputGUI(self::plugin()->translate('filter_label_login'), 'login');
		$this->addFilterItem($login_item);
		$login_item->readFromSession();
		$this->filter['login'] = $login_item->getValue();

		// Offline Courses
		$offline_item = new ilCheckboxInputGUI(self::plugin()->translate('filter_label_offline'), 'offline');
		$this->addFilterItem($offline_item);
		$offline_item->readFromSession();
		$this->filter['offline'] = $offline_item->getChecked();
	}


	/**
	 * @return array
	 */
	public function getTableColumns() {
		$cols = array();

		$cols['title'] = array( 'txt' => self::plugin()->translate('title'), 'default' => true, 'width' => 'auto' );
		$cols['status'] = array( 'txt' => self::plugin()->translate('status'), 'default' => true, 'width' => 'auto' );

		return $cols;
	}


    /**
     * @return array
     */
    public function getSelectableColumns() {
		return array();
	}


    /**
     *
     */
    private function addColumns() {
		foreach ($this->getTableColumns() as $k => $v) {
			//if ($this->isColumnSelected($k)) {
			if (isset($v['sort_field'])) {
				$sort = $v['sort_field'];
			} else {
				$sort = null;
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
				$this->export_formats[$format_key] = self::plugin()->getPluginObject()->getPrefix() . "_" . $this->custom_export_formats[$format_key];
			}
		}
	}


    /**
     * @param int $format
     * @param bool $send
     */
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
		$this->tpl->setVariable('USER', self::plugin()->translate('user_status_line', '', [$a_set['login'], $a_set['firstname'], $a_set['lastname']]));
		$this->tpl->setVariable('LAST_LOGIN', self::plugin()->translate('user_last_login_line', '', [date("d.m.Y H:i", strtotime($a_set['last_login']))]));

		$this->tpl->setCurrentBlock('module_tr');
		$this->tpl->setVariable('CSS_CLASS', 'status_line');
		$this->tpl->setVariable('COURSE', self::plugin()->translate('course_title'));
		$this->tpl->setVariable('STATUS', self::plugin()->translate('status_title'));
		$this->tpl->parseCurrentBlock();

		if (count($a_set['modules']) > 0) {
			$odd = true;
			foreach ($a_set['modules'] as $key => $module) {
				$this->tpl->setCurrentBlock('module_tr');

				$css_class = "status_list_entry ";
				$css_class = "";
				$css_class .= ($odd) ? "odd " : "even ";
				$css_class .= ($module['offline']) ? "offline" : "";
				$this->tpl->setVariable('CSS_CLASS', $css_class);

				$this->tpl->setVariable('COURSE', ilUtil::img($module['icon'], "", 22) . $module['title']);

				$status = $a_set['user_progresses'][$module['obj_id']]['status'];
				$this->tpl->setVariable('STATUS', ilUtil::img(srLearningProgressLookupModel::getProgressStatusImageTag($status), srLearningProgressLookupModel::getProgressStatusRepresentation($status), 18, 18));
				$this->tpl->parseCurrentBlock();
				$odd = !$odd;
			}
		} else {
			$this->tpl->setCurrentBlock('module_tr');
			$css_class = "status_list_entry ";
			$this->tpl->setVariable('CSS_CLASS', $css_class);
			$this->tpl->setVariable('COURSE', self::plugin()->translate('no_modules_with_permission'));
			$this->tpl->parseCurrentBlock();
		}
	}


    /**
     * @param ilExcel $a_excel
     * @param int $a_row
     * @param array $a_set
     */
	protected function fillRowExcel(ilExcel $a_excel, &$a_row, $a_set) {
		$col = 0;

		foreach ($this->getSelectableColumns() as $k => $v) {
			if ($this->isColumnSelected($k)) {
				if (is_array($a_set[$k])) {
					$a_set[$k] = implode(', ', $a_set[$k]);
				}
                $a_excel->writeString($a_row, $col, strip_tags($a_set[$k]));
				$col ++;
			}
		}
	}

    /**
     * @param ilExcel $a_excel
     * @param int $a_row
     */
	protected function fillHeaderExcel(ilExcel $a_excel, &$a_row) {
		$col = 0;
		foreach ($this->getSelectableColumns() as $column_key => $column) {
			$title = strip_tags($column["txt"]);
			if (!in_array($column_key, $this->getIgnoredCols()) && $title != '') {
				if ($this->isColumnSelected($column_key)) {
                    $a_excel->write($a_row, $col, $title);
					$col ++;
				}
			}
		}
		$a_row ++;
	}


	/**
	 * @param object $a_csv
	 * @param array $a_set
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