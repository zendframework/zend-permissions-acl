<?php
/**
 * @see       https://github.com/zendframework/zend-permissions-acl for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-permissions-acl/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Permissions\Acl\Assertion;

use PHPUnit\Framework\TestCase;
use Zend\Permissions\Acl\Acl;
use Zend\Permissions\Acl\Assertion\Exception\InvalidAssertionException;
use Zend\Permissions\Acl\Assertion\ExpressionAssertion;
use Zend\Permissions\Acl\Exception\RuntimeException;
use ZendTest\Permissions\Acl\TestAsset\ExpressionUseCase\User;
use ZendTest\Permissions\Acl\TestAsset\ExpressionUseCase\BlogPost;

class ExpressionAssertionTest extends TestCase
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
            'operator' => ExpressionAssertion::OPERATOR_EQ,
            'right' => 'bar'
        ]);

        $this->assertInstanceOf(ExpressionAssertion::class, $assertion);
    }

    public function testExceptionIsRaisedInCaseOfInvalidExpressionArray()
    {
        $this->expectException(InvalidAssertionException::class);
        $this->expectExceptionMessage("Expression assertion requires 'left', 'operator' and 'right' to be supplied");

        ExpressionAssertion::fromArray(['left' => 'test', 'foo' => 'bar']);
    }

    public function testExceptionIsRaisedInCaseOfInvalidExpressionContextOperandType()
    {
        $this->expectException(InvalidAssertionException::class);
        $this->expectExceptionMessage('Expression assertion context operand must be string');

        ExpressionAssertion::fromProperties(
            [ExpressionAssertion::OPERAND_CONTEXT_PROPERTY => 123],
            ExpressionAssertion::OPERATOR_IN,
            'test'
        );
    }

    public function testExceptionIsRaisedInCaseOfInvalidExpressionOperator()
    {
        $this->expectException(InvalidAssertionException::class);
        $this->expectExceptionMessage('Provided expression assertion operator is not supported');

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
            'equality' => [
                'expression' => [
                    'left' => [ExpressionAssertion::OPERAND_CONTEXT_PROPERTY => 'role.username'],
                    'operator' => ExpressionAssertion::OPERATOR_EQ,
                    'right' => 'test',
                ],
                'role' => new User([
                    'username' => 'test',
                ]),
                'resource' => new BlogPost(),
                'privilege' => 'read',
                'assert' => true,
            ],
            'inequality' => [
                'expression' => [
                    'left' => [ExpressionAssertion::OPERAND_CONTEXT_PROPERTY => 'role.username'],
                    'operator' => ExpressionAssertion::OPERATOR_NEQ,
                    'right' => 'test',
                ],
                'role' => new User([
                    'username' => 'foobar',
                ]),
                'resource' => new BlogPost(),
                'privilege' => 'read',
                'assert' => true,
            ],
            'boolean-equality' => [
                'expression' => [
                    'left' => [ExpressionAssertion::OPERAND_CONTEXT_PROPERTY => 'role.username'],
                    'operator' => ExpressionAssertion::OPERATOR_EQ,
                    'right' => true,
                ],
                'role' => $author3,
                'resource' => $post3,
                'privilege' => 'read',
                'assert' => true,
            ],
            'greater-than' => [
                'expression' => [
                    'left' => [ExpressionAssertion::OPERAND_CONTEXT_PROPERTY => 'role.age'],
                    'operator' => ExpressionAssertion::OPERATOR_GT,
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
            'greater-than-or-equal' => [
                'expression' => [
                    'left' => [ExpressionAssertion::OPERAND_CONTEXT_PROPERTY => 'role.age'],
                    'operator' => ExpressionAssertion::OPERATOR_GTE,
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
            'less-than' => [
                'expression' => [
                    'left' => [ExpressionAssertion::OPERAND_CONTEXT_PROPERTY => 'role.age'],
                    'operator' => ExpressionAssertion::OPERATOR_LT,
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
            'less-than-or-equal' => [
                'expression' => [
                    'left' => [ExpressionAssertion::OPERAND_CONTEXT_PROPERTY => 'role.age'],
                    'operator' => ExpressionAssertion::OPERATOR_LTE,
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
            'in' => [
                'expression' => [
                    'left' => [ExpressionAssertion::OPERAND_CONTEXT_PROPERTY => 'role.username'],
                    'operator' => ExpressionAssertion::OPERATOR_IN,
                    'right' => ['foo', 'bar'],
                ],
                'role' => new User([
                    'username' => 'test',
                ]),
                'resource' => new BlogPost(),
                'privilege' => 'read',
                'assert' => false,
            ],
            'not-in' => [
                'expression' => [
                    'left' => [ExpressionAssertion::OPERAND_CONTEXT_PROPERTY => 'role.username'],
                    'operator' => ExpressionAssertion::OPERATOR_NIN,
                    'right' => ['foo', 'bar'],
                ],
                'role' => new User([
                    'username' => 'test',
                ]),
                'resource' => new BlogPost(),
                'privilege' => 'read',
                'assert' => true,
            ],
            'regex' => [
                'expression' => [
                    'left' => [ExpressionAssertion::OPERAND_CONTEXT_PROPERTY => 'role.username'],
                    'operator' => ExpressionAssertion::OPERATOR_REGEX,
                    'right' => '/foobar/',
                ],
                'role' => new User([
                    'username' => 'test',
                ]),
                'resource' => new BlogPost(),
                'privilege' => 'read',
                'assert' => false,
            ],
            'REGEX' => [
                'expression' => [
                    'left' => [ExpressionAssertion::OPERAND_CONTEXT_PROPERTY => 'resource.shortDescription'],
                    'operator' => 'REGEX',
                    'right' => '/ipsum/',
                ],
                'role' => new User([
                    'username' => 'test',
                ]),
                'resource' => new BlogPost([
                    'title' => 'Test',
                    'content' => 'lorem ipsum dolor sit amet',
                    'shortDescription' => 'lorem ipsum'
                ]),
                'privilege' => 'read',
                'assert' => true,
            ],
            'nregex' => [
                'expression' => [
                    'left' => [ExpressionAssertion::OPERAND_CONTEXT_PROPERTY => 'role.username'],
                    'operator' => ExpressionAssertion::OPERATOR_NREGEX,
                    'right' => '/barbaz/',
                ],
                'role' => new User([
                    'username' => 'test',
                ]),
                'resource' => new BlogPost(),
                'privilege' => 'read',
                'assert' => true,
            ],
            'same' => [
                'expression' => [
                    'left' => [ExpressionAssertion::OPERAND_CONTEXT_PROPERTY => 'role.username'],
                    'operator' => ExpressionAssertion::OPERATOR_SAME,
                    'right' => 'test',
                ],
                'role' => new User([
                    'username' => 'test',
                ]),
                'resource' => new BlogPost(),
                'privilege' => 'read',
                'assert' => true,
            ],
            'not-same' => [
                'expression' => [
                    'left' => [ExpressionAssertion::OPERAND_CONTEXT_PROPERTY => 'role.username'],
                    'operator' => ExpressionAssertion::OPERATOR_NSAME,
                    'right' => 'test',
                ],
                'role' => new User([
                    'username' => 'foobar',
                ]),
                'resource' => new BlogPost(),
                'privilege' => 'read',
                'assert' => true,
            ],
            'equality-calculated-property' => [
                'expression' => [
                    'left' => [ExpressionAssertion::OPERAND_CONTEXT_PROPERTY => 'role.adult'],
                    'operator' => ExpressionAssertion::OPERATOR_EQ,
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
            'privilege' => [
                'expression' => [
                    'left' => [ExpressionAssertion::OPERAND_CONTEXT_PROPERTY => 'privilege'],
                    'operator' => ExpressionAssertion::OPERATOR_EQ,
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
        $assertion = ExpressionAssertion::fromProperties(
            [ExpressionAssertion::OPERAND_CONTEXT_PROPERTY => 'foobar'],
            ExpressionAssertion::OPERATOR_EQ,
            'test'
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("'foobar' is not available in the assertion context");

        $assertion->assert(new Acl(), new User(), new BlogPost(), 'read');
    }

    public function testExceptionIsRaisedInCaseOfUnknownContextOperandContainingPropertyPath()
    {
        $assertion = ExpressionAssertion::fromProperties(
            [ExpressionAssertion::OPERAND_CONTEXT_PROPERTY => 'foo.bar'],
            ExpressionAssertion::OPERATOR_EQ,
            'test'
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("'foo' is not available in the assertion context");

        $assertion->assert(new Acl(), new User(), new BlogPost(), 'read');
    }

    public function testExceptionIsRaisedIfContextObjectPropertyCannotBeResolved()
    {
        $assertion = ExpressionAssertion::fromProperties(
            [ExpressionAssertion::OPERAND_CONTEXT_PROPERTY => 'role.age123'],
            ExpressionAssertion::OPERATOR_EQ,
            30
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("'age123' property cannot be resolved on the 'role' object");

        $assertion->assert(new Acl(), new User(), new BlogPost(), 'read');
    }

    public function testExceptionIsRaisedInCaseThatAssertHasBeenInvokedWithoutPassingContext()
    {
        $assertion = ExpressionAssertion::fromProperties(
            [ExpressionAssertion::OPERAND_CONTEXT_PROPERTY => 'role.username'],
            ExpressionAssertion::OPERATOR_EQ,
            'test'
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("'role' is not available in the assertion context");

        $assertion->assert(new Acl());
    }

    public function testSerialization()
    {
        $assertion = ExpressionAssertion::fromProperties(
            'foo',
            ExpressionAssertion::OPERATOR_EQ,
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
            ExpressionAssertion::OPERATOR_EQ,
            'bar'
        );

        $serializedAssertion = serialize($assertion);

        $this->assertNotContains('assertContext', $serializedAssertion);
    }
}
