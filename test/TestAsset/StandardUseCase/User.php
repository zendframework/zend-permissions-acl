<?php
/**
 * @see       https://github.com/zendframework/zend-permissions-acl for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-permissions-acl/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Permissions\Acl\TestAsset\StandardUseCase;

use Zend\Permissions\Acl\Role\RoleInterface;

class User implements RoleInterface
{
    public $role = 'guest';

    public function getRoleId()
    {
        return $this->role;
    }
}
