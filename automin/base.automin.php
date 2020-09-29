<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * AutoMin Base Class
 *
 * @package     ExpressionEngine
 * @category    Module
 * @author      Matthew Kirkpatrick
 * @copyright   Copyright (c) 2020, Matthew Kirkpatrick
 * @link        https://github.com/javashakes
 */

// config
include(PATH_THIRD.'automin/config.php');

abstract class Automin_base {

    // --------------------------------------------------------------------
    // PROPERTIES
    // --------------------------------------------------------------------

    /**
     * Add-on version
     *
     * @var        string
     * @access     public
     */
    public $version;

    // --------------------------------------------------------------------

    /**
     * Package name
     *
     * @var        string
     * @access     protected
     */
    protected $package = AUTOMIN_PACKAGE;

    /**
     * This add-on's info based on setup file
     *
     * @access      private
     * @var         object
     */
    protected $info;

    /**
     * Main class shortcut
     *
     * @var        string
     * @access     protected
     */
    protected $class_name;

    /**
     * Site id shortcut
     *
     * @var        int
     * @access     protected
     */
    protected $site_id;

    /**
     * Libraries used
     *
     * @var        array
     * @access     protected
     */
    protected $libraries = array();

    /**
     * Models used
     *
     * @var        array
     * @access     protected
     */
    protected $models = array(
        'automin_preferences_model'
    );

    // --------------------------------------------------------------------
    // METHODS
    // --------------------------------------------------------------------

    /**
     * Constructor
     *
     * @access     public
     * @return     void
     */
    public function __construct()
    {
        // -------------------------------------
        //  Set info and version
        // -------------------------------------
        $this->info = ee('App')->get($this->package);
        $this->version = $this->info->getVersion();

        // -------------------------------------
        //  Load helper, libraries and models
        // -------------------------------------
        ee()->load->helper($this->package);
        ee()->load->library($this->libraries);
        ee()->load->model($this->models);

        // -------------------------------------
        //  Class name shortcut
        // -------------------------------------
        $this->class_name = ucfirst($this->package);

        // -------------------------------------
        //  Get site shortcut
        // -------------------------------------
        $this->site_id = (int) ee()->config->item('site_id');
    }

    // --------------------------------------------------------------------

}

/* End of file base.automin.php */
/* Location: ./system/expressionengine/third_party/automin/base.automin.php */
