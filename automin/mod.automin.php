<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * AutoMin Module Front End File
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

class Automin extends Automin_base {

    // --------------------------------------------------------------------
    // PROPERTIES
    // --------------------------------------------------------------------
    
    /**
     * Return data
     *
     * @access public
     * @var    string
     */
    public $return_data = '';

    // --------------------------------------------------------------------

    /**
     * Constants for different markup types
     *
     * @var    string
    */
    const MARKUP_TYPE_JS   = 'js';
    const MARKUP_TYPE_CSS  = 'css';
    const MARKUP_TYPE_LESS = 'less';

    // --------------------------------------------------------------------
    // METHODS
    // --------------------------------------------------------------------
    
    /**
     * Constructor
     *
     * @access public
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->settings =& ee()->automin_preferences_model->get();
    }
    
    /**
     * exp:automin:css
     * Parses both LESS and CSS files
     *
     * @access      public
     * @return      string
    */
    public function css() {
        $this->_write_log('Processing CSS/LESS');

        return $this->return_data = $this->_process_markup(
            ee()->TMPL->tagdata,
            self::MARKUP_TYPE_CSS
        );
    }

    /**
     * exp:automin:js
     * Parses JS files
     *
     * @access      public
     * @return      string
    */
    public function js() {
        $this->_write_log('Processing JS');

        return $this->return_data = $this->_process_markup(
            ee()->TMPL->tagdata,
            self::MARKUP_TYPE_JS
        );
    }

    // --------------------------------------------------------------------
    // METHODS: PRIVATE
    // --------------------------------------------------------------------
    
    /**
     * Main processing routine, to be used for all types
     *
     * @param  $markup
     * @param  $markup_type One of the MARKUP_TYPE_X values
     * @return string       The new markup
    */
    private function _process_markup($markup, $markup_type) {
        
        // bail out early if disabled
        if (   $this->settings['automin_enabled'] == 'n'
            || $this->settings['caching_enabled'] == 'n' ) {
            return $markup;
        }

        // Gather information
        $markup = (empty($markup)) ? ee()->TMPL->tagdata : $markup;
        $filename_array = $this->_extract_filenames($markup, $markup_type);
        $filename_array = $this->_prep_filenames($filename_array);
        $last_modified  = $this->_find_last_modified_timestamp($filename_array);

        // File Extension
        // LESS files should have a .css extension
        $extension = ($markup_type == self::MARKUP_TYPE_LESS) ? self::MARKUP_TYPE_CSS : $markup_type;
        $cache_key = md5($markup) . '.' . $extension;

        // Fetch and validate cache
        $cache_filename = $this->_fetch_cache(
            $cache_key, 
            $markup, 
            $last_modified
        );
        
        // Output cache file, if valid
        if (FALSE !== $cache_filename) {
            $this->_write_log("Cache found and valid");
            return $this->_format_output($cache_filename, $last_modified, $markup_type);
        }

        // Combine files, parse @imports if appropriate
        $combined_file_data = $this->_combine_files(
            $filename_array,
            ($markup_type == self::MARKUP_TYPE_CSS
                || $markup_type == self::MARKUP_TYPE_LESS)
        );

        // If we couldn't read some files, return original tags
        if (FALSE === $combined_file_data) {
            $this->_write_log("ERR||: One or more of your files couldn't be read.");
            return $markup;
        }
        
        // Attempt compilation and compression
        $data_length_before = strlen($combined_file_data) / 1024;
        $combined_file_data = $this->_compile_and_compress(
            $combined_file_data, 
            $markup_type
        );
        $data_length_after = strlen($combined_file_data) / 1024;

        // Log the savings
        $data_savings_kb = $data_length_before - $data_length_after;
        $data_savings_percent = ($data_savings_kb / $data_length_before) * 100;
        $data_savings_message = sprintf(
            '(%s Compression) Before: %1.0fkb / After: %1.0fkb / Data reduced by %1.2fkb or %1.2f%%',
            strtoupper($markup_type),
            $data_length_before,
            $data_length_after,
            $data_savings_kb,
            $data_savings_percent
        );
        $this->_write_log($data_savings_message);

        // If compilation fails, return original tags
        if (FALSE === $combined_file_data) {
            $this->_write_log("ERR||: Compilation failed. Perhaps you have a syntax error?");
            return $markup;
        }


        // Cache output
        $cache_result = $this->_write_cache($cache_key, $combined_file_data);

        // If caching failed, return original tags
        if (FALSE === $cache_result) {
            $this->_write_log("ERR||: Caching is disabled or we were unable to write to your cache directory.");
            return $markup;
        }
        
        // Return the markup output
        return $this->_format_output($cache_result, $last_modified, $markup_type);

    }

