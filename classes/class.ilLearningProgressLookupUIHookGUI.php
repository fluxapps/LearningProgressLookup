<?php
/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */
require_once('./Services/UIComponent/classes/class.ilUIHookPluginGUI.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/LearningProgressLookup/classes/class.ilLearningProgressLookupPlugin.php');

/**
 * Class ilLearningProgressLookupUIHookGUI
 *
 * @author  Michael Herren <mh@studer-raimann.ch>
 * @version 1.0.0
 */
class ilLearningProgressLookupUIHookGUI extends ilUIHookPluginGUI {

	/**
	 * @param       $a_comp
	 * @param       $a_part
	 * @param array $a_par
	 *
	 */
	/*function modifyGUI($a_comp, $a_part, $a_par = array()) {
		global $ilAccess, $ilTabs, $ilCtrl;

		if ($a_part == 'tabs') {
			if (ilObject::_lookupType($_GET['ref_id'], true) == 'crs' AND $ilAccess->checkAccess("write", "write", $_GET['ref_id'])
			) {
				if (!self::isLoaded('course_training_curriculum')) {
					$plugin_instance = ilTrainingProgramPlugin::getInstance();

					$ilTabsGUI = $a_par['tabs'];

					if ($ilTabsGUI->hasTabs()) {
						$ilCtrl->setParameterByClass('iltestreportingmarkgui', 'ref_id', $_GET['ref_id']);

						$ilTabsGUI->target[] = array(
							"text" => $plugin_instance->txt('mark_test'),
							"link" => $ilCtrl->getLinkTargetByClass(array( 'iluipluginroutergui', 'ilTestReportingMarkGUI' ), 'showForm'),
							"dir_text" => true,
							"id" => 'trepm_form_gui',
							"cmdClass" => array( 'iltestreportingmarkgui' )
						);
						if ($ilCtrl->getCmdClass() == 'iltestreportingmarkgui') {
							$ilTabsGUI->setTabActive('trepm_form_gui');
						}
					}

					self::setLoaded('test_reporting_mark_add_tab');
				}
			}
		}
	}*/
}

?>
