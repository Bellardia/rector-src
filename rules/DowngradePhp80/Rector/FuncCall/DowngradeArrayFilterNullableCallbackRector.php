<?php

declare(strict_types=1);

namespace Rector\DowngradePhp80\Rector\FuncCall;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\BinaryOp\Identical;
use PhpParser\Node\Expr\BooleanNot;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\Empty_;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Ternary;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Param;
use PHPStan\Type\MixedType;
use Rector\Core\Rector\AbstractRector;
use Rector\NodeTypeResolver\Node\AttributeKey;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * @changelog https://www.php.net/manual/en/function.array-filter.php#refsect1-function.array-filter-changelog
 *
 * @see \Rector\Tests\DowngradePhp80\Rector\FuncCall\DowngradeArrayFilterNullableCallbackRector\DowngradeArrayFilterNullableCallbackRectorTest
 */
final class DowngradeArrayFilterNullableCallbackRector extends AbstractRector
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Unset nullable callback on array_filter',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
class SomeClass
{
    public function run($callback = null)
    {
        $data = [[]];
        var_dump(array_filter($data, null));
    }
}
CODE_SAMPLE
,
                    <<<'CODE_SAMPLE'
class SomeClass
{
    public function run($callback = null)
    {
        $data = [[]];
        var_dump(array_filter($data));
    }
}
CODE_SAMPLE
                ),
            ]
        );
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [FuncCall::class];
    }

    /**
     * @param FuncCall $node
     */
    public function refactor(Node $node): FuncCall|Ternary|null
    {
        $args = $node->getArgs();

        if (! $this->isName($node, 'array_filter')) {
            return null;
        }

        if ($this->hasNamedArg($args)) {
            return null;
        }

        if (! isset($args[1])) {
            return null;
        }

        $createdByRule = $node->getAttribute(AttributeKey::CREATED_BY_RULE);
        if ($createdByRule === self::class) {
            return null;
        }

        // direct null check ConstFetch
        if ($args[1]->value instanceof ConstFetch && $this->valueResolver->isNull($args[1]->value)) {
            $args = [$args[0]];
            $node->args = $args;

            $node->setAttribute(AttributeKey::CREATED_BY_RULE, self::class);
            return $node;
        }

        $type = $this->nodeTypeResolver->getType($args[1]->value);
        if (! $type instanceof MixedType) {
            return null;
        }

        $node->args[1] = new Arg($this->createNewArgFirstTernary($args));
        $node->args[2] = new Arg($this->createNewArgSecondTernary($args));

        $node->setAttribute(AttributeKey::CREATED_BY_RULE, self::class);
        return $node;
    }

    /**
     * @param Arg[] $args
     */
    private function createNewArgFirstTernary(array $args): Ternary
    {
        $identical = new Identical($args[1]->value, $this->nodeFactory->createNull());
        $vVariable = new Variable('v');
        $arrowFunction = new ArrowFunction([
            'expr' => new BooleanNot(new Empty_($vVariable)),
        ]);
        $arrowFunction->params = [new Param($vVariable), new Param(new Variable('k'))];
        $arrowFunction->returnType = new Identifier('bool');

        return new Ternary($identical, $arrowFunction, $args[1]->value);
    }

    /**
     * @param Arg[] $args
     */
    private function createNewArgSecondTernary(array $args): Ternary
    {
        $identical = new Identical($args[1]->value, $this->nodeFactory->createNull());
        $constFetch = new ConstFetch(new Name('ARRAY_FILTER_USE_BOTH'));

        return new Ternary(
            $identical,
            $constFetch,
            isset($args[2]) ? $args[2]->value : new ConstFetch(new Name('0'))
        );
    }

    /**
     * @param Arg[] $args
     */
    private function hasNamedArg(array $args): bool
    {
        foreach ($args as $arg) {
            if ($arg->name instanceof Identifier) {
                return true;
            }
        }

        return false;
    }
}