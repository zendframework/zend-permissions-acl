<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Permissions\Acl;

use PHPUnit\Framework\TestCase;
use Zend\Permissions\Acl;
use Zend\Permissions\Acl\Role;
use Zend\Permissions\Acl\Resource;
use Zend\Permissions\Acl\Exception\InvalidArgumentException;
use Zend\Permissions\Acl\Exception\ExceptionInterface;

/**
 * @group      Zend_Acl
 */
class AclTest extends TestCase
{
    /**
     * ACL object for each test method
     *
     * @var Acl\Acl
     */
    protected $acl;

    /**
     * Instantiates a new ACL object and creates internal reference to it for each test method
     *
     * @return void
     */
    public function setUp()
    {
        $this->acl = new Acl\Acl();
    }

    /**
     * Ensures that basic addition and retrieval of a single Role works
     *
     * @return void
     */
    public function testRoleRegistryAddAndGetOne()
    {
        $roleGuest = new Role\GenericRole('guest');

        $role = $this->acl->addRole($roleGuest)
                          ->getRole($roleGuest->getRoleId());
        $this->assertEquals($roleGuest, $role);
        $role = $this->acl->getRole($roleGuest);
        $this->assertEquals($roleGuest, $role);
    }

    /**
     * Ensures that basic addition and retrieval of a single Resource works
     */
    public function testRoleAddAndGetOneByString()
    {
        $role = $this->acl->addRole('area')
                           ->getRole('area');
        $this->assertInstanceOf('Zend\Permissions\Acl\Role\RoleInterface', $role);
        $this->assertEquals('area', $role->getRoleId());
    }

    /**
     * Ensures that basic removal of a single Role works
     *
     * @return void
     */
    public function testRoleRegistryRemoveOne()
    {
        $roleGuest = new Role\GenericRole('guest');
        $this->acl->addRole($roleGuest)
                   ->removeRole($roleGuest);
        $this->assertFalse($this->acl->hasRole($roleGuest));
    }

    /**
     * Ensures that an exception is thrown when a non-existent Role is specified for removal
     *
     * @return void
     */
    public function testRoleRegistryRemoveOneNonExistent()
    {
        $this->expectException(InvalidArgumentException::class, 'not found');
        $this->acl->removeRole('nonexistent');
    }

    /**
     * Ensures that removal of all Roles works
     *
     * @return void
     */
    public function testRoleRegistryRemoveAll()
    {
        $roleGuest = new Role\GenericRole('guest');
        $this->acl->addRole($roleGuest)
                   ->removeRoleAll();
        $this->assertFalse($this->acl->hasRole($roleGuest));
    }

