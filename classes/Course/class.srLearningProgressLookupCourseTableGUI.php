<?php
use srag\DIC\LearningProgressLookup\DICTrait;
/**
 * Class srLearningProgressLookupCourseTableGUI
 *
 * @author  Michael Herren <mh@studer-raimann.ch>
 * @version 1.0.0
 */
class srLearningProgressLookupCourseTableGUI extends ilTable2GUI {

    use DICTrait;
    const PLUGIN_CLASS_NAME = ilLearningProgressLookupPlugin::class;

	/** @var  array $filter */
	protected $filter = array();
	protected $ignored_cols;
	/** @var bool */
	protected $show_default_filter = false;
	/** @var array */
	protected $numeric_fields = array( "course_id" );


    /**
     * srLearningProgressLookupCourseTableGUI constructor.
     * @param $parent_obj
     * @param string $parent_cmd
     * @throws \srag\DIC\LearningProgressLookup\Exception\DICException
     */
	public function __construct($parent_obj, $parent_cmd = "index") {
		if (!self::dic()->ctrl()->getCmd()) {
			$this->setShowDefaultFilter(true);
		}

		$this->setPrefix('sr_xlpl_courses_');
		$this->setFormName('sr_xlpl_courses');
		$this->setId('xlpl_courses');

		parent::__construct($parent_obj, $parent_cmd, '');

		$this->setRowTemplate('tpl.default_row.html', self::plugin()->getPluginObject()->getDirectory());
		$this->setFormAction(self::dic()->ctrl()->getFormAction($parent_obj));
		//$this->setDefaultOrderField('Datetime');
		$this->setDefaultOrderDirection('desc');

		$this->setShowRowsSelector(false);

		$this->setEnableTitle(true);
		$this->setDisableFilterHiding(true);
		$this->setEnableNumInfo(true);

		$this->setIgnoredCols(array( '' ));
		$this->setTitle(self::plugin()->translate('title_search_course'));

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
		$this->setDefaultOrderField($this->columns[0]);

		$this->determineLimit();
		$this->determineOffsetAndOrder();

		$options = array(
			'filters' => $this->filter,
			'limit'   => array(),
			'count'   => true,
			'sort'    => array( 'field' => $this->getOrderField(), 'direction' => $this->getOrderDirection() ),
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


    /**
     * @throws \srag\DIC\LearningProgressLookup\Exception\DICException
     */
    public function initFilter() {
		// Course
		$item = new ilTextInputGUI(self::plugin()->translate('course_title'), 'course_title');
		$this->addFilterItem($item);
		$item->readFromSession();
		$this->filter['obj.title'] = $item->getValue();
	}


    /**
     * @return array
     * @throws \srag\DIC\LearningProgressLookup\Exception\DICException
     */
    public function getTableColumns() {
		$cols = array();

		$cols['course_title'] = array(
			'txt'        => self::plugin()->translate('table_label_title'),
			'default'    => true,
			'width'      => 'auto',
			'sort_field' => 'course_title',
		);
		$cols['path'] = array( 'txt' => self::plugin()->translate('table_label_path'), 'default' => true, 'width' => 'auto' );
		$cols['online'] = array( 'txt' => self::plugin()->translate('table_label_online'), 'default' => true, 'width' => 'auto' );

		return $cols;
	}


	/**
	 * @return array
	 */
	public function getSelectableColumns() {
		return array();
	}


    /**
     * @throws \srag\DIC\LearningProgressLookup\Exception\DICException
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
		$this->addColumn(self::plugin()->translate('table_label_action'), '', '150px', 'text-right');
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
		foreach ($this->getTableColumns() as $k => $v) {
			switch ($k) {
				case 'online':
					$this->tpl->setCurrentBlock('td');
					$this->tpl->setVariable('VALUE', ilUtil::img(srLearningProgressLookupModel::getOfflineStatusImageTag($a_set['online']), self::plugin()->translate("online_status_"
					                                                                                                                                       . $a_set['online'], '', [18,18])));
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

		self::dic()->ctrl()->setParameterByClass('srlearningprogresslookupstatusgui', 'course_ref_id', $a_set['ref_id']);
		$link_target = self::dic()->ctrl()->getLinkTargetByClass('srlearningprogresslookupstatusgui');

		$this->tpl->setCurrentBlock("cmd");
		$this->tpl->setVariable("CMD", $link_target);
		$this->tpl->setVariable("CMD_TXT", self::plugin()->translate('table_label_lookup'));
		$this->tpl->parseCurrentBlock();
		//$this->tpl->setVariable('ACTIONS', '&nbsp;');
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