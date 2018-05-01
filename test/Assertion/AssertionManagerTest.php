<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */
namespace ZendTest\Permissions\Acl\Assertion;

use PHPUnit\Framework\TestCase;
use Zend\Permissions\Acl\Assertion\AssertionInterface;
use Zend\Permissions\Acl\Assertion\AssertionManager;
use Zend\ServiceManager\Exception\InvalidServiceException;
use Zend\ServiceManager\ServiceManager;

class AssertionManagerTest extends TestCase
{
    protected $manager;

    protected function setUp()
    {
        $this->manager = new AssertionManager(new ServiceManager);
    }

    public function testValidatePlugin()
    {
        $assertion = $this->getMockForAbstractClass(AssertionInterface::class);

        $this->assertNull($this->manager->validate($assertion));

        $this->expectException(InvalidServiceException::class);

        $this->manager->validate('invalid plugin');
    }
}