    /**
     * Compress and compile (if necessary) the code.
     * @param string $code
     * @param string $markup_type One of the MARKUP_TYPE_X values
     * @return mixed FALSE if failure, string if success
    */
    private function _compile_and_compress($code, $markup_type) {

        @ini_set('memory_limit', '50M');
        @ini_set('memory_limit', '128M');
        @ini_set('memory_limit', '256M');
        @ini_set('memory_limit', '512M');
        @ini_set('memory_limit', '1024M');

        try {
            
            switch($markup_type) {

                case self::MARKUP_TYPE_LESS:

                    // Compile with LESS
                    require_once('libraries/lessphp/lessc.inc.php');
                    $less = new lessc;
                    $code = $less->compile($code);

                    // Compress CSS
                    require_once('libraries/YUICompressor/YUICompressor.php');
					Minify_YUICompressor::$jarFile = PATH_THIRD . 'automin/libraries/YUICompressor/lib/yuicompressor-2.4.8.jar';
					Minify_YUICompressor::$tempDir = '/tmp';
					$code = Minify_YUICompressor::minifyCss(
						$code,
						array(
							'line-break' => 1000
						)
					);

                    break;

                case self::MARKUP_TYPE_CSS:

                    // Compress CSS
                    require_once('libraries/YUICompressor/YUICompressor.php');
					Minify_YUICompressor::$jarFile = PATH_THIRD . 'automin/libraries/YUICompressor/lib/yuicompressor-2.4.8.jar';
					Minify_YUICompressor::$tempDir = '/tmp';
					$code = Minify_YUICompressor::minifyCss(
						$code,
						array(
							'line-break' => 1000
						)
					);
                        
                    break;

                case self::MARKUP_TYPE_JS:
                    
                    // Compile JS
                    require_once('libraries/YUICompressor/YUICompressor.php');
					Minify_YUICompressor::$jarFile = PATH_THIRD . 'automin/libraries/YUICompressor/lib/yuicompressor-2.4.8.jar';
					Minify_YUICompressor::$tempDir = '/tmp';
					$code = Minify_YUICompressor::minifyJs(
						$code,
						array(
							'nomunge' => true,
							'line-break' => 1000
						)
					);

                    break;

            }

        } catch (Exception $e) {
            exit($e->getMessage());
            $this->_write_log('Compilation Exception: ' . $e->getMessage());
            return FALSE;

        }

        return $code;

    }

    /**
     * Formats the output into valid markup
     * @param  string  $cache_filename The url path to the cache file.
     * @param  integer $last_modified Timestamp of the latest-modified file
     * @param  string  $markup_type One of the MARKUP_TYPE_X values
     * @return string
    */
    private function _format_output($cache_filename, $last_modified, $markup_type) {

        $output = '';
        
        // Append modified time to the filename
        $cache_filename = $cache_filename . '?modified=' . $last_modified;

        // Format attributes
        $tag_attributes = $this->_fetch_colon_params('attribute');
        $attributes_string = '';
        foreach($tag_attributes as $key=>$value) {
            $attributes_string .= " $key=\"$value\" ";
        }

        // Create tag
        switch($markup_type) {

            case self::MARKUP_TYPE_LESS:
            case self::MARKUP_TYPE_CSS:
                $output = sprintf(
                    '<link href="%s" %s>', 
                    $cache_filename, 
                    $attributes_string
                );
                break;

            case self::MARKUP_TYPE_JS:
                $output = sprintf(
                    '<script src="%s" %s></script>', 
                    $cache_filename, 
                    $attributes_string
                );
                break;

        }

        return $output;

    }

