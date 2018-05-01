<?php
/**
 * @see       https://github.com/zendframework/zend-permissions-acl for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-permissions-acl/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Permissions\Acl\TestAsset\StandardUseCase;

use Zend\Permissions\Acl\Acl as BaseAcl;
use Zend\Permissions\Acl\Resource\GenericResource;
use Zend\Permissions\Acl\Role\GenericRole;

class Acl extends BaseAcl
{
    public $customAssertion = null;

    public function __construct()
    {
        $this->customAssertion = new UserIsBlogPostOwnerAssertion();

        $this->addRole(new GenericRole('guest'));
        $this->addRole(new GenericRole('contributor'), 'guest');
        $this->addRole(new GenericRole('publisher'), 'contributor');
        $this->addRole(new GenericRole('admin'));

        $this->addResource(new GenericResource('blogPost'));

        $this->allow('guest', 'blogPost', 'view');
        $this->allow('contributor', 'blogPost', 'contribute');
        $this->allow('contributor', 'blogPost', 'modify', $this->customAssertion);
        $this->allow('publisher', 'blogPost', 'publish');
    }
}
