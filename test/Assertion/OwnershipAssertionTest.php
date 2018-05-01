<?php
/**
 * @see       https://github.com/zendframework/zend-permissions-acl for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-permissions-acl/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Permissions\Acl\Assertion;

use PHPUnit\Framework\TestCase;
use ZendTest\Permissions\Acl\TestAsset\UseCase2;

class OwnershipAssertionTest extends TestCase
{
    public function testAssertPassesIfRoleIsNotProprietary()
    {
        $acl = new UseCase2\Acl();

        $this->assertTrue($acl->isAllowed('guest', 'blogPost', 'view'));
        $this->assertFalse($acl->isAllowed('guest', 'blogPost', 'delete'));
    }

    public function testAssertPassesIfResourceIsNotProprietary()
    {
        $acl = new UseCase2\Acl();

        $author = new UseCase2\Author1();

        $this->assertTrue($acl->isAllowed($author, 'comment', 'view'));
        $this->assertFalse($acl->isAllowed($author, 'comment', 'delete'));
    }

    public function testAssertPassesIfResourceDoesNotHaveOwner()
    {
        $acl = new UseCase2\Acl();

        $author = new UseCase2\Author1();

        $blogPost = new UseCase2\BlogPost();
        $blogPost->author = null;

        $this->assertTrue($acl->isAllowed($author, 'blogPost', 'write'));
        $this->assertTrue($acl->isAllowed($author, $blogPost, 'edit'));
    }

    public function testAssertFailsIfResourceHasOwnerOtherThanRoleOwner()
    {
        $acl = new UseCase2\Acl();

        $author1 = new UseCase2\Author1();
        $author2 = new UseCase2\Author2();

        $blogPost = new UseCase2\BlogPost();
        $blogPost->author = $author1;

        $this->assertTrue($acl->isAllowed($author2, 'blogPost', 'write'));
        $this->assertFalse($acl->isAllowed($author2, $blogPost, 'edit'));
    }
}
