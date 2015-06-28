<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Permissions\Acl\TestAsset\UseCase2;

use Zend\Permissions\Acl\Assertion\OwnershipAssertion;

class Acl extends \Zend\Permissions\Acl\Acl
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
        $this->allow('guest', 'comment', array('view', 'submit'));
        $this->allow('author', 'blogPost', 'write');
        $this->allow('author', 'blogPost', 'edit', new OwnershipAssertion());
        $this->allow('admin');
    }
}
