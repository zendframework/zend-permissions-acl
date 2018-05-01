<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */
namespace ZendTest\Permissions\Acl\Assertion;

use PHPUnit_Framework_TestCase;
use Zend\Permissions\Acl\Assertion\ExpressionAssertion;
use Zend\Permissions\Acl\Acl;
use ZendTest\Permissions\Acl\TestAsset\UseCase2\User;
use ZendTest\Permissions\Acl\TestAsset\UseCase2\BlogPost;
use Zend\Permissions\Acl\Assertion\Exception\InvalidAssertionException;
use Zend\Permissions\Acl\Exception\RuntimeException;

/**
 * @author Nikola Posa <posa.nikola@gmail.com>
 */
class ExpressionAssertionTest extends PHPUnit_Framework_TestCase
{
    public function testFromPropertiesCreation()
    {
        $assertion = ExpressionAssertion::fromProperties(
            'foo',
            '=',
            'bar'
        );

        $this->assertInstanceOf(ExpressionAssertion::class, $assertion);
    }

    public function testFromArrayCreation()
    {
        $assertion = ExpressionAssertion::fromArray([
            'left' => 'foo',
            'operator' => '=',
            'right' => 'bar'
        ]);

        $this->assertInstanceOf(ExpressionAssertion::class, $assertion);
    }

    public function testExceptionIsRaisedInCaseOfInvalidExpressionArray()
    {
        $this->setExpectedException(
            InvalidAssertionException::class,
            "Expression assertion requires 'left', 'operator' and 'right' to be supplied"
        );

        ExpressionAssertion::fromArray(['left' => 'test', 'foo' => 'bar']);
    }

    public function testExceptionIsRaisedInCaseOfInvalidExpressionContextOperandType()
    {
        $this->setExpectedException(
            InvalidAssertionException::class,
            'Expression assertion context operand must be string'
        );

        ExpressionAssertion::fromProperties(
            [ExpressionAssertion::OPERAND_CONTEXT_PROPERTY => 123],
            'in',
            'test'
        );
    }

    public function testExceptionIsRaisedInCaseOfInvalidExpressionOperator()
    {
        $this->setExpectedException(
            InvalidAssertionException::class,
            'Provided expression assertion operator is not supported'
        );

        ExpressionAssertion::fromProperties(
            'test',
            'invalid',
            'test'
        );
    }

    /**
     * @dataProvider getExpressions
     */
    public function testExpressionsEvaluation(array $expression, $role, $resource, $privilege, $expectedAssert)
    {
        $assertion = ExpressionAssertion::fromArray($expression);

        $this->assertThat(
            $assertion->assert(new Acl(), $role, $resource, $privilege),
            $expectedAssert ? $this->isTrue() : $this->isFalse()
        );
    }

