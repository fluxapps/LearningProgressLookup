<?php

//require_once('./Services/Tracking/classes/class.ilLPCollections.php');
require_once('./Services/Tracking/classes/class.ilLearningProgressBaseGUI.php');

/**
 * Class srLearningProgressCourseModel
 *
 * UPDATE HINT: For ILIAS 5 the ilLPCollections class needs to be replaced with ilLPCollectionOfRepositoryObjects
 *
 * @author  Michael Herren <mh@studer-raimann.ch>
 * @version 1.0.0
 */
class srLearningProgressLookupModel {

	static $module_cache = array();


	/**
	 * @param array $options
	 * @return array|int
	 */
	public static function getCourses(array $options = array()) {
		global $ilDB, $rbacsystem;

		$options = self::mergeDefaultOptions($options);

		// TODO: for better performance it would be good to pack the "edit_learning_progress" access check into the query (using rbac_pa)
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

		$sql .= self::parseWhereQuery($options['filters']);
		$sql .= self::parseDefaultQueryOptions($options);

		$result = $ilDB->query($sql);
		$data = array();

		while ($rec = $ilDB->fetchAssoc($result)) {
			// only display, when user has edit_learning_progress right!
			if ($rbacsystem->checkAccess("edit_learning_progress", $rec['ref_id'])) {
				$data[] = $rec;
			}
		}

		if ($options['count']) {
			return count($data);
		} else {
			return $data;
		}
	}


	/**
	 * @param $course_ref_id
	 * @param array $options
	 * @return array
	 */
	public static function getCourseUsers($course_ref_id, array $options = array()) {
		global $ilDB;

		$options = self::mergeDefaultOptions($options);

		if ($options['count']) {
			$sql = 'SELECT COUNT(usr_id) as count ';
		} else {
			$sql = 'SELECT usr_id, login, firstname, lastname, last_login ';
		}

		$sql .= "FROM usr_data WHERE usr_data.usr_id <> " . ANONYMOUS_USER_ID . " " . "AND usr_data.usr_id IN (SELECT DISTINCT ud.usr_id "
		        . "FROM usr_data ud join rbac_ua ON (ud.usr_id = rbac_ua.usr_id) " . "JOIN object_data od ON (rbac_ua.rol_id = od.obj_id) "
		        . "WHERE od.title LIKE 'il_crs_%_" . $ilDB->quote($course_ref_id) . "') ";

		if (strpos($options['filters']['login'], ',') !== false) {
			$options['filters']['login'] = explode(',', $options['filters']['login']);
		}

		// only parse the login filter!
		$sql .= self::parseWhereQuery($options['filters'], array( 'login' ));
		$sql .= self::parseDefaultQueryOptions($options);

		$result = $ilDB->query($sql);
		if ($options['count']) {
			$rec = $ilDB->fetchAssoc($result);

			return $rec['count'];
		} else {
			$data = array();

			while ($rec = $ilDB->fetchAssoc($result)) {
				$data[$rec['usr_id']] = $rec;
			}

			return $data;
		}
	}


	/**
	 * @param $course_ref_id
	 * @param array $user_ids
	 * @param array $options
	 * @return array
	 */
	public static function getUserProgresses($course_ref_id, array $user_ids = array(), array $options = array()) {
		global $ilDB;

		$options = self::mergeDefaultOptions($options);

		$modules = self::getCourseModules($course_ref_id, $options);
		$module_obj_ids = array_map(function ($ar) { return $ar['obj_id']; }, $modules);

		$sql = "SELECT usr_id, obj_id, status FROM ut_lp_marks
				WHERE " . $ilDB->in("obj_id", $module_obj_ids, false, "integer") . " AND " . $ilDB->in("usr_id", $user_ids, false, "integer");

		$set = $ilDB->query($sql);
		$res = array();
		while ($rec = $ilDB->fetchAssoc($set)) {
			$res[$rec['usr_id']][$rec['obj_id']] = $rec;
		}

		return $res;
	}


	/**
	 * @param $course_ref_id
	 * @param array $options
	 * @return mixed
	 */
	public static function getCourseModules($course_ref_id, array $options = array()) {
		global $ilDB, $rbacsystem;

		if (isset(self::$module_cache[$course_ref_id])) {
			return self::$module_cache[$course_ref_id];
		}

		$options = self::mergeDefaultOptions($options);

		$show_offline = false;
		if (isset($options['filters']['offline']) && $options['filters']['offline'] == 1) {
			$show_offline = true;
		}

		// find modules recursive
		$data = self::findCourseModules($course_ref_id, $show_offline);

		// sort alphabetic
		usort($data, function ($a, $b) {
			return strcmp($a['title'], $b['title']);
		});

		self::$module_cache[$course_ref_id] = $data;

		return self::$module_cache[$course_ref_id];
	}


