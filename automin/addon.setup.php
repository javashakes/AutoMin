<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * AutoMin
 *
 * @package     ExpressionEngine
 * @category    Plugin
 * @author      Matthew Kirkpatrick
 * @copyright   Copyright (c) 2020, Matthew Kirkpatrick
 * @link        https://github.com/javashakes
 */

// config
include(PATH_THIRD.'automin/config.php');

return array(
    'name'              => AUTOMIN_NAME,
    'version'           => AUTOMIN_VERSION,
    'author'            => AUTOMIN_AUTHOR,
    'author_url'        => AUTOMIN_AUTHOR_URL,
    'docs_url'          => AUTOMIN_DOCS,
    'description'       => AUTOMIN_DESC,
    'namespace'         => AUTOMIN_NAMESPACE,
    'settings_exist'    => TRUE
);

/* End of file addon.setup.php */
/* Location: /system/expressionengine/third_party/low_author/addon.setup.php */