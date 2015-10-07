<?php

require_once('./Services/Tracking/classes/class.ilLPCollections.php');
require_once('./Services/Tracking/classes/class.ilLearningProgressBaseGUI.php');

/**
 * Class srLearningProgressCourseModel
 *
 * @author  Michael Herren <mh@studer-raimann.ch>
 * @version 1.0.0
 */
class srLearningProgressLookupModel {
	static $module_cache = array();

	public static function getCourses(array $options = array()) {
		global $ilDB, $rbacsystem;

		$options = self::mergeDefaultOptions($options);

        $sql = 'SELECT DISTINCT ref.ref_id,
				 obj.obj_id,
                 obj.title as course_title,
                 CONCAT_WS(" > ", gp.title, p.title) AS path,
                 crs_settings.activation_type AS online
				 FROM object_data as obj


                 INNER JOIN object_reference AS ref ON (ref.obj_id = obj.obj_id)
                 INNER JOIN crs_settings ON (crs_settings.obj_id = obj.obj_id)

                INNER JOIN tree AS t1 ON (ref.ref_id = t1.child)
                INNER JOIN object_reference ref2 ON (ref2.ref_id = t1.parent)
                INNER JOIN object_data AS p ON (ref2.obj_id = p.obj_id)

                LEFT JOIN tree AS t2 ON (ref2.ref_id = t2.child)
                LEFT JOIN object_reference AS ref3 ON (ref3.ref_id = t2.parent)
                LEFT JOIN object_data AS gp ON (ref3.obj_id = gp.obj_id)

                 WHERE ref.deleted IS NULL ';

		// fix for correct field name
		if(isset($options['filters']['course_title'])) {
			$options['filters']['obj.title'] = $options['filters']['course_title'];
			unset($options['filters']['course_title']);
		}

		$sql .= self::parseWhereQuery($options['filters']);
		$sql .= self::parseDefaultQueryOptions($options);

		$result = $ilDB->query($sql);
		$data = array();

		while($rec = $ilDB->fetchAssoc($result)) {
			if($rbacsystem->checkAccess("lp_other_users", $rec['ref_id'])) {
				$data[] = $rec;
			}
		}

		if($options['count']) {
			return count($data);
		} else {
			return $data;
		}
	}

	public static function getCourseUsers($course_ref_id, array $options = array()) {
		global $ilDB;

		$options = self::mergeDefaultOptions($options);

		if ($options['count']) {
			$sql = 'SELECT COUNT(obj_members.usr_id),';
		} else {
			$sql = 'SELECT obj_members.usr_id, ';
		}

		$sql .= "login, firstname, lastname, last_login FROM obj_members
				LEFT JOIN usr_data ON obj_members.usr_id = usr_data.usr_id
				WHERE obj_members.obj_id = ".$ilDB->quote(ilObject::_lookupObjId($course_ref_id),'integer')." ";

		// only parse the login filter!
		$sql .= self::parseWhereQuery($options['filters'], array('login'));
		$sql .= self::parseDefaultQueryOptions($options);

		$result = $ilDB->query($sql);
		if ($options['count']) {
			$rec = $ilDB->fetchAssoc($result);
			return $rec['count'];

		} else {
			$data = array();

			while($rec = $ilDB->fetchAssoc($result)) {
				$data[] = $rec;
			}
			return $data;
		}

	}

	public static function getUserProgresses($course_ref_id, array $options = array()) {
		global $ilDB;

		$options = self::mergeDefaultOptions($options);

		$modules = self::getCourseModules($course_ref_id);
		$module_obj_ids = array_map(function ($ar) {return $ar['obj_id'];}, $modules);

		$sql = "SELECT usr_id, obj_id, status FROM ut_lp_marks".
			" WHERE ".$ilDB->in("obj_id", $module_obj_ids, false, "integer");

		$set = $ilDB->query($sql);
		$res = array();
		while($rec = $ilDB->fetchAssoc($set)) {
			$res[$rec['usr_id']][$rec['obj_id']] = $rec;
		}

		return $res;
	}

	public static function getCourseModules($course_ref_id, $show_offline = true) {
		global $ilDB, $rbacsystem;

		if(isset(self::$module_cache[$course_ref_id])) {
			return self::$module_cache[$course_ref_id];
		}

		$obj_id = ilObject::_lookupObjId($course_ref_id);
		$collection = new ilLPCollections($obj_id);
		$items = ilLPCollections::_getPossibleItems($course_ref_id, $collection);

		$data = array();
		foreach($items as $item) {
			$object = ilObjectFactory::getInstanceByRefId($item);
			$online = true;
			if(method_exists($object, 'isOnline')) {
				if(!$object->isOnline()) {
					$online = false;
				}
			}

			// check if offline show is enabled and user has lp-other-users right
			if((!$show_offline && !$online) || !$rbacsystem->checkAccess("lp_other_users", $object->getRefId())) {
				continue;
			}

			$row = array(
				'obj_id' => $object->getId(),
				'ref_id' => $object->getRefId(),
				'title' => $object->getTitle(),
				'icon' => ilUtil::getTypeIconPath($object->getType(), $object->getId()),
				'online' => $online
			);
			$data[] = $row;
		}

		// sort alphabetic
		usort($data, function($a, $b) {
			return strcmp($a['title'], $b['title']);
		});

		self::$module_cache[$course_ref_id] = $data;
		return self::$module_cache[$course_ref_id];
	}

	public static function mergeDefaultOptions(array $options, array $defaults = array()) {

		$_options = (count($defaults) > 0)? $defaults : array(
			'filters' => array(),
			'permission_filters'=>array(),
			'sort' => array(),
			'limit' => array(),
			'count' => false,
		);
		return array_merge($_options, $options);
	}

	public static function parseWhereQuery($filters, $valid_params = false, $first = false) {
		global $ilDB;

		// allow filtering with *
		$sql = "";
		foreach($filters as $key => $value) {
			if($value != null) {

				if(is_array($valid_params) && !in_array($key, $valid_params)) {
					continue;
				}

				$sql .= ($first)? '' : ' AND ';
				if(!is_numeric($value) && !is_array($value)) {
					$sql .= $ilDB->like($key, 'text', "%".trim(str_replace("*","%",trim($value)), "%")."%");
				} else {
					$sql .= $key."=".$ilDB->quote($value, 'text');
				}
				$first = false;
			}
		}
		return $sql;
	}

	public static function parseDefaultQueryOptions($options) {
		$sql = "";
		if (isset($options['limit']['start']) && isset($options['limit']['end'])) {
			$sql .= " LIMIT ".$options['limit']['start'].", ".$options['limit']['end'];
		}

		if (isset($options['sort']['field']) && isset($options['sort']['direction'])) {
			$sql .= " ORDER BY ".$options['sort']['field']." ".$options['sort']['direction'];
		}

		return $sql;
	}

	public static function getProgressStatusRepresentation($status) {
		global $lng;

		$lng->loadLanguageModule('trac');
		return ilLearningProgressBaseGUI::_getStatusText($status);
	}

	public static function getProgressStatusImageTag($status) {
		return ilLearningProgressBaseGUI::_getImagePathForStatus($status);
	}

	public static function getOfflineStatusImageTag($status) {
		// magic constant mapping on correct stati
		$online_status_transform = ($status == 1)? 2 : 3;
		return ilLearningProgressBaseGUI::_getImagePathForStatus($online_status_transform);
	}
}