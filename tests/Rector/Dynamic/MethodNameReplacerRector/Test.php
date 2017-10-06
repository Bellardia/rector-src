<?php declare(strict_types=1);

namespace Rector\Tests\Rector\Dynamic\MethodNameReplacerRector;

use Rector\Rector\Dynamic\MethodNameReplacerRector;
use Rector\Testing\PHPUnit\AbstractRectorTestCase;

final class Test extends AbstractRectorTestCase
{
    public function test(): void
    {
        $this->doTestFileMatchesExpectedContent(
            __DIR__ . '/wrong/wrong.php.inc',
            __DIR__ . '/correct/correct.php.inc'
        );
        $this->doTestFileMatchesExpectedContent(
            __DIR__ . '/wrong/wrong2.php.inc',
            __DIR__ . '/correct/correct2.php.inc'
        );
        $this->doTestFileMatchesExpectedContent(
            __DIR__ . '/wrong/wrong3.php.inc',
            __DIR__ . '/correct/correct3.php.inc'
        );
        $this->doTestFileMatchesExpectedContent(
            __DIR__ . '/wrong/wrong4.php.inc',
            __DIR__ . '/correct/correct4.php.inc'
        );
        $this->doTestFileMatchesExpectedContent(
            __DIR__ . '/wrong/SomeClass.php',
            __DIR__ . '/correct/SomeClass.php'
        );
    }

    /**
     * @return string[]
     */
    protected function getRectorClasses(): array
    {
        return [MethodNameReplacerRector::class];
    }
}
