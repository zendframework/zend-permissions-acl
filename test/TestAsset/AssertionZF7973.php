<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Permissions\Acl\TestAsset;

use Zend\Permissions\Acl\Assertion\AssertionInterface;
use Zend\Permissions\Acl;

class AssertionZF7973 implements AssertionInterface
{
    public function assert(
        Acl\Acl $acl,
        Acl\Role\RoleInterface $role = null,
        Acl\Resource\ResourceInterface $resource = null,
        $privilege = null
    ) {
        if ($privilege != 'privilege') {
            return false;
        }

        return true;
    }
}
