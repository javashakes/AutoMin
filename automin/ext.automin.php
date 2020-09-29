<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * AutoMin Extension
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

class Automin_ext extends Automin_base {

    // --------------------------------------------------------------------
    // PROPERTIES
    // --------------------------------------------------------------------

    /**
     * Do settings exist?
     *
     * @var        string   y|n
     * @access     public
     */
    public $settings_exist = 'n';

    /**
     * Required?
     *
     * @var        array
     * @access     public
     */
    public $required_by = array('module');

    // --------------------------------------------------------------------
    // METHODS
    // --------------------------------------------------------------------

    /**
     * Constructor
	 *
     * @param  mixed, Settings array or empty string if none exist.
     * @return void
	 *
     */
    public function __construct($settings = array())
    {
        // Get global instance
        parent::__construct();

        // Force settings array
        if ( ! is_array($settings))
        {
            $settings = array();
        }

        $this->settings = ee()->automin_preferences_model->get();
    }

	// ------------------------------------------------------------------------

    /**
     * Hook for processing template output.
	 *
     * @param string  $final_template The template markup
     * @param bool    $is_partial     Is the template an embed?
     * @param int     $site_id        Site ID
     * @return string                 The final template string
	 *
     */
    public function template_post_parse($final_template, $is_partial, $site_id) {

        $output = $final_template;

        // Prior output?
        if (isset(ee()->extensions->last_call)
            && ee()->extensions->last_call) {
            $output = ee()->extensions->last_call;
        }

        // bail out early if disabled
        if (   $this->settings['automin_enabled'] == 'n'
            || $this->settings['compress_html'] == 'n' ) {
            return $output;
        }

        // Minify
        if ( ! $is_partial) {

            $data_length_before = strlen($output) / 1024;
            require_once('libraries/tiny-html-minifier/src/TinyMinify.php');
            $output = TinyMinify::html($output, $options = [
                'collapse_whitespace' => true,
                'disable_comments' => true,
            ]);
            $data_length_after = strlen($output) / 1024;

            // Log results
            $data_savings_kb = $data_length_before - $data_length_after;
            $data_savings_percent = $data_savings_kb / $data_length_before;
            $data_savings_message = sprintf(
                'AutoMin Module HTML Compression: Before: %1.0fkb / After: %1.0fkb / Data reduced by %1.2fkb or %1.2f%%',
                $data_length_before,
                $data_length_after,
                $data_savings_kb,
                $data_savings_percent
            );
            ee()->TMPL->log_item($data_savings_message);

        }

        return $output;

    }

    // --------------------------------------------------------------------

}

/* End of file ext.automin.php */
/* Location: /system/expressionengine/third_party/automin/ext.automin.php */
