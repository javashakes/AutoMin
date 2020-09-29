<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * AutoMin Module Install/Update File
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

class Automin_upd extends Automin_base {

    // --------------------------------------------------------------------
    // PROPERTIES
    // --------------------------------------------------------------------

    /**
     * Actions used
     *
     * @access      private
     * @var         array
     */
    private $actions = array(
        // array('', '')
    );

    /**
     * Hooks used
     *
     * @access      private
     * @var         array
     */
    private $hooks = array(
        'template_post_parse'
    );

    // --------------------------------------------------------------------
    // METHODS
    // --------------------------------------------------------------------

    /**
     * Install the module
     *
     * @access      public
     * @return      bool
     */
    public function install()
    {

        // --------------------------------------
        // Install tables
        // --------------------------------------
        foreach ($this->models AS $model)
        {
            ee()->$model->install();
        }

        // --------------------------------------
        // Add row to modules table
        // --------------------------------------
        ee()->db->insert('modules', array(
            'module_name'           => $this->class_name,
            'module_version'        => $this->version,
            'has_cp_backend'        => 'y',
            'has_publish_fields'    => 'n'
        ));

        // --------------------------------------
        // Add rows to action table
        // --------------------------------------
        foreach ($this->actions AS $row)
        {
            $this->_add_action($row);
        }

        // --------------------------------------
        // Add rows to extensions table
        // --------------------------------------
        foreach ($this->hooks AS $hook)
        {
            $this->_add_hook($hook);
        }

        return TRUE;
    }

    /**
     * Uninstall the module
     *
     * @return    bool
     */
    public function uninstall()
    {
        // --------------------------------------
        // get module id
        // --------------------------------------
        $query = ee()->db->select('module_id')
               ->from('modules')
               ->where('module_name', $this->class_name)
               ->get();

        // --------------------------------------
        // remove references from module_member_groups
        // --------------------------------------
        ee()->db->where('module_id', $query->row('module_id'));
        ee()->db->delete('module_member_groups');

        // --------------------------------------
        // remove references from modules
        // --------------------------------------
        ee()->db->where('module_name', $this->class_name);
        ee()->db->delete('modules');

        // --------------------------------------
        // remove references from extensions
        // --------------------------------------
        ee()->db->where('class', $this->class_name.'_ext');
        ee()->db->delete('extensions');

        // --------------------------------------
        // Uninstall tables
        // --------------------------------------
        foreach ($this->models AS $model)
        {
            ee()->$model->uninstall();
        }

        return TRUE;
    }

    /**
     * Update the module
     *
     * @return    bool
     */
    public function update($current = '')
    {

        // Don't update if they are the same
        if (version_compare($current, $this->version, '=')) {
            return FALSE;
        }

        // Update if there is a new version
        if (version_compare($current, $this->version, '<')) {

            $current = str_replace('.', '', $current);

            // Two Digits? (needs to be 3)
            if (strlen($current) == 2) $current .= '0';

            $update_dir = PATH_THIRD.strtolower($this->class_name).'/updates/';

            // Does our folder exist?
            if (@is_dir($update_dir) === TRUE)
            {
                // Loop over all files
                $files = @scandir($update_dir);

                if (is_array($files) == TRUE)
                {
                    foreach ($files as $file)
                    {
                        if ($file == '.' OR $file == '..' OR strtolower($file) == '.ds_store') continue;

                        // Get the version number
                        $ver = substr($file, 0, -4);

                        // We only want greater ones
                        if ($current >= $ver) continue;

                        // run the update(s)
                        require $update_dir . $file;
                        $class = 'AutoMinVersion_' . $ver;
                        $UPD = new $class();
                        $UPD->do_update();
                    }
                }
            }

            // Return TRUE to update version number in DB
            return TRUE;

        }

    }

    // --------------------------------------------------------------------

    /**
     * Add action to actions table
     *
     * @access     private
     * @param      array
     * @return     void
     */
    private function _add_action($array)
    {
        list($class, $method) = $array;

        ee()->db->insert('actions', array(
            'class'  => $class,
            'method' => $method
        ));
    }

    /**
     * Add hook to extensions table
     *
     * @access     private
     * @param      string
     * @return     void
     */
    private function _add_hook($hook)
    {
        ee()->db->insert('extensions', array(
            'class'     => $this->class_name.'_ext',
            'method'    => $hook,
            'hook'      => $hook,
            'priority'  => ($hook == $this->hooks[0] ? 101 : 10),
            'version'   => $this->version,
            'enabled'   => 'y',
            'settings'  => ''
        ));
    }

    // --------------------------------------------------------------------

}
/* End of file upd.automin.php */
/* Location: /system/expressionengine/third_party/automin/upd.automin.php */
