<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * AutoMin Module Control Panel File
 *
 * @package     ExpressionEngine
 * @category    Module
 * @author      Matthew Kirkpatrick
 * @copyright   Copyright (c) 2020, Matthew Kirkpatrick
 * @link        https://github.com/javashakes
 */

// include base class
if ( ! class_exists('Automin_base'))
{
    require_once(PATH_THIRD.'automin/base.automin.php');
}

class Automin_mcp extends Automin_base {

    // --------------------------------------------------------------------
    // PROPERTIES
    // --------------------------------------------------------------------
    
    /**
     * Data array for views
     *
     * @var        array
     * @access     private
     */
    private $data = array();

    /**
     * View heading
     *
     * @var        string
     * @access     private
     */
    private $heading;

    /**
     * View breadcrumb
     *
     * @var        array
     * @access     private
     */
    private $crumb = array();

    // --------------------------------------------------------------------
    // METHODS
    // --------------------------------------------------------------------
        
    /**
     * Constructor
     *
     * @access public
     * @return void
     */
    public function __construct() {

        parent::__construct();

        // --------------------------------------
        // Load settings
        // --------------------------------------
        $this->settings =& ee()->automin_preferences_model->get();

    }

    /**
     * Module index page
     * 
     * @access public
     */
    public function index() {

        // Form definition
        $this->data['sections'][0] = array(
            array(
                'title'  => lang('settings_automin_enabled_title'),
                'desc'   => lang('settings_automin_enabled_description'),
                'fields' => array(

                    'automin_enabled' => array(
                        'type'           => 'yes_no',
                        'value'          => $this->settings['automin_enabled'],
                        'required'       => TRUE
                    )

                )
            ),
            array(
                'title'  => lang('settings_compress_html_title'),
                'desc'   => lang('settings_compress_html_description'),
                'fields' => array(

                    'compress_html' => array(
                        'type'           => 'yes_no',
                        'value'          => $this->settings['compress_html'],
                        'required'       => TRUE
                    )

                )
            ),
            array(
                'group'  => 'feature_toggles',
                'title'  => lang('settings_caching_enabled_title'),
                'desc'   => lang('settings_caching_enabled_description'),
                'fields' => array(

                    'caching_enabled' => array(
                        'type'           => 'yes_no',
                        'value'          => $this->settings['caching_enabled'],
                        'required'       => TRUE,
                        'group_toggle'   => array(
                            'y' => 'cache_paths'
                        )
                    )

                )
            ),
            array(
                'group'  => 'cache_paths',
                'title'  => lang('settings_cache_directory_path_title'),
                'desc'   => lang('settings_cache_directory_path_description'),
                'fields' => array(

                    'cache_path' => array(
                        'type'         => 'text',
                        'value'        => $this->settings['cache_path'],
                        'required'     => TRUE,
                        'placeholder'  => lang('settings_cache_directory_path_placeholder')
                    )

                )
            ),
            array(
                'group'  => 'cache_paths',
                'title'  => lang('settings_cache_directory_url_title'),
                'desc'   => lang('settings_cache_directory_url_description'),
                'fields' => array(

                    'cache_url' => array(
                        'type'         => 'text',
                        'value'        => $this->settings['cache_url'],
                        'required'     => TRUE,
                        'placeholder'  => lang('settings_cache_directory_url_placeholder')
                    )

                )
            )
        );

        // Final view variables we need to render the form
        $this->data += array(
            'base_url'              => $this->mcp_url('save_settings'),
            'cp_page_title'         => lang('settings_title'),
            'save_btn_text'         => 'btn_save_settings',
            'save_btn_text_working' => 'btn_saving'
        );

        // --------------------------------------
        // Set breadcrumb
        // --------------------------------------
        $this->set_cp_crumb($this->mcp_url(), lang('automin_module_name'));

        ee()->cp->add_js_script(array(
            'file' => array('cp/form_group'),
        ));

        return $this->view('form');

    }

    /**
     * Save settings
     * 
     * @access public
     * @return      void
     */
    public function save_settings() {

        // Update Settings
        ee()->automin_preferences_model->set(
            array(
                'automin_enabled' => ee()->input->post('automin_enabled'),
                'caching_enabled' => ee()->input->post('caching_enabled'),
                'compress_html'   => ee()->input->post('compress_html'),
                'cache_path'      => ee()->input->post('cache_path'),
                'cache_url'       => ee()->input->post('cache_url')
            )
        );

        // feedback
        ee('CP/Alert')->makeInline('shared-form')
            ->asSuccess()
            ->withTitle(lang('settings_update_success'))
            ->defer();

        // back home
        ee()->functions->redirect($this->mcp_url());

    }

    // --------------------------------------------------------------------

    /**
     * Return an MCP URL
     *
     * @access     private
     * @param      string
     * @param      mixed     [array|string]
     * @param      bool
     * @return     mixed
     */
    private function mcp_url($path = NULL, $extra = NULL, $obj = FALSE)
    {
        // Base settings
        $segments = array('addons', 'settings', $this->package);

        // Add method to segments, of given
        if (is_string($path)) $segments[] = $path;
        if (is_array($path)) $segments = array_merge($segments, $path);

        // Create the URL
        $url = ee('CP/URL', implode('/', $segments));

        // Add the extras to it
        if ( ! empty($extra))
        {
            // convert to array
            if ( ! is_array($extra)) parse_str($extra, $extra);

            // And add to the url
            $url->addQueryStringVariables($extra);
        }

        // Return it
        return ($obj) ? $url : $url->compile();
    }

    /**
     * Set cp var
     *
     * @access     private
     * @param      string
     * @param      string
     * @return     void
     */
    private function set_cp_var($key, $val)
    {
        ee()->view->$key = $val;

        if ($key == 'cp_page_title')
        {
            $this->heading = $val;
            $this->data[$key] = $val;
        }
    }

    /**
     * Set cp breadcrumb
     *
     * @access     private
     * @param      string
     * @param      string
     * @return     void
     */
    private function set_cp_crumb($url, $text)
    {
        $this->crumb[$url] = $text;
    }

    /**
     * View add-on page
     *
     * @access     protected
     * @param      string
     * @return     string
     */
    private function view($file)
    {
        // -------------------------------------
        //  Main page header
        // -------------------------------------
        ee()->view->header = array('title' => $this->info->getName());

        // -------------------------------------
        //  Return the view
        // -------------------------------------

        $view = array(
            'heading' => $this->heading,
            'breadcrumb' => $this->crumb,
            'body' => ee('View')->make($this->package.':'.$file)->render($this->data)
        );

        return $view;
    }

    // --------------------------------------------------------------------

}
/* End of file mcp.automin.php */
/* Location: /system/expressionengine/third_party/automin/mcp.automin.php */
