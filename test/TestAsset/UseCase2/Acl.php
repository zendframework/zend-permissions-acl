<?php
/**
 * @see       https://github.com/zendframework/zend-permissions-acl for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-permissions-acl/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Permissions\Acl\TestAsset\UseCase2;

use Zend\Permissions\Acl\Acl as ZendAcl;
use Zend\Permissions\Acl\Assertion\OwnershipAssertion;

class Acl extends ZendAcl
{
    public function __construct()
    {
        $this->addRole('guest');
        $this->addRole('member', 'guest');
        $this->addRole('author', 'member');
        $this->addRole('admin');

        $this->addResource(new BlogPost());
        $this->addResource(new Comment());

        $this->allow('guest', 'blogPost', 'view');
        $this->allow('guest', 'comment', ['view', 'submit']);
        $this->allow('author', 'blogPost', 'write');
        $this->allow('author', 'blogPost', 'edit', new OwnershipAssertion());
        $this->allow('admin');
    }
}