    public function getExpressions()
    {
        $author3 = new User([
            'username' => 'author3',
        ]);
        $post3 = new BlogPost([
            'author' => $author3,
        ]);

        return [
            [
                'expression' => [
                    'left' => [ExpressionAssertion::OPERAND_CONTEXT_PROPERTY => 'role.username'],
                    'operator' => '=',
                    'right' => 'test',
                ],
                'role' => new User([
                    'username' => 'test',
                ]),
                'resource' => new BlogPost(),
                'privilege' => 'read',
                'assert' => true,
            ],
            [
                'expression' => [
                    'left' => [ExpressionAssertion::OPERAND_CONTEXT_PROPERTY => 'role.username'],
                    'operator' => '!=',
                    'right' => 'test',
                ],
                'role' => new User([
                    'username' => 'foobar',
                ]),
                'resource' => new BlogPost(),
                'privilege' => 'read',
                'assert' => true,
            ],
            [
                'expression' => [
                    'left' => [ExpressionAssertion::OPERAND_CONTEXT_PROPERTY => 'role.username'],
                    'operator' => '=',
                    'right' => true,
                ],
                'role' => $author3,
                'resource' => $post3,
                'privilege' => 'read',
                'assert' => true,
            ],
            [
                'expression' => [
                    'left' => [ExpressionAssertion::OPERAND_CONTEXT_PROPERTY => 'role.age'],
                    'operator' => '>',
                    'right' => 20,
                ],
                'role' => new User([
                    'username' => 'foobar',
                    'age' => 15,
                ]),
                'resource' => new BlogPost(),
                'privilege' => 'read',
                'assert' => false,
            ],
            [
                'expression' => [
                    'left' => [ExpressionAssertion::OPERAND_CONTEXT_PROPERTY => 'role.age'],
                    'operator' => '>=',
                    'right' => 20,
                ],
                'role' => new User([
                    'username' => 'foobar',
                    'age' => 20,
                ]),
                'resource' => new BlogPost(),
                'privilege' => 'read',
                'assert' => true,
            ],
            [
                'expression' => [
                    'left' => [ExpressionAssertion::OPERAND_CONTEXT_PROPERTY => 'role.age'],
                    'operator' => '<',
                    'right' => 30,
                ],
                'role' => new User([
                    'username' => 'foobar',
                    'age' => 20,
                ]),
                'resource' => new BlogPost(),
                'privilege' => 'read',
                'assert' => true,
            ],
            [
                'expression' => [
                    'left' => [ExpressionAssertion::OPERAND_CONTEXT_PROPERTY => 'role.age'],
                    'operator' => '<=',
                    'right' => 30,
                ],
                'role' => new User([
                    'username' => 'foobar',
                    'age' => 30,
                ]),
                'resource' => new BlogPost(),
                'privilege' => 'read',
                'assert' => true,
            ],
            [
                'expression' => [
                    'left' => [ExpressionAssertion::OPERAND_CONTEXT_PROPERTY => 'role.username'],
                    'operator' => 'in',
                    'right' => ['foo', 'bar'],
                ],
                'role' => new User([
                    'username' => 'test',
                ]),
                'resource' => new BlogPost(),
                'privilege' => 'read',
                'assert' => false,
            ],
            [
                'expression' => [
                    'left' => [ExpressionAssertion::OPERAND_CONTEXT_PROPERTY => 'role.username'],
                    'operator' => 'nin',
                    'right' => ['foo', 'bar'],
                ],
                'role' => new User([
                    'username' => 'test',
                ]),
                'resource' => new BlogPost(),
                'privilege' => 'read',
                'assert' => true,
            ],
            [
                'expression' => [
                    'left' => [ExpressionAssertion::OPERAND_CONTEXT_PROPERTY => 'role.username'],
                    'operator' => 'regex',
                    'right' => '/foobar/',
                ],
                'role' => new User([
                    'username' => 'test',
                ]),
                'resource' => new BlogPost(),
                'privilege' => 'read',
                'assert' => false,
            ],
            [
                'expression' => [
                    'left' => [ExpressionAssertion::OPERAND_CONTEXT_PROPERTY => 'resource.short_description'],
                    'operator' => 'REGEX',
                    'right' => '/ipsum/',
                ],
                'role' => new User([
                    'username' => 'test',
                ]),
                'resource' => new BlogPost([
                    'title' => 'Test',
                    'content' => 'lorem ipsum dolor sit amet',
                    'short_description' => 'lorem ipsum'
                ]),
                'privilege' => 'read',
                'assert' => true,
            ],
            [
                'expression' => [
                    'left' => [ExpressionAssertion::OPERAND_CONTEXT_PROPERTY => 'role.adult'],
                    'operator' => '=',
                    'right' => true,
                ],
                'role' => new User([
                    'username' => 'test',
                    'age' => 30,
                ]),
                'resource' => new BlogPost(),
                'privilege' => 'read',
                'assert' => true,
            ],
            [
                'expression' => [
                    'left' => [ExpressionAssertion::OPERAND_CONTEXT_PROPERTY => 'privilege'],
                    'operator' => '=',
                    'right' => 'read',
                ],
                'role' => new User([
                    'username' => 'test',
                ]),
                'resource' => new BlogPost(),
                'privilege' => 'update',
                'assert' => false,
            ],
        ];
    }

    public function testExceptionIsRaisedInCaseOfUnknownContextOperand()
    {
        $this->setExpectedException(
            RuntimeException::class,
            "'foobar' is not available in the assertion context"
        );

        $assertion = ExpressionAssertion::fromProperties(
            [ExpressionAssertion::OPERAND_CONTEXT_PROPERTY => 'foobar'],
            '=',
            'test'
        );

        $assertion->assert(new Acl(), new User(), new BlogPost(), 'read');
    }

    public function testExceptionIsRaisedInCaseOfUnknownContextOperandContainingPropertyPath()
    {
        $this->setExpectedException(
            RuntimeException::class,
            "'foo' is not available in the assertion context"
        );

        $assertion = ExpressionAssertion::fromProperties(
            [ExpressionAssertion::OPERAND_CONTEXT_PROPERTY => 'foo.bar'],
            '=',
            'test'
        );

        $assertion->assert(new Acl(), new User(), new BlogPost(), 'read');
    }

    public function testExceptionIsRaisedIfContextObjectPropertyCannotBeResolved()
    {
        $this->setExpectedException(
            RuntimeException::class,
            "'age123' property cannot be resolved on the 'role' object"
        );

        $assertion = ExpressionAssertion::fromProperties(
            [ExpressionAssertion::OPERAND_CONTEXT_PROPERTY => 'role.age123'],
            '=',
            30
        );

        $assertion->assert(new Acl(), new User(), new BlogPost(), 'read');
    }

    public function testExceptionIsRaisedInCaseThatAssertHasBeenInvokedWithoutPassingContext()
    {
        $this->setExpectedException(
            RuntimeException::class,
            "'role' is not available in the assertion context"
        );

        $assertion = ExpressionAssertion::fromProperties(
            [ExpressionAssertion::OPERAND_CONTEXT_PROPERTY => 'role.username'],
            '=',
            'test'
        );

        $assertion->assert(new Acl());
    }

    public function testSerialization()
    {
        $assertion = ExpressionAssertion::fromProperties(
            'foo',
            '=',
            'bar'
        );

        $serializedAssertion = serialize($assertion);

        $this->assertContains('left', $serializedAssertion);
        $this->assertContains('foo', $serializedAssertion);
        $this->assertContains('operator', $serializedAssertion);
        $this->assertContains('=', $serializedAssertion);
        $this->assertContains('right', $serializedAssertion);
        $this->assertContains('bar', $serializedAssertion);
    }

    public function testSerializationShouldNotSerializeAssertContext()
    {
        $assertion = ExpressionAssertion::fromProperties(
            'foo',
            '=',
            'bar'
        );

        $serializedAssertion = serialize($assertion);

        $this->assertNotContains('assertContext', $serializedAssertion);
    }
}
