<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */
namespace ZendTest\Permissions\Acl\Assertion;

use InvalidArgumentException as PHPInvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Zend\Permissions\Acl\Acl;
use Zend\Permissions\Acl\Assertion\AssertionAggregate;
use Zend\Permissions\Acl\Assertion\Exception\InvalidAssertionException;
use Zend\Permissions\Acl\Exception\InvalidArgumentException;
use Zend\Permissions\Acl\Exception\RuntimeException;
use Zend\Permissions\Acl\Resource\GenericResource;
use Zend\Permissions\Acl\Role\GenericRole;

class AssertionAggregateTest extends TestCase
{
    protected $assertionAggregate;

    protected function setUp()
    {
        $this->assertionAggregate = new AssertionAggregate();
    }

    public function testAddAssertion()
    {
        $assertion = $this->getMockForAbstractClass('\Zend\Permissions\Acl\Assertion\AssertionInterface');
        $this->assertionAggregate->addAssertion($assertion);

        $this->assertAttributeEquals([
            $assertion
        ], 'assertions', $this->assertionAggregate);

        $aggregate = $this->assertionAggregate->addAssertion('other.assertion');
        $this->assertAttributeEquals([
            $assertion,
            'other.assertion'
        ], 'assertions', $this->assertionAggregate);

        // test fluent interface
        $this->assertSame($this->assertionAggregate, $aggregate);

        return clone $this->assertionAggregate;
    }

    public function testAddAssertions()
    {
        $assertions[] = $this->getMockForAbstractClass('\Zend\Permissions\Acl\Assertion\AssertionInterface');
        $assertions[] = $this->getMockForAbstractClass('\Zend\Permissions\Acl\Assertion\AssertionInterface');

        $aggregate = $this->assertionAggregate->addAssertions($assertions);

        $this->assertAttributeEquals($assertions, 'assertions', $this->assertionAggregate);

        // test fluent interface
        $this->assertSame($this->assertionAggregate, $aggregate);
    }

    /**
     * @depends testAddAssertion
     */
    public function testClearAssertions(AssertionAggregate $assertionAggregate)
    {
        $this->assertAttributeCount(2, 'assertions', $assertionAggregate);

        $aggregate = $assertionAggregate->clearAssertions();

        $this->assertAttributeEmpty('assertions', $assertionAggregate);

        // test fluent interface
        $this->assertSame($assertionAggregate, $aggregate);
    }

    public function testDefaultModeValue()
    {
        $this->assertAttributeEquals(AssertionAggregate::MODE_ALL, 'mode', $this->assertionAggregate);
    }

    /**
     * @dataProvider getDataForTestSetMode
     */
    public function testSetMode($mode, $exception = false)
    {
        if ($exception) {
            $this->expectException(InvalidArgumentException::class);
            $this->assertionAggregate->setMode($mode);
        } else {
            $this->assertionAggregate->setMode($mode);
            $this->assertAttributeEquals($mode, 'mode', $this->assertionAggregate);
        }
    }

    public static function getDataForTestSetMode()
    {
        return [
            [
                AssertionAggregate::MODE_ALL
            ],
            [
                AssertionAggregate::MODE_AT_LEAST_ONE
            ],
            [
                'invalid mode',
                true
            ]
        ];
    }

    public function testManagerAccessors()
    {
        $manager = $this->getMockBuilder('Zend\Permissions\Acl\Assertion\AssertionManager')
                        ->disableOriginalConstructor()
                        ->getMock();

        $aggregate = $this->assertionAggregate->setAssertionManager($manager);
        $this->assertAttributeEquals($manager, 'assertionManager', $this->assertionAggregate);
        $this->assertEquals($manager, $this->assertionAggregate->getAssertionManager());
        $this->assertSame($this->assertionAggregate, $aggregate);
    }

