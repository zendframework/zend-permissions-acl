<?php
/**
 * @link      http://github.com/zendframework/zend-permissions-acl for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Permissions\Acl;

class Module
{
    /**
     * Retrieve default zend-session config for zend-mvc context.
     *
     * @return array
     */
    public function getConfig()
    {
        $provider = new ConfigProvider();
        return $provider();
    }
}
