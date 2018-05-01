<?php
/**
 * @see       https://github.com/zendframework/zend-permissions-acl for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-permissions-acl/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Permissions\Acl\TestAsset\ExpressionUseCase;

use Zend\Permissions\Acl\Role\RoleInterface;

class User implements RoleInterface
{
    public $username;

    public $role = 'guest';

    public $age;

    public function __construct(array $data = [])
    {
        foreach ($data as $property => $value) {
            $this->$property = $value;
        }
    }

    public function getRoleId()
    {
        return $this->role;
    }

    public function isAdult()
    {
        return $this->age >= 18;
    }
}