	/**
	 * @param $ref_id
	 * @param $show_offline
	 * @return array
	 */
	public static function findCourseModules($ref_id, $show_offline) {
		global $rbacsystem;

		$obj_id = ilObject::_lookupObjId($ref_id);
		$collection = new ilLPCollections($obj_id);
		$items = ilLPCollections::_getPossibleItems($ref_id, $collection);

		$data = array();
		foreach ($items as $item) {
			$object = ilObjectFactory::getInstanceByRefId($item);

			$online = true;
			// determine if something is online in ILIAS
			if (method_exists($object, 'isOnline')) {
				if (!$object->isOnline()) {
					$online = false;
				}
			} else {
				if (method_exists($object, 'getOnline')) {
					if (!$object->getOnline()) {
						$online = false;
					}
				} else {
					if (method_exists($object, '_lookupOnline')) {
						if (!$object->_lookupOnline()) {
							$online = false;
						}
					}
				}
			}

			// check if offline show is enabled and user has lp-other-users right
			if (!$show_offline && !$online || !$rbacsystem->checkAccess("edit_learning_progress", $object->getRefId())) {
				continue;
			}

			$row = array(
				'obj_id'  => $object->getId(),
				'ref_id'  => $object->getRefId(),
				'title'   => $object->getPresentationTitle(),
				'icon'    => ilUtil::getTypeIconPath($object->getType(), $object->getId()),
				'offline' => !$online,
			);
			$data[] = $row;
		}

		return $data;
	}


	/**
	 * @param array $options
	 * @param array $defaults
	 * @return array
	 */
	public static function mergeDefaultOptions(array $options, array $defaults = array()) {

		$_options = (count($defaults) > 0) ? $defaults : array(
			'filters'            => array(),
			'permission_filters' => array(),
			'sort'               => array(),
			'limit'              => array(),
			'count'              => false,
		);

		return array_merge($_options, $options);
	}


	/**
	 * @param $filters
	 * @param bool $valid_params
	 * @param bool $first
	 * @param string $op
	 * @return string
	 */
	public static function parseWhereQuery($filters, $valid_params = false, $first = false, $op = "AND") {
		global $ilDB;

		// allow filtering with *
		$sql = "";
		foreach ($filters as $key => $value) {
			if ($value != null) {

				if (is_array($valid_params) && !in_array($key, $valid_params)) {
					continue;
				}

				// parse options as array
				if (is_array($value)) {
					$other_sql = '';
					$first = true;
					foreach ($value as $split) {
						if ($split != null && $split != '') {
							$other_sql .= ($first) ? '' : ' OR ';
							if (!is_numeric($split) && !is_array($split)) {
								$other_sql .= $ilDB->like($key, 'text', "%" . trim(str_replace("*", "%", trim($split)), "%") . "%");
							} else {
								$other_sql .= $key . "=" . $ilDB->quote($split, 'text');
							}
							$first = false;
						}
					}

					$sql .= $op . ' (' . $other_sql . ') ';

					if ($other_sql != '') {
						$first = false;
					}
					continue;
				}

				$sql .= ($first) ? '' : ' ' . $op . ' ';
				if (!is_numeric($value) && !is_array($value)) {
					$sql .= $ilDB->like($key, 'text', "%" . trim(str_replace("*", "%", trim($value)), "%") . "%");
				} else {
					$sql .= $key . "=" . $ilDB->quote($value, 'text');
				}
				$first = false;
			}
		}

		return $sql;
	}


	/**
	 * @param $options
	 * @return string
	 */
	public static function parseDefaultQueryOptions($options) {
		$sql = "";
		if (isset($options['sort']['field']) && isset($options['sort']['direction'])) {
			$sql .= " ORDER BY " . $options['sort']['field'] . " " . $options['sort']['direction'];
		}

		if (isset($options['limit']['start']) && isset($options['limit']['end'])) {
			$sql .= " LIMIT " . $options['limit']['start'] . ", " . $options['limit']['end'];
		}

		return $sql;
	}


	/**
	 * @param $status
	 * @return string
	 */
	public static function getProgressStatusRepresentation($status) {
		global $lng;

		$lng->loadLanguageModule('trac');

		return ilLearningProgressBaseGUI::_getStatusText($status);
	}


	/**
	 * @param $status
	 * @return string
	 */
	public static function getProgressStatusImageTag($status) {
		return ilLearningProgressBaseGUI::_getImagePathForStatus($status);
	}


	/**
	 * @param $status
	 * @return string
	 */
	public static function getOfflineStatusImageTag($status) {
		// magic constant mapping on correct stati
		$online_status_transform = ($status == 1) ? 2 : 3;

		return ilLearningProgressBaseGUI::_getImagePathForStatus($online_status_transform);
	}
}