    /**
     * Ensures that an exception is thrown when a non-existent Role is specified as a parent upon Role addition
     *
     * @return void
     */
    public function testRoleRegistryAddInheritsNonExistent()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->acl->addRole(new Role\GenericRole('guest'), 'nonexistent');
    }

    /**
     * Ensures that an exception is thrown when a not Role is passed
     *
     * @return void
     */
    public function testRoleRegistryAddNotRole()
    {
        $this->expectException(
            InvalidArgumentException::class,
            'addRole() expects $role to be of type Zend\Permissions\Acl\Role'
        );
        $this->acl->addRole(new \stdClass, 'guest');
    }

    /**
     * Ensures that an exception is thrown when a non-existent Role is specified to each parameter of inherits()
     *
     * @return void
     */
    public function testRoleRegistryInheritsNonExistent()
    {
        $roleGuest = new Role\GenericRole('guest');
        $this->acl->addRole($roleGuest);
        try {
            $this->acl->inheritsRole('nonexistent', $roleGuest);
            $this->fail(
                'Expected Zend\Permissions\Acl\Role\Exception not thrown upon specifying a non-existent child Role'
            );
        } catch (Acl\Exception\InvalidArgumentException $e) {
            $this->assertContains('not found', $e->getMessage());
        }
        try {
            $this->acl->inheritsRole($roleGuest, 'nonexistent');
            $this->fail(
                'Expected Zend\Permissions\Acl\Role\Exception not thrown upon specifying a non-existent child Role'
            );
        } catch (Acl\Exception\InvalidArgumentException $e) {
            $this->assertContains('not found', $e->getMessage());
        }
    }

    /**
     * Tests basic Role inheritance
     *
     * @return void
     */
    public function testRoleRegistryInherits()
    {
        $roleGuest  = new Role\GenericRole('guest');
        $roleMember = new Role\GenericRole('member');
        $roleEditor = new Role\GenericRole('editor');
        $roleRegistry = new Role\Registry();
        $roleRegistry->add($roleGuest)
                     ->add($roleMember, $roleGuest->getRoleId())
                     ->add($roleEditor, $roleMember);
        $this->assertEmpty($roleRegistry->getParents($roleGuest));
        $roleMemberParents = $roleRegistry->getParents($roleMember);
        $this->assertCount(1, $roleMemberParents);
        $this->assertTrue(isset($roleMemberParents['guest']));
        $roleEditorParents = $roleRegistry->getParents($roleEditor);
        $this->assertCount(1, $roleEditorParents);
        $this->assertTrue(isset($roleEditorParents['member']));
        $this->assertTrue($roleRegistry->inherits($roleMember, $roleGuest, true));
        $this->assertTrue($roleRegistry->inherits($roleEditor, $roleMember, true));
        $this->assertTrue($roleRegistry->inherits($roleEditor, $roleGuest));
        $this->assertFalse($roleRegistry->inherits($roleGuest, $roleMember));
        $this->assertFalse($roleRegistry->inherits($roleMember, $roleEditor));
        $this->assertFalse($roleRegistry->inherits($roleGuest, $roleEditor));
        $roleRegistry->remove($roleMember);
        $this->assertEmpty($roleRegistry->getParents($roleEditor));
        $this->assertFalse($roleRegistry->inherits($roleEditor, $roleGuest));
    }

    /**
     * Tests basic Role multiple inheritance with array
     *
     * @return void
     */
    public function testRoleRegistryInheritsMultipleArray()
    {
        $roleParent1 = new Role\GenericRole('parent1');
        $roleParent2 = new Role\GenericRole('parent2');
        $roleChild   = new Role\GenericRole('child');
        $roleRegistry = new Role\Registry();
        $roleRegistry->add($roleParent1)
                     ->add($roleParent2)
                     ->add($roleChild, [$roleParent1, $roleParent2]);
        $roleChildParents = $roleRegistry->getParents($roleChild);
        $this->assertCount(2, $roleChildParents);
        $i = 1;
        foreach ($roleChildParents as $roleParentId => $roleParent) {
            $this->assertEquals("parent$i", $roleParentId);
            $i++;
        }
        $this->assertTrue($roleRegistry->inherits($roleChild, $roleParent1));
        $this->assertTrue($roleRegistry->inherits($roleChild, $roleParent2));
        $roleRegistry->remove($roleParent1);
        $roleChildParents = $roleRegistry->getParents($roleChild);
        $this->assertCount(1, $roleChildParents);
        $this->assertTrue(isset($roleChildParents['parent2']));
        $this->assertTrue($roleRegistry->inherits($roleChild, $roleParent2));
    }

    /**
     * Tests basic Role multiple inheritance with traversable object
     *
     * @return void
     */
    public function testRoleRegistryInheritsMultipleTraversable()
    {
        $roleParent1 = new Role\GenericRole('parent1');
        $roleParent2 = new Role\GenericRole('parent2');
        $roleChild   = new Role\GenericRole('child');
        $roleRegistry = new Role\Registry();
        $roleRegistry->add($roleParent1)
            ->add($roleParent2)
            ->add(
                $roleChild,
                new \ArrayIterator([$roleParent1, $roleParent2])
            );
        $roleChildParents = $roleRegistry->getParents($roleChild);
        $this->assertCount(2, $roleChildParents);
        $i = 1;
        foreach ($roleChildParents as $roleParentId => $roleParent) {
            $this->assertEquals("parent$i", $roleParentId);
            $i++;
        }
        $this->assertTrue($roleRegistry->inherits($roleChild, $roleParent1));
        $this->assertTrue($roleRegistry->inherits($roleChild, $roleParent2));
        $roleRegistry->remove($roleParent1);
        $roleChildParents = $roleRegistry->getParents($roleChild);
        $this->assertCount(1, $roleChildParents);
        $this->assertTrue(isset($roleChildParents['parent2']));
        $this->assertTrue($roleRegistry->inherits($roleChild, $roleParent2));
    }

    /**
     * Ensures that the same Role cannot be registered more than once to the registry
     *
     * @return void
     */
    public function testRoleRegistryDuplicate()
    {
        $roleGuest = new Role\GenericRole('guest');
        $roleRegistry = new Role\Registry();
        $this->expectException(InvalidArgumentException::class, 'already exists');
        $roleRegistry->add($roleGuest)
                     ->add($roleGuest);
    }

    /**
     * Ensures that two Roles having the same ID cannot be registered
     *
     * @return void
     */
    public function testRoleRegistryDuplicateId()
    {
        $roleGuest1 = new Role\GenericRole('guest');
        $roleGuest2 = new Role\GenericRole('guest');
        $roleRegistry = new Role\Registry();
        $this->expectException(InvalidArgumentException::class, 'already exists');
        $roleRegistry->add($roleGuest1)
                     ->add($roleGuest2);
    }

    /**
     * Ensures that basic addition and retrieval of a single Resource works
     *
     * @return void
     */
    public function testResourceAddAndGetOne()
    {
        $resourceArea = new Resource\GenericResource('area');
        $resource = $this->acl->addResource($resourceArea)
                          ->getResource($resourceArea->getResourceId());
        $this->assertEquals($resourceArea, $resource);
        $resource = $this->acl->getResource($resourceArea);
        $this->assertEquals($resourceArea, $resource);
    }

    /**
     * Ensures that basic addition and retrieval of a single Resource works
     */
    public function testResourceAddAndGetOneByString()
    {
        $resource = $this->acl->addResource('area')
                          ->getResource('area');
        $this->assertInstanceOf('Zend\Permissions\Acl\Resource\ResourceInterface', $resource);
        $this->assertEquals('area', $resource->getResourceId());
    }

    /**
     * Ensures that basic addition and retrieval of a single Resource works
     *
     * @group ZF-1167
     */
    public function testResourceAddAndGetOneWithAddResourceMethod()
    {
        $resourceArea = new Resource\GenericResource('area');
        $resource = $this->acl->addResource($resourceArea)
                               ->getResource($resourceArea->getResourceId());
        $this->assertEquals($resourceArea, $resource);
        $resource = $this->acl->getResource($resourceArea);
        $this->assertEquals($resourceArea, $resource);
    }

    /**
     * Ensures that basic removal of a single Resource works
     *
     * @return void
     */
    public function testResourceRemoveOne()
    {
        $resourceArea = new Resource\GenericResource('area');
        $this->acl->addResource($resourceArea)
                   ->removeResource($resourceArea);
        $this->assertFalse($this->acl->hasResource($resourceArea));
    }

    /**
     * Ensures that an exception is thrown when a non-existent Resource is specified for removal
     *
     * @return void
     */
    public function testResourceRemoveOneNonExistent()
    {
        $this->expectException(ExceptionInterface::class, 'not found');
        $this->acl->removeResource('nonexistent');
    }

    /**
     * Ensures that removal of all Resources works
     *
     * @return void
     */
    public function testResourceRemoveAll()
    {
        $resourceArea = new Resource\GenericResource('area');
        $this->acl->addResource($resourceArea)
                   ->removeResourceAll();
        $this->assertFalse($this->acl->hasResource($resourceArea));
    }

    /**
     * Ensures that an exception is thrown when a non-existent Resource is specified as a parent upon Resource addition
     *
     * @return void
     */
    public function testResourceAddInheritsNonExistent()
    {
        $this->expectException(InvalidArgumentException::class, 'does not exist');
        $this->acl->addResource(new Resource\GenericResource('area'), 'nonexistent');
    }

    /**
     * Ensures that an exception is thrown when a not Resource is passed
     *
     * @return void
     */
    public function testResourceRegistryAddNotResource()
    {
        $this->expectException(
            InvalidArgumentException::class,
            'addResource() expects $resource to be of type Zend\Permissions\Acl\Resource'
        );
        $this->acl->addResource(new \stdClass);
    }

    /**
     * Ensures that an exception is thrown when a non-existent Resource is specified to each parameter of inherits()
     *
     * @return void
     */
    public function testResourceInheritsNonExistent()
    {
        $resourceArea = new Resource\GenericResource('area');
        $this->acl->addResource($resourceArea);
        try {
            $this->acl->inheritsResource('nonexistent', $resourceArea);
            $this->fail(
                'Expected Zend\Permissions\Acl\Exception\ExceptionInterface not '
                . 'thrown upon specifying a non-existent child Resource'
            );
        } catch (Acl\Exception\ExceptionInterface $e) {
            $this->assertContains('not found', $e->getMessage());
        }
        try {
            $this->acl->inheritsResource($resourceArea, 'nonexistent');
            $this->fail(
                'Expected Zend\Permissions\Acl\Exception\ExceptionInterface not thrown '
                . 'upon specifying a non-existent parent Resource'
            );
        } catch (Acl\Exception\ExceptionInterface $e) {
            $this->assertContains('not found', $e->getMessage());
        }
    }

    /**
     * Tests basic Resource inheritance
     *
     * @return void
     */
    public function testResourceInherits()
    {
        $resourceCity     = new Resource\GenericResource('city');
        $resourceBuilding = new Resource\GenericResource('building');
        $resourceRoom     = new Resource\GenericResource('room');
        $this->acl->addResource($resourceCity)
                   ->addResource($resourceBuilding, $resourceCity->getResourceId())
                   ->addResource($resourceRoom, $resourceBuilding);
        $this->assertTrue($this->acl->inheritsResource($resourceBuilding, $resourceCity, true));
        $this->assertTrue($this->acl->inheritsResource($resourceRoom, $resourceBuilding, true));
        $this->assertTrue($this->acl->inheritsResource($resourceRoom, $resourceCity));
        $this->assertFalse($this->acl->inheritsResource($resourceCity, $resourceBuilding));
        $this->assertFalse($this->acl->inheritsResource($resourceBuilding, $resourceRoom));
        $this->assertFalse($this->acl->inheritsResource($resourceCity, $resourceRoom));
        $this->acl->removeResource($resourceBuilding);
        $this->assertFalse($this->acl->hasResource($resourceRoom));
    }

    /**
     * Ensures that the same Resource cannot be added more than once
     *
     * @return void
     */
    public function testResourceDuplicate()
    {
        $this->expectException(ExceptionInterface::class, 'already exists');
        $resourceArea = new Resource\GenericResource('area');
        $this->acl->addResource($resourceArea)
                   ->addResource($resourceArea);
    }

    /**
     * Ensures that two Resources having the same ID cannot be added
     *
     * @return void
     */
    public function testResourceDuplicateId()
    {
        $this->expectException(ExceptionInterface::class, 'already exists');
        $resourceArea1 = new Resource\GenericResource('area');
        $resourceArea2 = new Resource\GenericResource('area');
        $this->acl->addResource($resourceArea1)
                   ->addResource($resourceArea2);
    }

    /**
     * Ensures that an exception is thrown when a non-existent Role and Resource parameters are specified to isAllowed()
     *
     * @return void
     */
    public function testIsAllowedNonExistent()
    {
        try {
            $this->acl->isAllowed('nonexistent');
            $this->fail(
                'Expected Zend\Permissions\Acl\Role\Exception\ExceptionInterface not thrown upon non-existent Role'
            );
        } catch (Acl\Exception\InvalidArgumentException $e) {
            $this->assertContains('not found', $e->getMessage());
        }
        try {
            $this->acl->isAllowed(null, 'nonexistent');
            $this->fail(
                'Expected Zend\Permissions\Acl\Exception\ExceptionInterface not thrown upon non-existent Resource'
            );
        } catch (Acl\Exception\InvalidArgumentException $e) {
            $this->assertContains('not found', $e->getMessage());
        }
    }

    /**
     * Ensures that by default, Zend_Acl denies access to everything by all
     *
     * @return void
     */
    public function testDefaultDeny()
    {
        $this->assertFalse($this->acl->isAllowed());
    }

    /**
     * Ensures that the default rule obeys its assertion
     *
     * @return void
     */
    public function testDefaultAssert()
    {
        $this->acl->deny(null, null, null, new TestAsset\MockAssertion(false));
        $this->assertTrue($this->acl->isAllowed());
        $this->assertTrue($this->acl->isAllowed(null, null, 'somePrivilege'));
    }

    /**
     * Ensures that ACL-wide rules (all Roles, Resources, and privileges) work properly
     *
     * @return void
     */
    public function testDefaultRuleSet()
    {
        $this->acl->allow();
        $this->assertTrue($this->acl->isAllowed());
        $this->acl->deny();
        $this->assertFalse($this->acl->isAllowed());
    }

    /**
     * Ensures that by default, Zend_Acl denies access to a privilege on anything by all
     *
     * @return void
     */
    public function testDefaultPrivilegeDeny()
    {
        $this->assertFalse($this->acl->isAllowed(null, null, 'somePrivilege'));
    }

    /**
     * Ensures that ACL-wide rules apply to privileges
     *
     * @return void
     */
    public function testDefaultRuleSetPrivilege()
    {
        $this->acl->allow();
        $this->assertTrue($this->acl->isAllowed(null, null, 'somePrivilege'));
        $this->acl->deny();
        $this->assertFalse($this->acl->isAllowed(null, null, 'somePrivilege'));
    }

    /**
     * Ensures that a privilege allowed for all Roles upon all Resources works properly
     *
     * @return void
     */
    public function testPrivilegeAllow()
    {
        $this->acl->allow(null, null, 'somePrivilege');
        $this->assertTrue($this->acl->isAllowed(null, null, 'somePrivilege'));
    }

    /**
     * Ensures that a privilege denied for all Roles upon all Resources works properly
     *
     * @return void
     */
    public function testPrivilegeDeny()
    {
        $this->acl->allow();
        $this->acl->deny(null, null, 'somePrivilege');
        $this->assertFalse($this->acl->isAllowed(null, null, 'somePrivilege'));
    }

    /**
     * Ensures that multiple privileges work properly
     *
     * @return void
     */
    public function testPrivileges()
    {
        $this->acl->allow(null, null, ['p1', 'p2', 'p3']);
        $this->assertTrue($this->acl->isAllowed(null, null, 'p1'));
        $this->assertTrue($this->acl->isAllowed(null, null, 'p2'));
        $this->assertTrue($this->acl->isAllowed(null, null, 'p3'));
        $this->assertFalse($this->acl->isAllowed(null, null, 'p4'));
        $this->acl->deny(null, null, 'p1');
        $this->assertFalse($this->acl->isAllowed(null, null, 'p1'));
        $this->acl->deny(null, null, ['p2', 'p3']);
        $this->assertFalse($this->acl->isAllowed(null, null, 'p2'));
        $this->assertFalse($this->acl->isAllowed(null, null, 'p3'));
    }

    /**
     * Ensures that assertions on privileges work properly
     *
     * @return void
     */
    public function testPrivilegeAssert()
    {
        $this->acl->allow(null, null, 'somePrivilege', new TestAsset\MockAssertion(true));
        $this->assertTrue($this->acl->isAllowed(null, null, 'somePrivilege'));
        $this->acl->allow(null, null, 'somePrivilege', new TestAsset\MockAssertion(false));
        $this->assertFalse($this->acl->isAllowed(null, null, 'somePrivilege'));
    }

    /**
     * Ensures that by default, Zend_Acl denies access to everything for a particular Role
     *
     * @return void
     */
    public function testRoleDefaultDeny()
    {
        $roleGuest = new Role\GenericRole('guest');
        $this->acl->addRole($roleGuest);
        $this->assertFalse($this->acl->isAllowed($roleGuest));
    }

    /**
     * Ensures that ACL-wide rules (all Resources and privileges) work properly for a particular Role
     *
     * @return void
     */
    public function testRoleDefaultRuleSet()
    {
        $roleGuest = new Role\GenericRole('guest');
        $this->acl->addRole($roleGuest)
                   ->allow($roleGuest);
        $this->assertTrue($this->acl->isAllowed($roleGuest));
        $this->acl->deny($roleGuest);
        $this->assertFalse($this->acl->isAllowed($roleGuest));
    }

    /**
     * Ensures that by default, Zend_Acl denies access to a privilege on anything for a particular Role
     *
     * @return void
     */
    public function testRoleDefaultPrivilegeDeny()
    {
        $roleGuest = new Role\GenericRole('guest');
        $this->acl->addRole($roleGuest);
        $this->assertFalse($this->acl->isAllowed($roleGuest, null, 'somePrivilege'));
    }

    /**
     * Ensures that ACL-wide rules apply to privileges for a particular Role
     *
     * @return void
     */
    public function testRoleDefaultRuleSetPrivilege()
    {
        $roleGuest = new Role\GenericRole('guest');
        $this->acl->addRole($roleGuest)
                   ->allow($roleGuest);
        $this->assertTrue($this->acl->isAllowed($roleGuest, null, 'somePrivilege'));
        $this->acl->deny($roleGuest);
        $this->assertFalse($this->acl->isAllowed($roleGuest, null, 'somePrivilege'));
    }

    /**
     * Ensures that a privilege allowed for a particular Role upon all Resources works properly
     *
     * @return void
     */
    public function testRolePrivilegeAllow()
    {
        $roleGuest = new Role\GenericRole('guest');
        $this->acl->addRole($roleGuest)
                   ->allow($roleGuest, null, 'somePrivilege');
        $this->assertTrue($this->acl->isAllowed($roleGuest, null, 'somePrivilege'));
    }

    /**
     * Ensures that a privilege denied for a particular Role upon all Resources works properly
     *
     * @return void
     */
    public function testRolePrivilegeDeny()
    {
        $roleGuest = new Role\GenericRole('guest');
        $this->acl->addRole($roleGuest)
                   ->allow($roleGuest)
                   ->deny($roleGuest, null, 'somePrivilege');
        $this->assertFalse($this->acl->isAllowed($roleGuest, null, 'somePrivilege'));
    }

    /**
     * Ensures that multiple privileges work properly for a particular Role
     *
     * @return void
     */
    public function testRolePrivileges()
    {
        $roleGuest = new Role\GenericRole('guest');
        $this->acl->addRole($roleGuest)
                   ->allow($roleGuest, null, ['p1', 'p2', 'p3']);
        $this->assertTrue($this->acl->isAllowed($roleGuest, null, 'p1'));
        $this->assertTrue($this->acl->isAllowed($roleGuest, null, 'p2'));
        $this->assertTrue($this->acl->isAllowed($roleGuest, null, 'p3'));
        $this->assertFalse($this->acl->isAllowed($roleGuest, null, 'p4'));
        $this->acl->deny($roleGuest, null, 'p1');
        $this->assertFalse($this->acl->isAllowed($roleGuest, null, 'p1'));
        $this->acl->deny($roleGuest, null, ['p2', 'p3']);
        $this->assertFalse($this->acl->isAllowed($roleGuest, null, 'p2'));
        $this->assertFalse($this->acl->isAllowed($roleGuest, null, 'p3'));
    }

    /**
     * Ensures that assertions on privileges work properly for a particular Role
     *
     * @return void
     */
    public function testRolePrivilegeAssert()
    {
        $roleGuest = new Role\GenericRole('guest');
        $this->acl->addRole($roleGuest)
                   ->allow($roleGuest, null, 'somePrivilege', new TestAsset\MockAssertion(true));
        $this->assertTrue($this->acl->isAllowed($roleGuest, null, 'somePrivilege'));
        $this->acl->allow($roleGuest, null, 'somePrivilege', new TestAsset\MockAssertion(false));
        $this->assertFalse($this->acl->isAllowed($roleGuest, null, 'somePrivilege'));
    }

    /**
     * Ensures that removing the default deny rule results in default deny rule
     *
     * @return void
     */
    public function testRemoveDefaultDeny()
    {
        $this->assertFalse($this->acl->isAllowed());
        $this->acl->removeDeny();
        $this->assertFalse($this->acl->isAllowed());
    }

    /**
     * Ensures that removing the default deny rule results in assertion method being removed
     *
     * @return void
     */
    public function testRemoveDefaultDenyAssert()
    {
        $this->acl->deny(null, null, null, new TestAsset\MockAssertion(false));
        $this->assertTrue($this->acl->isAllowed());
        $this->acl->removeDeny();
        $this->assertFalse($this->acl->isAllowed());
    }

    /**
     * Ensures that removing the default allow rule results in default deny rule being assigned
     *
     * @return void
     */
    public function testRemoveDefaultAllow()
    {
        $this->acl->allow();
        $this->assertTrue($this->acl->isAllowed());
        $this->acl->removeAllow();
        $this->assertFalse($this->acl->isAllowed());
    }

    /**
     * Ensures that removing non-existent default allow rule does nothing
     *
     * @return void
     */
    public function testRemoveDefaultAllowNonExistent()
    {
        $this->acl->removeAllow();
        $this->assertFalse($this->acl->isAllowed());
    }

    /**
     * Ensures that removing non-existent default deny rule does nothing
     *
     * @return void
     */
    public function testRemoveDefaultDenyNonExistent()
    {
        $this->acl->allow()
                   ->removeDeny();
        $this->assertTrue($this->acl->isAllowed());
    }

    /**
     * Ensures that for a particular Role, a deny rule on a specific Resource is honored before an allow rule
     * on the entire ACL
     *
     * @return void
     */
    public function testRoleDefaultAllowRuleWithResourceDenyRule()
    {
        $this->acl->addRole(new Role\GenericRole('guest'))
                   ->addRole(new Role\GenericRole('staff'), 'guest')
                   ->addResource(new Resource\GenericResource('area1'))
                   ->addResource(new Resource\GenericResource('area2'))
                   ->deny()
                   ->allow('staff')
                   ->deny('staff', ['area1', 'area2']);
        $this->assertFalse($this->acl->isAllowed('staff', 'area1'));
    }

    /**
     * Ensures that for a particular Role, a deny rule on a specific privilege is honored before an allow
     * rule on the entire ACL
     *
     * @return void
     */
    public function testRoleDefaultAllowRuleWithPrivilegeDenyRule()
    {
        $this->acl->addRole(new Role\GenericRole('guest'))
                   ->addRole(new Role\GenericRole('staff'), 'guest')
                   ->deny()
                   ->allow('staff')
                   ->deny('staff', null, ['privilege1', 'privilege2']);
        $this->assertFalse($this->acl->isAllowed('staff', null, 'privilege1'));
    }

    /**
     * Ensure that basic rule removal works
     *
     * @return void
     */
    public function testRulesRemove()
    {
        $this->acl->allow(null, null, ['privilege1', 'privilege2']);
        $this->assertFalse($this->acl->isAllowed());
        $this->assertTrue($this->acl->isAllowed(null, null, 'privilege1'));
        $this->assertTrue($this->acl->isAllowed(null, null, 'privilege2'));
        $this->acl->removeAllow(null, null, 'privilege1');
        $this->assertFalse($this->acl->isAllowed(null, null, 'privilege1'));
        $this->assertTrue($this->acl->isAllowed(null, null, 'privilege2'));
    }

    /**
     * Ensures that removal of a Role results in its rules being removed
     *
     * @return void
     */
    public function testRuleRoleRemove()
    {
        $this->acl->addRole(new Role\GenericRole('guest'))
                   ->allow('guest');
        $this->assertTrue($this->acl->isAllowed('guest'));
        $this->acl->removeRole('guest');
        try {
            $this->acl->isAllowed('guest');
            $this->fail(
                'Expected Zend\Permissions\Acl\Role\Exception not thrown upon isAllowed() on non-existent Role'
            );
        } catch (Acl\Exception\InvalidArgumentException $e) {
            $this->assertContains('not found', $e->getMessage());
        }
        $this->acl->addRole(new Role\GenericRole('guest'));
        $this->assertFalse($this->acl->isAllowed('guest'));
    }

    /**
     * Ensures that removal of all Roles results in Role-specific rules being removed
     *
     * @return void
     */
    public function testRuleRoleRemoveAll()
    {
        $this->acl->addRole(new Role\GenericRole('guest'))
                   ->allow('guest');
        $this->assertTrue($this->acl->isAllowed('guest'));
        $this->acl->removeRoleAll();
        try {
            $this->acl->isAllowed('guest');
            $this->fail(
                'Expected Zend\Permissions\Acl\Role\Exception not thrown upon isAllowed() on non-existent Role'
            );
        } catch (Acl\Exception\InvalidArgumentException $e) {
            $this->assertContains('not found', $e->getMessage());
        }
        $this->acl->addRole(new Role\GenericRole('guest'));
        $this->assertFalse($this->acl->isAllowed('guest'));
    }

    /**
     * Ensures that removal of a Resource results in its rules being removed
     *
     * @return void
     */
    public function testRulesResourceRemove()
    {
        $this->acl->addResource(new Resource\GenericResource('area'))
                   ->allow(null, 'area');
        $this->assertTrue($this->acl->isAllowed(null, 'area'));
        $this->acl->removeResource('area');
        try {
            $this->acl->isAllowed(null, 'area');
            $this->fail(
                'Expected Zend\Permissions\Acl\Exception not thrown upon isAllowed() on non-existent Resource'
            );
        } catch (Acl\Exception\ExceptionInterface $e) {
            $this->assertContains('not found', $e->getMessage());
        }
        $this->acl->addResource(new Resource\GenericResource('area'));
        $this->assertFalse($this->acl->isAllowed(null, 'area'));
    }

    /**
     * Ensures that removal of all Resources results in Resource-specific rules being removed
     *
     * @return void
     */
    public function testRulesResourceRemoveAll()
    {
        $this->acl->addResource(new Resource\GenericResource('area'))
                   ->allow(null, 'area');
        $this->assertTrue($this->acl->isAllowed(null, 'area'));
        $this->acl->removeResourceAll();
        try {
            $this->acl->isAllowed(null, 'area');
            $this->fail(
                'Expected Zend\Permissions\Acl\Exception\ExceptionInterface not thrown upon '
                . 'isAllowed() on non-existent Resource'
            );
        } catch (Acl\Exception\ExceptionInterface $e) {
            $this->assertContains('not found', $e->getMessage());
        }
        $this->acl->addResource(new Resource\GenericResource('area'));
        $this->assertFalse($this->acl->isAllowed(null, 'area'));
    }

    /**
     * Ensures that an example for a content management system is operable
     *
     * @return void
     */
    public function testCMSExample()
    {
        // Add some roles to the Role registry
        $this->acl->addRole(new Role\GenericRole('guest'))
                   ->addRole(new Role\GenericRole('staff'), 'guest')  // staff inherits permissions from guest
                   ->addRole(new Role\GenericRole('editor'), 'staff') // editor inherits permissions from staff
                   ->addRole(new Role\GenericRole('administrator'));

        // Guest may only view content
        $this->acl->allow('guest', null, 'view');

        // Staff inherits view privilege from guest, but also needs additional privileges
        $this->acl->allow('staff', null, ['edit', 'submit', 'revise']);

        // Editor inherits view, edit, submit, and revise privileges, but also needs additional privileges
        $this->acl->allow('editor', null, ['publish', 'archive', 'delete']);

        // Administrator inherits nothing but is allowed all privileges
        $this->acl->allow('administrator');

        // Access control checks based on above permission sets

        $this->assertTrue($this->acl->isAllowed('guest', null, 'view'));
        $this->assertFalse($this->acl->isAllowed('guest', null, 'edit'));
        $this->assertFalse($this->acl->isAllowed('guest', null, 'submit'));
        $this->assertFalse($this->acl->isAllowed('guest', null, 'revise'));
        $this->assertFalse($this->acl->isAllowed('guest', null, 'publish'));
        $this->assertFalse($this->acl->isAllowed('guest', null, 'archive'));
        $this->assertFalse($this->acl->isAllowed('guest', null, 'delete'));
        $this->assertFalse($this->acl->isAllowed('guest', null, 'unknown'));
        $this->assertFalse($this->acl->isAllowed('guest'));

        $this->assertTrue($this->acl->isAllowed('staff', null, 'view'));
        $this->assertTrue($this->acl->isAllowed('staff', null, 'edit'));
        $this->assertTrue($this->acl->isAllowed('staff', null, 'submit'));
        $this->assertTrue($this->acl->isAllowed('staff', null, 'revise'));
        $this->assertFalse($this->acl->isAllowed('staff', null, 'publish'));
        $this->assertFalse($this->acl->isAllowed('staff', null, 'archive'));
        $this->assertFalse($this->acl->isAllowed('staff', null, 'delete'));
        $this->assertFalse($this->acl->isAllowed('staff', null, 'unknown'));
        $this->assertFalse($this->acl->isAllowed('staff'));

        $this->assertTrue($this->acl->isAllowed('editor', null, 'view'));
        $this->assertTrue($this->acl->isAllowed('editor', null, 'edit'));
        $this->assertTrue($this->acl->isAllowed('editor', null, 'submit'));
        $this->assertTrue($this->acl->isAllowed('editor', null, 'revise'));
        $this->assertTrue($this->acl->isAllowed('editor', null, 'publish'));
        $this->assertTrue($this->acl->isAllowed('editor', null, 'archive'));
        $this->assertTrue($this->acl->isAllowed('editor', null, 'delete'));
        $this->assertFalse($this->acl->isAllowed('editor', null, 'unknown'));
        $this->assertFalse($this->acl->isAllowed('editor'));

        $this->assertTrue($this->acl->isAllowed('administrator', null, 'view'));
        $this->assertTrue($this->acl->isAllowed('administrator', null, 'edit'));
        $this->assertTrue($this->acl->isAllowed('administrator', null, 'submit'));
        $this->assertTrue($this->acl->isAllowed('administrator', null, 'revise'));
        $this->assertTrue($this->acl->isAllowed('administrator', null, 'publish'));
        $this->assertTrue($this->acl->isAllowed('administrator', null, 'archive'));
        $this->assertTrue($this->acl->isAllowed('administrator', null, 'delete'));
        $this->assertTrue($this->acl->isAllowed('administrator', null, 'unknown'));
        $this->assertTrue($this->acl->isAllowed('administrator'));

        // Some checks on specific areas, which inherit access controls from the root ACL node
        $this->acl->addResource(new Resource\GenericResource('newsletter'))
                   ->addResource(new Resource\GenericResource('pending'), 'newsletter')
                   ->addResource(new Resource\GenericResource('gallery'))
                   ->addResource(new Resource\GenericResource('profiles', 'gallery'))
                   ->addResource(new Resource\GenericResource('config'))
                   ->addResource(new Resource\GenericResource('hosts'), 'config');
        $this->assertTrue($this->acl->isAllowed('guest', 'pending', 'view'));
        $this->assertTrue($this->acl->isAllowed('staff', 'profiles', 'revise'));
        $this->assertTrue($this->acl->isAllowed('staff', 'pending', 'view'));
        $this->assertTrue($this->acl->isAllowed('staff', 'pending', 'edit'));
        $this->assertFalse($this->acl->isAllowed('staff', 'pending', 'publish'));
        $this->assertFalse($this->acl->isAllowed('staff', 'pending'));
        $this->assertFalse($this->acl->isAllowed('editor', 'hosts', 'unknown'));
        $this->assertTrue($this->acl->isAllowed('administrator', 'pending'));

        // Add a new group, marketing, which bases its permissions on staff
        $this->acl->addRole(new Role\GenericRole('marketing'), 'staff');

        // Refine the privilege sets for more specific needs

        // Allow marketing to publish and archive newsletters
        $this->acl->allow('marketing', 'newsletter', ['publish', 'archive']);

        // Allow marketing to publish and archive latest news
        $this->acl->addResource(new Resource\GenericResource('news'))
                   ->addResource(new Resource\GenericResource('latest'), 'news');
        $this->acl->allow('marketing', 'latest', ['publish', 'archive']);

        // Deny staff (and marketing, by inheritance) rights to revise latest news
        $this->acl->deny('staff', 'latest', 'revise');

        // Deny everyone access to archive news announcements
        $this->acl->addResource(new Resource\GenericResource('announcement'), 'news');
        $this->acl->deny(null, 'announcement', 'archive');

        // Access control checks for the above refined permission sets

        $this->assertTrue($this->acl->isAllowed('marketing', null, 'view'));
        $this->assertTrue($this->acl->isAllowed('marketing', null, 'edit'));
        $this->assertTrue($this->acl->isAllowed('marketing', null, 'submit'));
        $this->assertTrue($this->acl->isAllowed('marketing', null, 'revise'));
        $this->assertFalse($this->acl->isAllowed('marketing', null, 'publish'));
        $this->assertFalse($this->acl->isAllowed('marketing', null, 'archive'));
        $this->assertFalse($this->acl->isAllowed('marketing', null, 'delete'));
        $this->assertFalse($this->acl->isAllowed('marketing', null, 'unknown'));
        $this->assertFalse($this->acl->isAllowed('marketing'));

        $this->assertTrue($this->acl->isAllowed('marketing', 'newsletter', 'publish'));
        $this->assertFalse($this->acl->isAllowed('staff', 'pending', 'publish'));
        $this->assertTrue($this->acl->isAllowed('marketing', 'pending', 'publish'));
        $this->assertTrue($this->acl->isAllowed('marketing', 'newsletter', 'archive'));
        $this->assertFalse($this->acl->isAllowed('marketing', 'newsletter', 'delete'));
        $this->assertFalse($this->acl->isAllowed('marketing', 'newsletter'));

        $this->assertTrue($this->acl->isAllowed('marketing', 'latest', 'publish'));
        $this->assertTrue($this->acl->isAllowed('marketing', 'latest', 'archive'));
        $this->assertFalse($this->acl->isAllowed('marketing', 'latest', 'delete'));
        $this->assertFalse($this->acl->isAllowed('marketing', 'latest', 'revise'));
        $this->assertFalse($this->acl->isAllowed('marketing', 'latest'));

        $this->assertFalse($this->acl->isAllowed('marketing', 'announcement', 'archive'));
        $this->assertFalse($this->acl->isAllowed('staff', 'announcement', 'archive'));
        $this->assertFalse($this->acl->isAllowed('administrator', 'announcement', 'archive'));

        $this->assertFalse($this->acl->isAllowed('staff', 'latest', 'publish'));
        $this->assertFalse($this->acl->isAllowed('editor', 'announcement', 'archive'));

        // Remove some previous permission specifications

        // Marketing can no longer publish and archive newsletters
        $this->acl->removeAllow('marketing', 'newsletter', ['publish', 'archive']);

        // Marketing can no longer archive the latest news
        $this->acl->removeAllow('marketing', 'latest', 'archive');

        // Now staff (and marketing, by inheritance) may revise latest news
        $this->acl->removeDeny('staff', 'latest', 'revise');

        // Access control checks for the above refinements

        $this->assertFalse($this->acl->isAllowed('marketing', 'newsletter', 'publish'));
        $this->assertFalse($this->acl->isAllowed('marketing', 'newsletter', 'archive'));

        $this->assertFalse($this->acl->isAllowed('marketing', 'latest', 'archive'));

        $this->assertTrue($this->acl->isAllowed('staff', 'latest', 'revise'));
        $this->assertTrue($this->acl->isAllowed('marketing', 'latest', 'revise'));

        // Grant marketing all permissions on the latest news
        $this->acl->allow('marketing', 'latest');

        // Access control checks for the above refinement
        $this->assertTrue($this->acl->isAllowed('marketing', 'latest', 'archive'));
        $this->assertTrue($this->acl->isAllowed('marketing', 'latest', 'publish'));
        $this->assertTrue($this->acl->isAllowed('marketing', 'latest', 'edit'));
        $this->assertTrue($this->acl->isAllowed('marketing', 'latest'));
    }

    /**
     * Ensures that the $onlyParents argument to inheritsRole() works
     *
     * @return void
     * @group  ZF-2502
     */
    public function testRoleInheritanceSupportsCheckingOnlyParents()
    {
        $this->acl->addRole(new Role\GenericRole('grandparent'))
                   ->addRole(new Role\GenericRole('parent'), 'grandparent')
                   ->addRole(new Role\GenericRole('child'), 'parent');
        $this->assertFalse($this->acl->inheritsRole('child', 'grandparent', true));
    }

    /**
     * Ensures that the solution for ZF-2234 works as expected
     *
     * @return void
     * @group  ZF-2234
     */
    public function testAclInternalDFSMethodsBehaveProperly()
    {
        $acl = new TestAsset\ExtendedAclZF2234();

        $someResource = new Resource\GenericResource('someResource');
        $someRole     = new Role\GenericRole('someRole');

        $acl->addResource($someResource)
            ->addRole($someRole);

        $nullValue     = null;
        $nullReference =& $nullValue;

        try {
            $acl->exroleDFSVisitAllPrivileges($someRole, $someResource, $nullReference);
            $this->fail('Expected Zend\Permissions\Acl\Exception not thrown');
        } catch (Acl\Exception\ExceptionInterface $e) {
            $this->assertEquals('$dfs parameter may not be null', $e->getMessage());
        }

        try {
            $acl->exroleDFSOnePrivilege($someRole, $someResource, null);
            $this->fail('Expected Zend\Permissions\Acl\Exception not thrown');
        } catch (Acl\Exception\ExceptionInterface $e) {
            $this->assertEquals('$privilege parameter may not be null', $e->getMessage());
        }

        try {
            $acl->exroleDFSVisitOnePrivilege($someRole, $someResource, null);
            $this->fail('Expected Zend\Permissions\Acl\Exception not thrown');
        } catch (Acl\Exception\ExceptionInterface $e) {
            $this->assertEquals('$privilege parameter may not be null', $e->getMessage());
        }

        try {
            $acl->exroleDFSVisitOnePrivilege($someRole, $someResource, 'somePrivilege', $nullReference);
            $this->fail('Expected Zend\Permissions\Acl\Exception not thrown');
        } catch (Acl\Exception\ExceptionInterface $e) {
            $this->assertEquals('$dfs parameter may not be null', $e->getMessage());
        }
    }

    public function testRolesAreEvaluatedFIFO()
    {
        $acl = $this->loadUseCase1();

        $user = new Role\GenericRole('hierarchy-admin');
        $blogPost = new Resource\GenericResource('hierarchy-resource');

        $this->assertTrue($acl->isAllowed($user, $blogPost, 'assert'));
    }

    /**
     * @group ZF-1721
     */
    public function testAclAssertionsGetProperRoleWhenInheritenceIsUsed()
    {
        $acl = $this->loadUseCase1();

        $user = new Role\GenericRole('publisher');
        $blogPost = new Resource\GenericResource('blogPost');

        /**
         * @var ZendTest\Permissions\Acl\UseCase1\UserIsBlogPostOwnerAssertion
         */
        $assertion = $acl->customAssertion;

        $this->assertTrue($acl->isAllowed($user, $blogPost, 'modify'));

        $this->assertEquals('publisher', $assertion->lastAssertRole->getRoleId());
    }

    /**
     *
     * @group ZF-1722
     */
    public function testAclAssertionsGetOriginalIsAllowedObjects()
    {
        $acl = $this->loadUseCase1();

        $user     = new TestAsset\UseCase1\User();
        $blogPost = new TestAsset\UseCase1\BlogPost();

        $this->assertTrue($acl->isAllowed($user, $blogPost, 'view'));

        /**
         * @var ZendTest\Permissions\Acl\UseCase1\UserIsBlogPostOwnerAssertion
         */
        $assertion = $acl->customAssertion;

        $assertion->assertReturnValue = true;
        $user->role = 'contributor';
        $this->assertTrue($acl->isAllowed($user, $blogPost, 'modify'), 'Assertion should return true');
        $assertion->assertReturnValue = false;
        $this->assertFalse($acl->isAllowed($user, $blogPost, 'modify'), 'Assertion should return false');

        // check to see if the last assertion has the proper objects
        $this->assertInstanceOf(
            'ZendTest\Permissions\Acl\TestAsset\UseCase1\User',
            $assertion->lastAssertRole,
            'Assertion did not receive proper role object'
        );
        $this->assertInstanceOf(
            'ZendTest\Permissions\Acl\TestAsset\UseCase1\BlogPost',
            $assertion->lastAssertResource,
            'Assertion did not receive proper resource object'
        );
    }

    /**
     *
     * @return Zend_Acl_UseCase1_Acl
     */
    protected function loadUseCase1()
    {
        return new TestAsset\UseCase1\Acl();
    }

    /**
     * Confirm that deleting a role after allowing access to all roles
     * raise undefined index error
     *
     * @group ZF-5700
     */
    public function testRemovingRoleAfterItWasAllowedAccessToAllResourcesGivesError()
    {
        $acl = new Acl\Acl();
        $acl->addRole(new Role\GenericRole('test0'));
        $acl->addRole(new Role\GenericRole('test1'));
        $acl->addRole(new Role\GenericRole('test2'));
        $acl->addResource(new Resource\GenericResource('Test'));

        $acl->allow(null, 'Test', 'xxx');

        // error test
        $acl->removeRole('test0');

        // Check after fix
        $this->assertFalse($acl->hasRole('test0'));
    }

    /**
     * @group ZF-8039
     *
     * Meant to test for the (in)existence of this notice:
     * "Notice: Undefined index: allPrivileges in lib/Zend/Acl.php on line 682"
     */
    public function testMethodRemoveAllowDoesNotThrowNotice()
    {
        $acl = new Acl\Acl();
        $acl->addRole('admin');
        $acl->addResource('blog');
        $acl->allow('admin', 'blog', 'read');
        $acl->removeAllow(['admin'], ['blog'], null);

        $this->assertTrue(true);
    }

    public function testRoleObjectImplementsToString()
    {
        $role = new Role\GenericRole('_fooBar_');
        $this->assertEquals('_fooBar_', (string) $role);
    }

    public function testResourceObjectImplementsToString()
    {
        $resource = new Resource\GenericResource('_fooBar_');
        $this->assertEquals('_fooBar_', (string) $resource);
    }

    /**
     * @group ZF-7973
     */
    public function testAclPassesPrivilegeToAssertClass()
    {
        $assertion = new TestAsset\AssertionZF7973();

        $acl = new Acl\Acl();
        $acl->addRole('role');
        $acl->addResource('resource');
        $acl->allow('role', null, null, $assertion);
        $allowed = $acl->isAllowed('role', 'resource', 'privilege', $assertion);

        $this->assertTrue($allowed);
    }

    /**
     * @group ZF-8468
     */
    public function testgetRoles()
    {
        $this->assertEquals([], $this->acl->getRoles());

        $roleGuest = new Role\GenericRole('guest');
        $this->acl->addRole($roleGuest);
        $this->acl->addRole(new Role\GenericRole('staff'), $roleGuest);
        $this->acl->addRole(new Role\GenericRole('editor'), 'staff');
        $this->acl->addRole(new Role\GenericRole('administrator'));

        $expected = ['guest', 'staff','editor','administrator'];
        $this->assertEquals($expected, $this->acl->getRoles());
    }

    /**
     * @group ZF-8468
     */
    public function testgetResources()
    {
        $this->assertEquals([], $this->acl->getResources());

        $this->acl->addResource(new Resource\GenericResource('someResource'));
        $this->acl->addResource(new Resource\GenericResource('someOtherResource'));

        $expected = ['someResource', 'someOtherResource'];
        $this->assertEquals($expected, $this->acl->getResources());
    }

    /**
     * @group ZF-9643
     */
    public function testRemoveAllowWithNullResourceAppliesToAllResources()
    {
        $this->acl->addRole('guest');
        $this->acl->addResource('blogpost');
        $this->acl->addResource('newsletter');
        $this->acl->allow('guest', 'blogpost', 'read');
        $this->acl->allow('guest', 'newsletter', 'read');
        $this->assertTrue($this->acl->isAllowed('guest', 'blogpost', 'read'));
        $this->assertTrue($this->acl->isAllowed('guest', 'newsletter', 'read'));

        $this->acl->removeAllow('guest', 'newsletter', 'read');
        $this->assertTrue($this->acl->isAllowed('guest', 'blogpost', 'read'));
        $this->assertFalse($this->acl->isAllowed('guest', 'newsletter', 'read'));

        $this->acl->removeAllow('guest', null, 'read');
        $this->assertFalse($this->acl->isAllowed('guest', 'blogpost', 'read'));
        $this->assertFalse($this->acl->isAllowed('guest', 'newsletter', 'read'));

        // ensure allow null/all resoures works
        $this->acl->allow('guest', null, 'read');
        $this->assertTrue($this->acl->isAllowed('guest', 'blogpost', 'read'));
        $this->assertTrue($this->acl->isAllowed('guest', 'newsletter', 'read'));
    }

    /**
     * @group ZF-9643
     */
    public function testRemoveDenyWithNullResourceAppliesToAllResources()
    {
        $this->acl->addRole('guest');
        $this->acl->addResource('blogpost');
        $this->acl->addResource('newsletter');

        $this->acl->allow();
        $this->acl->deny('guest', 'blogpost', 'read');
        $this->acl->deny('guest', 'newsletter', 'read');
        $this->assertFalse($this->acl->isAllowed('guest', 'blogpost', 'read'));
        $this->assertFalse($this->acl->isAllowed('guest', 'newsletter', 'read'));

        $this->acl->removeDeny('guest', 'newsletter', 'read');
        $this->assertFalse($this->acl->isAllowed('guest', 'blogpost', 'read'));
        $this->assertTrue($this->acl->isAllowed('guest', 'newsletter', 'read'));

        $this->acl->removeDeny('guest', null, 'read');
        $this->assertTrue($this->acl->isAllowed('guest', 'blogpost', 'read'));
        $this->assertTrue($this->acl->isAllowed('guest', 'newsletter', 'read'));

        // ensure deny null/all resources works
        $this->acl->deny('guest', null, 'read');
        $this->assertFalse($this->acl->isAllowed('guest', 'blogpost', 'read'));
        $this->assertFalse($this->acl->isAllowed('guest', 'newsletter', 'read'));
    }

    /**
     * @group ZF2-3454
     */
    public function testAclResourcePermissionsAreInheritedWithMultilevelResourcesAndDenyPolicy()
    {
        $this->acl->addRole('guest');
        $this->acl->addResource('blogposts');
        $this->acl->addResource('feature', 'blogposts');
        $this->acl->addResource('post_1', 'feature');
        $this->acl->addResource('post_2', 'feature');

        // Allow a guest to read feature posts and
        // comment on everything except feature posts.
        $this->acl->deny();
        $this->acl->allow('guest', 'feature', 'read');
        $this->acl->allow('guest', null, 'comment');
        $this->acl->deny('guest', 'feature', 'comment');

        $this->assertFalse($this->acl->isAllowed('guest', 'feature', 'write'));
        $this->assertTrue($this->acl->isAllowed('guest', 'post_1', 'read'));
        $this->assertTrue($this->acl->isAllowed('guest', 'post_2', 'read'));

        $this->assertFalse($this->acl->isAllowed('guest', 'post_1', 'comment'));
        $this->assertFalse($this->acl->isAllowed('guest', 'post_2', 'comment'));
    }

    public function testSetRuleWorksWithResourceInterface()
    {
        $roleGuest = new Role\GenericRole('guest');
        $this->acl->addRole($roleGuest);

        $resourceFoo = new Resource\GenericResource('foo');
        $this->acl->addResource($resourceFoo);

        $this->acl->setRule(Acl\Acl::OP_ADD, Acl\Acl::TYPE_ALLOW, $roleGuest, $resourceFoo);

        $this->assertTrue(true);
    }

    /**
     * @group 4226
     */
    public function testAllowNullPermissionAfterResourcesExistShouldAllowAllPermissionsForRole()
    {
        $this->acl->addRole('admin');
        $this->acl->addResource('newsletter');
        $this->acl->allow('admin');
        $this->assertTrue($this->acl->isAllowed('admin'));
    }
}