    public function testCallingAssertWillFetchAssertionFromManager()
    {
        $acl = $this->getMockBuilder(Acl::class)
            ->getMock()
            ;

        $role = $this->getMockBuilder(GenericRole::class)
            ->setConstructorArgs(['test.role'])
            ->getMock()
            ;

        $resource = $this->getMockBuilder(GenericResource::class)
            ->setConstructorArgs(['test.resource'])
            ->getMock()
            ;

        $assertion = $this->getMockForAbstractClass('Zend\Permissions\Acl\Assertion\AssertionInterface');
        $assertion->expects($this->once())
            ->method('assert')
            ->will($this->returnValue(true));

        $manager = $this->getMockBuilder('Zend\Permissions\Acl\Assertion\AssertionManager')
                        ->disableOriginalConstructor()
                        ->getMock();

        $manager->expects($this->once())
            ->method('get')
            ->with('assertion')
            ->will($this->returnValue($assertion));

        $this->assertionAggregate->setAssertionManager($manager);
        $this->assertionAggregate->addAssertion('assertion');

        $this->assertTrue($this->assertionAggregate->assert($acl, $role, $resource, 'privilege'));
    }

    public function testAssertThrowsAnExceptionWhenReferingToNonExistentAssertion()
    {
        $acl = $this->getMockBuilder(Acl::class)
            ->getMock()
            ;

        $role = $this->getMockBuilder(GenericRole::class)
            ->setConstructorArgs(['test.role'])
            ->getMock()
            ;

        $resource = $this->getMockBuilder(GenericResource::class)
            ->setConstructorArgs(['test.resource'])
            ->getMock()
            ;

        $manager = $this->getMockBuilder('Zend\Permissions\Acl\Assertion\AssertionManager')
                        ->disableOriginalConstructor()
                        ->getMock();

        $manager->expects($this->once())
            ->method('get')
            ->with('assertion')
            ->will($this->throwException(new PHPInvalidArgumentException()));

        $this->assertionAggregate->setAssertionManager($manager);

        $this->expectException(InvalidAssertionException::class);
        $this->assertionAggregate->addAssertion('assertion');
        $this->assertionAggregate->assert($acl, $role, $resource, 'privilege');
    }

    public function testAssertWithModeAll()
    {
        $acl = $this->getMockBuilder(Acl::class)
            ->getMock()
            ;

        $role = $this->getMockBuilder(GenericRole::class)
            ->setConstructorArgs(['test.role'])
            ->getMock()
            ;

        $resource = $this->getMockBuilder(GenericResource::class)
            ->setConstructorArgs(['test.resource'])
            ->getMock()
            ;

        $assertions[] = $this->getMockForAbstractClass('\Zend\Permissions\Acl\Assertion\AssertionInterface');
        $assertions[] = $this->getMockForAbstractClass('\Zend\Permissions\Acl\Assertion\AssertionInterface');
        $assertions[] = $this->getMockForAbstractClass('\Zend\Permissions\Acl\Assertion\AssertionInterface');

        $assertions[0]->expects($this->once())
            ->method('assert')
            ->with($acl, $role, $resource, 'privilege')
            ->will($this->returnValue(true));
        $assertions[1]->expects($this->once())
            ->method('assert')
            ->with($acl, $role, $resource, 'privilege')
            ->will($this->returnValue(true));
        $assertions[2]->expects($this->once())
            ->method('assert')
            ->with($acl, $role, $resource, 'privilege')
            ->will($this->returnValue(true));

        foreach ($assertions as $assertion) {
            $this->assertionAggregate->addAssertion($assertion);
        }

        $this->assertTrue($this->assertionAggregate->assert($acl, $role, $resource, 'privilege'));
    }

    public function testAssertWithModeAtLeastOne()
    {
        $acl = $this->getMockBuilder(Acl::class)
            ->getMock()
            ;

        $role = $this->getMockBuilder(GenericRole::class)
            ->setConstructorArgs(['test.role'])
            ->getMock()
            ;

        $resource = $this->getMockBuilder(GenericResource::class)
            ->setConstructorArgs(['test.resource'])
            ->getMock()
            ;

        $assertions[] = $this->getMockForAbstractClass('\Zend\Permissions\Acl\Assertion\AssertionInterface');
        $assertions[] = $this->getMockForAbstractClass('\Zend\Permissions\Acl\Assertion\AssertionInterface');
        $assertions[] = $this->getMockForAbstractClass('\Zend\Permissions\Acl\Assertion\AssertionInterface');

        $assertions[0]->expects($this->once())
            ->method('assert')
            ->with($acl, $role, $resource, 'privilege')
            ->will($this->returnValue(false));
        $assertions[1]->expects($this->once())
            ->method('assert')
            ->with($acl, $role, $resource, 'privilege')
            ->will($this->returnValue(false));
        $assertions[2]->expects($this->once())
            ->method('assert')
            ->with($acl, $role, $resource, 'privilege')
            ->will($this->returnValue(true));

        foreach ($assertions as $assertion) {
            $this->assertionAggregate->addAssertion($assertion);
        }

        $this->assertionAggregate->setMode(AssertionAggregate::MODE_AT_LEAST_ONE);
        $this->assertTrue($this->assertionAggregate->assert($acl, $role, $resource, 'privilege'));
    }

