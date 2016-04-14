<?php

/**
 * ilLearningProgressLookupAccess
 *
 * @author   Michael Herren <mh@studer-raimann.ch>
 *
 * @version  1.0.0
 *
 */
class ilLearningProgressLookupAccess {

	public static $ALLOWED_LOOKUP_ROLE_IDS = array( 2 );
	protected static $instance;


	public static function getInstance() {
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}

		return self::$instance;
	}


	public function hasCurrentUserViewPermission() {
		global $ilUser, $rbacreview;

		/*foreach(self::$ALLOWED_LOOKUP_ROLE_IDS as $role_id) {
			if($rbacreview->isAssigned($ilUser->getId(), $role_id)) {
				return true;
			}
		}
		return false;*/

		// we do only display data when the user has certain permissions. Additional checking is not neccessary
		return true;
	}


	public function hasCurrentUserStatusPermission($ref_id) {
		global $rbacsystem;

		$object_type = ilObjectFactory::getTypeByRefId($ref_id);
		if ($rbacsystem->checkAccess("edit_learning_progress", $ref_id) && $object_type == 'crs') {
			return true;
		}

		return false;
	}
}
