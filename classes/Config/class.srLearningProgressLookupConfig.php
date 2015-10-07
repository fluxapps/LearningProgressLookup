<?php

require_once("./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/LearningProgressLookup/classes/Config/class.srLearningProgressLookupConfigFormGUI.php");


/**
 * Class srConfiguration
 */
class srLearningProgressLookupConfig extends ActiveRecord {

    /*const F_TRAINING_OBJ_CAT_REF_ID = 'training_obj_cat_ref_id';
	const F_CHANGE_LOCK = 'change_lock';
	const F_LAST_CRONJOB_DATE = 'last_cronjob_date';
	const F_SYSTEM_USER_ID = 'system_user_id';*/

    /**
     * @var array
     */
    protected static $cache = array();
    /**
     * @var array
     */
    protected static $cache_loaded = array();
    /**
     * @var bool
     */
    protected $ar_safe_read = false;

    public static function returnDbTableName() {
        return 'sr_lpl_config';
    }

    /**
     * @param $name
     *
     * @return string
     */
    public static function get($name) {
        if (! isset(self::$cache_loaded[$name])) {
            $obj = self::find($name);
            if ($obj === NULL) {
                self::$cache[$name] = NULL;
            } else {
                self::$cache[$name] = $obj->getValue();
            }
            self::$cache_loaded[$name] = true;
        }
        return self::$cache[$name];
    }
    /**
     * @param $name
     * @param $value
     *
     * @return null
     */
    public static function set($name, $value) {
        /**
         * @var $obj arConfig
         */
        $obj = self::findOrGetInstance($name);
        $obj->setValue($value);
        if (self::where(array( 'name' => $name ))->hasSets()) {
            $obj->update();
        } else {
            $obj->create();
        }
    }

    /**
     * @var string
     *
     * @db_has_field        true
     * @db_is_unique        true
     * @db_is_primary       true
     * @db_is_notnull       true
     * @db_fieldtype        text
     * @db_length           250
     */
    protected $name;
    /**
     * @var string
     *
     * @db_has_field        true
     * @db_fieldtype        text
     * @db_length           1000
     */
    protected $value;

    /**
     * @var int
     *
     * @db_has_field        true
     * @db_fieldtype        integer
     * @db_length           8
     */
    protected $conf_group= 0;

    /**
     * @return int
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * @param int $group
     */
    public function setGroup($group)
    {
        $this->group = $group;
    }

    /**
     * @param string $value
     */
    public function setValue($value) {
        $this->value = $value;
    }
    /**
     * @return string
     */
    public function getValue() {
        return $this->value;
    }
    /**
     * @param string $name
     */
    public function setName($name) {
        $this->name = $name;
    }
    /**
     * @return string
     */
    public function getName() {
        return $this->name;
    }


} 