    /**
     * Returns a string of all the files combined. If a file cannot be read,
     * this function will return FALSE.
     * @param array $files_array Pass in the output of _prep_filenames
     * @return mixed string or FALSE
    */
    private function _combine_files($files_array, $should_parse_imports = FALSE) {
        
        $combined_output = '';
        foreach ($files_array as $file_array) {
            
            if (!file_exists($file_array['server_path'])
                || !is_readable($file_array['server_path'])) {
                return FALSE;
            }

            // Get file contents
            $combined_output .= file_get_contents($file_array['server_path']);

            // Parse @imports
            if ($should_parse_imports) {
                $combined_output = $this->_parse_css_imports(
                    $combined_output, 
                    $file_array['url_path']
                );
            }

        }

        return $combined_output;

    }

    /**
     * Returns the timestamp of the latest modified file
     *
     * @param array $files_array Pass in the output of _prep_filenames
     * @return int
    */
    private function _find_last_modified_timestamp($files_array) {
        
        $last_modified_timestamp = 0;
        foreach ($files_array as $file_array) {
            if ($file_array['last_modified']
                && $file_array['last_modified'] > $last_modified_timestamp) {
                $last_modified_timestamp = $file_array['last_modified'];
            }
        }

        return $last_modified_timestamp;

    }

    /**
     * Gathers information about each file and normalizes
     * the filename and path.
     * @param array $filenames_array
     * @return array
     *     - url_path
     *     - server_path
     *     - last_modified
    */
    private function _prep_filenames($filenames_array) {
        
        $information_array = array();

        foreach($filenames_array as $index => $filename) {

            // Path for URLs
            $information_array[$index]['url_path'] = $this->_normalize_file_path(
                $filename
            );

            // Path for reading
            $information_array[$index]['server_path'] = $this->_normalize_file_path(
                $filename, 
                '', 
                TRUE
            );

            // Last modified
            $information_array[$index]['last_modified'] = @filemtime(
                $information_array[$index]['server_path']
            );
            
        } 

        return $information_array;

    }

    /**
     * Extracts the filenames from the markup based on the provided
     * markup type.
     * @param string $markup
     * @param string $markup_type Use one of the constants MARKUP_TYPE_X
     * @return array (of filenames)
    */
    private function _extract_filenames($markup, $markup_type) {

        $matches_array;
        switch($markup_type) {
            case self::MARKUP_TYPE_CSS:
            case self::MARKUP_TYPE_LESS:
                preg_match_all(
                    "/href\=\"([A-Za-z0-9\.\/\_\-\?\=\:]+.[css|less])\"/",
                    $markup,
                    $matches_array
                );
                break;
            case self::MARKUP_TYPE_JS:
                preg_match_all(
                    "/src\=\"([A-Za-z0-9\.\/\_\-\?\=\:]+.js)\"/",
                    $markup,
                    $matches_array
                );
                break;
        }

        // Matches?
        if (count($matches_array) >= 2) {
            return $matches_array[1];
        }

        return FALSE;

    }

    /**
     * Extracts parameters from the tag param array that are
     * considered to be colon parameters. e.g. attribute:param="value"
     * @param string $colon_key The "attribute" part
     * @return array key/value pairs (param = "value")
    */
    private function _fetch_colon_params($colon_key) {


        // Get all params
        $all_params = ee()->TMPL->tagparams;

        // Pull out params that start with "custom:"
        $colon_params = array();
        if (is_array($all_params) && count($all_params)) {
            $colon_key_end_index = strlen($colon_key) + 1;
            foreach ($all_params as $key => $val) {
                if (strncmp($key, $colon_key, $colon_key_end_index-1) == 0) {
                    $colon_params[substr($key, $colon_key_end_index)] = $val;
                }
            }                    
        }

        return $colon_params;

    }

