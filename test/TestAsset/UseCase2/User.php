<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Permissions\Acl\TestAsset\UseCase2;

use Zend\Permissions\Acl\Role;
use Zend\Permissions\Acl\ProprietaryInterface;

class User implements Role\RoleInterface, ProprietaryInterface
{
    public $id;

    public $role = 'guest';

    public function getRoleId()
    {
        return $this->role;
    }

    public function getOwnerId()
    {
        return $this->id;
    }
}
