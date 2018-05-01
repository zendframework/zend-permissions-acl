<?php
/**
 * @see       https://github.com/zendframework/zend-permissions-acl for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-permissions-acl/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Permissions\Acl\TestAsset\StandardUseCase;

use Zend\Permissions\Acl\Acl as ZendAcl;
use Zend\Permissions\Acl\Assertion\AssertionInterface;
use Zend\Permissions\Acl\Resource\ResourceInterface;
use Zend\Permissions\Acl\Role\RoleInterface;

class UserIsBlogPostOwnerAssertion implements AssertionInterface
{
    public $lastAssertRole = null;
    public $lastAssertResource = null;
    public $lastAssertPrivilege = null;
    public $assertReturnValue = true;

    public function assert(
        ZendAcl $acl,
        RoleInterface $user = null,
        ResourceInterface $blogPost = null,
        $privilege = null
    ) {
        $this->lastAssertRole      = $user;
        $this->lastAssertResource  = $blogPost;
        $this->lastAssertPrivilege = $privilege;
        return $this->assertReturnValue;
    }
}
