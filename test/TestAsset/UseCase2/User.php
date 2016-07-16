<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */
namespace ZendTest\Permissions\Acl\TestAsset\UseCase2;

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
