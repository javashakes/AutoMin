<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * AutoMin Model
 *
 * @package     ExpressionEngine
 * @category    Module
 * @author      Matthew Kirkpatrick
 * @copyright   Copyright (c) 2020, Matthew Kirkpatrick
 * @link        https://github.com/javashakes
 */

// config
include(PATH_THIRD.'automin/config.php');

// include super model
if ( ! class_exists('Automin_model'))
{
    require_once(PATH_THIRD.'automin/model.automin.php');
}

class Automin_preferences_model extends Automin_model {

    // --------------------------------------------------------------------
    // PROPERTIES
    // --------------------------------------------------------------------

    /**
     * Package shortcut
     *
     * @var        integer
     * @access     private
     */
    private $package;

    /**
     * Settings
     *
     * @var        array
     * @access     private
     */
    private $settings = array();


    /**
     * Default settings
     *
     * @var        array    y|n
     * @access     private
     */
    private $_default_settings = array(
        'automin_enabled' => 'n',
        'caching_enabled' => 'n',
        'compress_html'   => 'n',
        'cache_path'      => '',
        'cache_url'       => ''
    );

    // --------------------------------------------------------------------
    // METHODS
    // --------------------------------------------------------------------

    /**
     * Constructor
     *
     * @access      public
     * @return      void
     */
    function __construct()
    {
        // Call parent constructor
        parent::__construct();

        // Initialize this model
        $this->initialize(
            AUTOMIN_PACKAGE . '_preferences',
            'site_id',
            array(
                'automin_enabled' => 'varchar(1) NOT NULL DEFAULT "' . $this->_default_settings['automin_enabled'] . '"',
                'caching_enabled' => 'varchar(1) NOT NULL DEFAULT "' . $this->_default_settings['caching_enabled'] . '"',
                'compress_html'   => 'varchar(1) NOT NULL DEFAULT "' . $this->_default_settings['compress_html'] . '"',
                'cache_path'      => 'varchar(255) NOT NULL DEFAULT "' . $this->_default_settings['cache_path'] . '"',
                'cache_url'       => 'varchar(255) NOT NULL DEFAULT "' . $this->_default_settings['cache_url'] . '"'
            )
        );

        $this->site_id = (int) ee()->config->item('site_id');
        $this->package = AUTOMIN_PACKAGE;
    }

    /**
     * Installs given table
     *
     * @access      public
     * @return      void
     */
    public function install()
    {
        parent::install();
    }

    /**
     * Set the settings
     *
     * @access     public
     * @return     none
     */
    public function set($settings = array(), $update = TRUE)
    {
        // Always fallback to default settings
        $this->settings = array_merge($this->_default_settings, $settings);

        // Config overrides
        foreach($this->settings as $k => $v) {
            if (ee()->config->item(AUTOMIN_PACKAGE . '_' . $k)) {
                $this->settings[$k] = ee()->config->item(AUTOMIN_PACKAGE . '_' . $k);
            }
        }

        // Create or update the settings for this site
        if ($update === FALSE) {
            ee()->db->insert(AUTOMIN_PACKAGE . '_preferences', $this->settings);
        } else {
            ee()->db->where('site_id', $this->site_id)
                ->update(AUTOMIN_PACKAGE . '_preferences', $this->settings);
        }

    }

    /**
     * Get settings
     *
     * @access     public
     * @return     mixed
     */
    public function get($key = NULL)
    {
        $this->settings = ee()->db->select('*')
           ->from(AUTOMIN_PACKAGE . '_preferences')
           ->where('site_id', $this->site_id)
           ->limit(1)
           ->get()
           ->row_array();

        // If no row was returned, make one for this site_id and set settings
        if (empty($this->settings)) {
            $this->set(array('site_id' => $this->site_id), FALSE);
        }

        // Config overrides
        foreach($this->settings as $k => $v) {
            if (ee()->config->item(AUTOMIN_PACKAGE . '_' . $k)) {
                $this->settings[$k] = ee()->config->item(AUTOMIN_PACKAGE . '_' . $k);
            }
        }

        return is_null($key)
            ? $this->settings
            : (isset($this->settings[$key]) ? $this->settings[$key] : NULL);
    }

    // --------------------------------------------------------------------
    
    /**
     * Magic getter
     */
    public function __get($key)
    {
        $key = '_'.$key;
        return isset($this->$key) ? $this->$key : NULL;
    }

}

/* End of file automin_preferences_model.php */
/* Location: /system/expressionengine/third_party/automin/libraries/automin_preferences_model.php */
