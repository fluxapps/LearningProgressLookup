<?php
require_once __DIR__ . "/../vendor/autoload.php";
use srag\DIC\LearningProgressLookup\DICTrait;

/**
 * Class ilLearningProgressLookupConfigGUI
 *
 * @author  Michael Herren <mh@studer-raimann.ch>
 */
class ilLearningProgressLookupConfigGUI extends ilPluginConfigGUI {

    use DICTrait;
    
    const PLUGIN_CLASS_NAME = ilLearningProgressLookupPlugin::class;

	const CMD_DEFAULT = 'index';
	const CMD_SAVE = 'save';
	const CMD_CANCEL = 'cancel';


    /**
     * ilLearningProgressLookupConfigGUI constructor.
     */
    public function __construct() {
	}


    /**
     * @param $cmd
     */
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


    /**
     *
     */
    public function index() {
		$config_form_gui = $this->initForm();
		$config_form_gui->fillForm();

		self::dic()->ui()->mainTemplate()->setContent($config_form_gui->getHTML());
	}


    /**
     *
     */
    public function cancel() {
		self::dic()->ctrl()->redirect($this, self::CMD_DEFAULT);
	}


    /**
     * @return srLearningProgressLookupConfigFormGUI
     */
    protected function initForm() {
		return new srLearningProgressLookupConfigFormGUI($this);
	}


    /**
     * @throws \srag\DIC\LearningProgressLookup\Exception\DICException
     */
    protected function save() {
		$config_form_gui = $this->initForm();
		$config_form_gui->setValuesByPost();
		if ($config_form_gui->saveObject()) {
			ilUtil::sendSuccess(self::plugin()->translate("message_saved_config"), true);
			self::dic()->ctrl()->redirect($this, self::CMD_DEFAULT);
		} else {
			ilUtil::sendFailure(self::plugin()->translate("message_saved_failed_config"));
		}

		self::dic()->ui()->mainTemplate()->setContent($config_form_gui->getHTML());
	}
}

?>