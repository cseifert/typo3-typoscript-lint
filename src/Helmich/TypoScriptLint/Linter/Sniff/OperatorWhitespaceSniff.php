<?php
namespace Helmich\TypoScriptLint\Linter\Sniff;

use Helmich\TypoScriptLint\Linter\LinterConfiguration;
use Helmich\TypoScriptLint\Linter\Report\File;
use Helmich\TypoScriptLint\Linter\Report\Warning;
use Helmich\TypoScriptLint\Linter\Sniff\Inspection\TokenInspections;
use Helmich\TypoScriptParser\Tokenizer\LineGrouper;
use Helmich\TypoScriptParser\Tokenizer\TokenInterface;

class OperatorWhitespaceSniff implements TokenStreamSniffInterface
{
    use TokenInspections;

    /**
     * @param array $parameters
     */
    public function __construct(array $parameters)
    {
    }

    /**
     * @param TokenInterface[]    $tokens
     * @param File                $file
     * @param LinterConfiguration $configuration
     * @return void
     */
    public function sniff(array $tokens, File $file, LinterConfiguration $configuration)
    {
        $tokensByLine = new LineGrouper($tokens);

        /** @var TokenInterface[] $tokensInLine */
        foreach ($tokensByLine->getLines() as $line => $tokensInLine) {
            $count = count($tokensInLine);
            for ($i = 0; $i < $count; $i++) {
                if ($tokensInLine[$i]->getType() === TokenInterface::TYPE_OBJECT_IDENTIFIER && isset($tokensInLine[$i + 1])) {
                    if (!self::isWhitespace($tokensInLine[$i + 1])) {
                        $file->addWarning(new Warning(
                            $tokensInLine[$i]->getLine(),
                            null,
                            'No whitespace after object accessor.',
                            Warning::SEVERITY_WARNING,
                            __CLASS__
                        ));
                    } elseif (!self::isWhitespaceOfLength($tokensInLine[$i + 1], 1)) {
                        $file->addWarning(new Warning(
                            $tokensInLine[$i]->getLine(),
                            null,
                            'Accessor should be followed by single space.',
                            Warning::SEVERITY_WARNING,
                            __CLASS__
                        ));
                    }

                    // Scan forward until we find the actual operator
                    for ($j = 0; $j < $count && !self::isOperator($tokensInLine[$j]); $j ++);

                    if (isset($tokensInLine[$j + 1])) {
                        if (!self::isWhitespace($tokensInLine[$j + 1])) {
                            $file->addWarning(new Warning(
                                $tokensInLine[$j]->getLine(),
                                null,
                                'No whitespace after operator.',
                                Warning::SEVERITY_WARNING,
                                __CLASS__
                            ));
                        } elseif (!self::isWhitespaceOfLength($tokensInLine[$j + 1], 1)) {
                            $file->addWarning(new Warning(
                                $tokensInLine[$j]->getLine(),
                                null,
                                'Operator should be followed by single space.',
                                Warning::SEVERITY_WARNING,
                                __CLASS__
                            ));
                        }
                    }
                }
            }
        }
    }
}
