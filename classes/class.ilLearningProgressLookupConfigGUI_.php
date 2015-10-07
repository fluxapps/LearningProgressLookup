<?php
require_once('./Services/Component/classes/class.ilPluginConfigGUI.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/LearningProgressLookup/classes/class.ilLearningProgressLookupPlugin.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/LearningProgressLookup/classes/Config/class.srLearningProgressLookupConfigFormGUI.php');


/**
 * Class ilLearningProgressLookupConfigGUI
 *
 * @author  Michael Herren <mh@studer-raimann.ch>
 */
class ilLearningProgressLookupConfigGUI extends ilPluginConfigGUI {

    const CMD_DEFAULT = 'index';
    const CMD_SAVE = 'save';
    const CMD_CANCEL = 'cancel';
    /**
     * @var ilCtrl
     */
    protected $ctrl;
    /**
     * @var ilTemplate
     */
    protected $tpl;

    protected $pl;


    public function __construct() {
        global $tpl, $ilCtrl;

        $this->tpl = $tpl;
        $this->ctrl = $ilCtrl;
        $this->pl = ilLearningProgressLookupPlugin::getInstance();
    }


    public function performCommand($cmd) {
        if ($cmd == 'configure') {
            $cmd = self::CMD_DEFAULT;
        }
        switch ($cmd) {
            case self::CMD_DEFAULT:
            case self::CMD_SAVE;
            case self::CMD_CANCEL;
                $this->{$cmd}();
                break;
        }
    }

    public function index() {
        $config_form_gui = $this->initForm();
        $config_form_gui->fillForm();

        $this->tpl->setContent($config_form_gui->getHTML());
    }

    public function cancel() {
        $this->ctrl->redirect($this, self::CMD_DEFAULT);
    }

    protected function initForm() {
        return new srLearningProgressLookupConfigFormGUI($this);
    }

    protected function save() {
        $config_form_gui = $this->initForm();
        $config_form_gui->setValuesByPost();
        if ($config_form_gui->saveObject()) {
            ilUtil::sendSuccess($this->pl->txt("ui_lpl_saved_config"), true);
            $this->ctrl->redirect($this, self::CMD_DEFAULT);
        } else {
            ilUtil::sendFailure($this->pl->txt("ui_lpl_saved_failed_config"));
        }

        $this->tpl->setContent($config_form_gui->getHTML());
    }

}

?>