    /**
     * File paths may be in different formats. This function will take
     * any file path and normalize it to on of two formats depending on the
     * parameters you pass.
     * @param string $file_path The path to normalize.
     * @param string $relative_path If $file_path is a relative path, we need
     * the path to the relative file. If no path is supplied, the dirname of 
     * the current URI is used.
     * @param bool $include_root If TRUE, the full server path is returned. If
     * FALSE, the path returned is relative to the document root.
     * @return string
    */
    private function _normalize_file_path($file_path, $relative_path='', $include_root = FALSE) {

        // If the path is a full URL, return it
        // We don't currently fetch remote files
        if (0 === stripos($file_path, 'http')
            || 0 === stripos($file_path, '//')) {
            return $file_path;
        }

        // Get the relative path
        if (!$relative_path) {
            $relative_path = $_SERVER['REQUEST_URI'];
        }

        // Relative path should leave out the document root
        $relative_path = str_replace($_SERVER['DOCUMENT_ROOT'], '', $relative_path);

        // Parse the path
        $path_parts = pathinfo($relative_path);
        $dirname = $path_parts['dirname'].'/';

        // If not document-root relative, we must add the URI
        // of the calling page to make it document-root relative
        if (substr($file_path, 0, 1) != '/') {
            $file_path = $dirname.$file_path;
        }
    
        // Include full root path?
        if ($include_root) {
            $file_path = $_SERVER['DOCUMENT_ROOT'] . $file_path;
        }


        ee()->load->helper('string');
        $sFixedslashes = reduce_double_slashes($file_path);
        
        return $sFixedslashes;
    }
    
    /**
     * Looks for and parses @imports in the provided string.
     * @param string $string
     * @param string $relative_path Passed to _normalize_file_path(). See
     * that function's documentation for details on this param.
     * @return string
    */
    private function _parse_css_imports(&$string, $relative_path = '') {
        
        // Get all @imports
        $matches = array();
        preg_match_all('/\@import\s[url\(]?[\'\"]{1}([A-Za-z0-9\.\/\_\-]+)[\'\"]{1}[\)]?[;]?/', $string, $matches);
        $matched_lines = $matches[0];
        $matched_filenames = $matches[1];
        $count = 0;

        // Iterate and parse
        foreach($matched_filenames as $filename) {

            $filename = $this->_normalize_file_path($filename, $relative_path, TRUE);

            // Read the file
            $file_data = @file_get_contents($filename);

            // If we have data, replace the @import
            if ($file_data) {
                $string = str_replace($matched_lines[$count], $file_data, $string);
            }

            $count++;

        }

        return $string;

    }

    /**
     * Returns the filename of the cache file for the provided
     * cache value, if it exists and is readable.
     * @param string  $cache_key
     * @param string  $cache_value
     * @param integer $timestamp
     * @access private
     * @return void
    */
    private function _fetch_cache($cache_key, $cache_value, $timestamp)
    {
        // bail out early if disabled
        if (   $this->settings['automin_enabled'] == 'n'
            || $this->settings['caching_enabled'] == 'n' ) {
            return FALSE;
        }

        $cache_file_path = $this->_get_cache_file_path($cache_key);
        if ( ! file_exists($cache_file_path)) { return FALSE; }
        if ( ! is_readable($cache_file_path)) { return FALSE; }

        $last_modified = @filemtime($cache_file_path);
        if ( ! $last_modified
            || $last_modified < $timestamp) {   return FALSE; }

        return $this->_get_cache_url_path($cache_key);
    }

    /**
     * Writes the provided cache value and returns the filename of
     * the cache file.
     * @param string $cache_key A hash of
     * @return mixed FALSE if failure, string if success
    */
    private function _write_cache($cache_key, $cache_value)
    {
        // bail out early if disabled
        if (   $this->settings['automin_enabled'] == 'n'
            || $this->settings['caching_enabled'] == 'n' ) {
            return FALSE;
        }
        
        $cache_file_path = $this->_get_cache_file_path($cache_key);

        if (FALSE === file_put_contents($cache_file_path, $cache_value)) {
            return FALSE;
        }

        return $this->_get_cache_url_path($cache_key);
    }
    
    /**
     * Returns the full server path to the cache file, should it exist.
     * Does not check if the file exists.
     * @param string $cache_key
     * @return string
    */
    private function _get_cache_file_path($cache_key)
    {
        return $this->settings['cache_path'] . $cache_key;
    }

    /**
     * Returns the URL to the cache file, should it exist
     * @param string $cache_key
     * @return string
    */
    private function _get_cache_url_path($cache_key)
    {
        return $this->settings['cache_url'] . $cache_key;
    }

    /**
     * Writes the message to the template log
     * @param string $message
     * @return void
    */
    private function _write_log($message) {
        ee()->TMPL->log_item("AutoMin Module: $message");
    }

    // --------------------------------------------------------------------

}

/* End of file mod.automin.php */
/* Location: /system/expressionengine/third_party/automin/mod.automin.php */
