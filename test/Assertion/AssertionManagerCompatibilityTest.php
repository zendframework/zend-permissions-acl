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
use Zend\Permissions\Acl\Exception\InvalidArgumentException;
use Zend\ServiceManager\ServiceManager;
use Zend\ServiceManager\Test\CommonPluginManagerTrait;

class AssertionManagerCompatibilityTest extends TestCase
{
    use CommonPluginManagerTrait;

    public function setExpectedException($exception, $message = '', $code = null)
    {
        $this->expectException($exception, $message, $code);
    }

    protected function getPluginManager()
    {
        return new AssertionManager(new ServiceManager());
    }

    protected function getV2InvalidPluginException()
    {
        return InvalidArgumentException::class;
    }

    protected function getInstanceOf()
    {
        return AssertionInterface::class;
    }

    public function testPluginAliasesResolve()
    {
        $this->markTestSkipped(
            'No aliases yet defined; remove implementation if/when aliases are added to AssertionManager'
        );
    }
}