    public function testDoesNotAssertWithModeAll()
    {
        $acl = $this->getMockBuilder(Acl::class)
            ->getMock()
            ;

        $role = $this->getMockBuilder(GenericRole::class)
            ->setConstructorArgs(['test.role'])
            ->setMethods(['assert'])
            ->getMock()
            ;

        $resource = $this->getMockBuilder(GenericResource::class)
            ->setConstructorArgs(['test.resource'])
            ->setMethods(['assert'])
            ->getMock()
            ;

        $assertions[] = $this->getMockForAbstractClass('\Zend\Permissions\Acl\Assertion\AssertionInterface');
        $assertions[] = $this->getMockForAbstractClass('\Zend\Permissions\Acl\Assertion\AssertionInterface');
        $assertions[] = $this->getMockForAbstractClass('\Zend\Permissions\Acl\Assertion\AssertionInterface');

        $assertions[0]->expects($this->once())
            ->method('assert')
            ->with($acl, $role, $resource, 'privilege')
            ->will($this->returnValue(true));
        $assertions[1]->expects($this->once())
            ->method('assert')
            ->with($acl, $role, $resource, 'privilege')
            ->will($this->returnValue(true));
        $assertions[2]->expects($this->once())
            ->method('assert')
            ->with($acl, $role, $resource, 'privilege')
            ->will($this->returnValue(false));

        foreach ($assertions as $assertion) {
            $this->assertionAggregate->addAssertion($assertion);
        }

        $this->assertFalse($this->assertionAggregate->assert($acl, $role, $resource, 'privilege'));
    }

    public function testDoesNotAssertWithModeAtLeastOne()
    {
        $acl = $this->getMockBuilder(Acl::class)
            ->getMock()
            ;

        $role = $this->getMockBuilder(GenericRole::class)
            ->setConstructorArgs(['test.role'])
            ->setMethods(['assert'])
            ->getMock()
            ;

        $resource = $this->getMockBuilder(GenericResource::class)
            ->setConstructorArgs(['test.resource'])
            ->setMethods(['assert'])
            ->getMock()
            ;

        $assertions[] = $this->getMockForAbstractClass('\Zend\Permissions\Acl\Assertion\AssertionInterface');
        $assertions[] = $this->getMockForAbstractClass('\Zend\Permissions\Acl\Assertion\AssertionInterface');
        $assertions[] = $this->getMockForAbstractClass('\Zend\Permissions\Acl\Assertion\AssertionInterface');

        $assertions[0]->expects($this->once())
            ->method('assert')
            ->with($acl, $role, $resource, 'privilege')
            ->will($this->returnValue(false));
        $assertions[1]->expects($this->once())
            ->method('assert')
            ->with($acl, $role, $resource, 'privilege')
            ->will($this->returnValue(false));
        $assertions[2]->expects($this->once())
            ->method('assert')
            ->with($acl, $role, $resource, 'privilege')
            ->will($this->returnValue(false));

        foreach ($assertions as $assertion) {
            $this->assertionAggregate->addAssertion($assertion);
        }

        $this->assertionAggregate->setMode(AssertionAggregate::MODE_AT_LEAST_ONE);
        $this->assertFalse($this->assertionAggregate->assert($acl, $role, $resource, 'privilege'));
    }

    public function testAssertThrowsAnExceptionWhenNoAssertionIsAggregated()
    {
        $acl = $this->getMockBuilder(Acl::class)
            ->getMock()
            ;

        $role = $this->getMockBuilder(GenericRole::class)
            ->setConstructorArgs(['test.role'])
            ->setMethods(['assert'])
            ->getMock()
            ;

        $resource = $this->getMockBuilder(GenericResource::class)
            ->setConstructorArgs(['test.resource'])
            ->setMethods(['assert'])
            ->getMock()
            ;

        $this->expectException(RuntimeException::class);

        $this->assertionAggregate->assert($acl, $role, $resource, 'privilege');
    }
}
