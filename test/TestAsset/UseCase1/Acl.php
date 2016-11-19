<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Permissions\Acl\TestAsset\UseCase1;

use Zend\Permissions\Acl\Resource\GenericResource;
use Zend\Permissions\Acl\Role\GenericRole;

class Acl extends \Zend\Permissions\Acl\Acl
{

    public $customAssertion = null;

    public function __construct()
    {
        $this->customAssertion = new UserIsBlogPostOwnerAssertion();

        $this->addRole(new GenericRole('guest'));                    // $acl->addRole('guest');
        $this->addRole(new GenericRole('contributor'), 'guest');     // $acl->addRole('contributor', 'guest');
        $this->addRole(new GenericRole('publisher'), 'contributor'); // $acl->addRole('publisher', 'contributor');
        $this->addRole(new GenericRole('admin'));                    // $acl->addRole('admin');
        $this->addResource(new GenericResource('blogPost'));     // $acl->addResource('blogPost');
        $this->allow('guest', 'blogPost', 'view');
        $this->allow('contributor', 'blogPost', 'contribute');
        $this->allow('contributor', 'blogPost', 'modify', $this->customAssertion);
        $this->allow('publisher', 'blogPost', 'publish');
    }
}
