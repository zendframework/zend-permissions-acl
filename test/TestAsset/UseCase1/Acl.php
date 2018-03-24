<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Permissions\Acl\TestAsset\UseCase1;

class Acl extends \Zend\Permissions\Acl\Acl
{

    public $customAssertion = null;

    public function __construct()
    {
        $this->customAssertion = new UserIsBlogPostOwnerAssertion();

        $this->addRole(new \Zend\Permissions\Acl\Role\GenericRole('guest'));
        $this->addRole(new \Zend\Permissions\Acl\Role\GenericRole('contributor'), 'guest');
        $this->addRole(new \Zend\Permissions\Acl\Role\GenericRole('publisher'), 'contributor');
        $this->addRole(new \Zend\Permissions\Acl\Role\GenericRole('admin'));
        $this->addResource(new \Zend\Permissions\Acl\Resource\GenericResource('blogPost'));
        $this->allow('guest', 'blogPost', 'view');
        $this->allow('contributor', 'blogPost', 'contribute');
        $this->allow('contributor', 'blogPost', 'modify', $this->customAssertion);
        $this->allow('publisher', 'blogPost', 'publish');

        $this->addRole(new \Zend\Permissions\Acl\Role\GenericRole('hierarchy-guest'));
        $this->addRole(new \Zend\Permissions\Acl\Role\GenericRole('hierarchy-user'), 'hierarchy-guest');
        $this->addRole(
            new \Zend\Permissions\Acl\Role\GenericRole('hierarchy-admin'),
            ['hierarchy-user', 'hierarchy-guest']
        );
        $this->addResource(new \Zend\Permissions\Acl\Resource\GenericResource('hierarchy-resource'));
        $this->allow('hierarchy-user', 'hierarchy-resource', 'assert');
        $this->deny('hierarchy-guest', 'hierarchy-resource', 'assert');
    }
}
