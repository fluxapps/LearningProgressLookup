<?php
require_once('./Services/Form/classes/class.ilPropertyFormGUI.php');
require_once('./Services/Form/classes/class.ilMultiSelectInputGUI.php');
require_once('./Services/Form/classes/class.ilRoleAutoCompleteInputGUI.php');
require_once('class.srLearningProgressLookupConfig.php');


/**
 * Class
 *
 * @author Michael Herren <mh@studer-raimann.ch>
 */
class srLearningProgressLookupConfigFormGUI extends ilPropertyFormGUI {

    /**
     * @var
     */
    protected $parent_gui;
    /**
     * @var  ilCtrl
     */
    protected $ctrl;

    /**
     * @param  $parent_gui
     */
    public function __construct($parent_gui) {
        global $ilCtrl;
        $this->parent_gui = $parent_gui;
        $this->ctrl = $ilCtrl;
        $this->pl = ilLearningProgressLookupPlugin::getInstance();
        $this->setFormAction($this->ctrl->getFormAction($this->parent_gui));
        $this->initForm();
    }


    /**
     * @param $field
     *
     * @return string
     */
    public function txt($field) {
        return $this->pl->txt('admin_form_' . $field);
    }


    protected function initForm() {
        global $rbacreview, $ilUser;

        $this->setTitle($this->txt('filter_label_title'));

        /*$access_role_config = new ilTextInputGUI($this->txt(ilTrainingProgramConfig::F_TRAINING_OBJ_CAT_REF_ID), ilTrainingProgramConfig::F_TRAINING_OBJ_CAT_REF_ID);
        $access_role_config->setRequired(true);
        $this->addItem($access_role_config);

	    $change_lock = new ilNumberInputGUI($this->txt(ilTrainingProgramConfig::F_CHANGE_LOCK), ilTrainingProgramConfig::F_CHANGE_LOCK);
	    $change_lock->setRequired(true);
	    $this->addItem($change_lock);

	    $last_cronjob = new ilNumberInputGUI($this->txt(ilTrainingProgramConfig::F_LAST_CRONJOB_DATE), ilTrainingProgramConfig::F_LAST_CRONJOB_DATE);
	    $last_cronjob->setRequired(true);
	    $this->addItem($last_cronjob);

	    $system_user_id = new ilNumberInputGUI($this->txt(ilTrainingProgramConfig::F_SYSTEM_USER_ID), ilTrainingProgramConfig::F_SYSTEM_USER_ID);
	    $system_user_id->setRequired(true);
	    $this->addItem($system_user_id);*/

	    $this->addCommandButtons();
    }


    public function fillForm() {
        $array = array();
        foreach ($this->getItems() as $item) {
            $this->getValuesForItem($item, $array);
        }
        $this->setValuesByArray($array);
    }

    /**
     * @param ilFormPropertyGUI $item
     * @param                   $array
     *
     * @internal param $key
     */
    private function getValuesForItem($item, &$array) {
        if (self::checkItem($item)) {
            $key = $item->getPostVar();
            $array[$key] = ilTrainingProgramConfig::get($key);
            if (self::checkForSubItem($item)) {
                foreach ($item->getSubItems() as $subitem) {
                    $this->getValuesForItem($subitem, $array);
                }
            }
        }
    }

    /**
     * @return bool
     */
    public function saveObject() {
        if (!$this->checkInput()) {
            return false;
        }
        foreach ($this->getItems() as $item) {
            $this->saveValueForItem($item);
        }

        return true;
    }


    /**
     * @param  ilFormPropertyGUI $item
     */
    private function saveValueForItem($item) {
        if (self::checkItem($item)) {
            $key = $item->getPostVar();
            ilTrainingProgramConfig::set($key, $this->getInput($key));

            if (self::checkForSubItem($item)) {
                foreach ($item->getSubItems() as $subitem) {
                    $this->saveValueForItem($subitem);
                }
            }
        }
    }


    /**
     * @param $item
     *
     * @return bool
     */
    public static function checkForSubItem($item) {
        return !$item instanceof ilFormSectionHeaderGUI AND !$item instanceof ilMultiSelectInputGUI;
    }


    /**
     * @param $item
     *
     * @return bool
     */
    public static function checkItem($item) {
        return !$item instanceof ilFormSectionHeaderGUI;
    }


    protected function addCommandButtons() {
        $this->addCommandButton('save', $this->pl->txt('admin_form_button_save'));
        $this->addCommandButton('cancel', $this->pl->txt('admin_form_button_cancel'));
    }
}