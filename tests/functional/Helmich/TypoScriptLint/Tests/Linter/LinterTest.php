<?php
namespace Helmich\TypoScriptLint\Tests\Linter;


use Helmich\TypoScriptLint\Linter\Report\Warning;
use Helmich\TypoScriptParser\Parser\Parser;
use Helmich\TypoScriptParser\Tokenizer\Tokenizer;
use Helmich\TypoScriptLint\Linter\Linter;
use Helmich\TypoScriptLint\Linter\LinterConfiguration;
use Helmich\TypoScriptLint\Linter\Report\Report;
use Helmich\TypoScriptLint\Linter\Sniff\DeadCodeSniff;
use Helmich\TypoScriptLint\Linter\Sniff\DuplicateAssignmentSniff;
use Helmich\TypoScriptLint\Linter\Sniff\IndentationSniff;
use Helmich\TypoScriptLint\Linter\Sniff\NestingConsistencySniff;
use Helmich\TypoScriptLint\Linter\Sniff\OperatorWhitespaceSniff;
use Helmich\TypoScriptLint\Linter\Sniff\RepeatingRValueSniff;
use Helmich\TypoScriptLint\Linter\Sniff\SniffLocator;
use Prophecy\Argument;
use Symfony\Component\Console\Output\NullOutput;


class LinterTest extends \PHPUnit_Framework_TestCase
{


    /** @var  Linter */
    private $linter;



    public function setUp()
    {
        $tokenizer = new Tokenizer();
        $parser    = new Parser($tokenizer);

        $sniffLocator = $this->prophesize(SniffLocator::class);
        $sniffLocator->getTokenStreamSniffs(Argument::any())->willReturn([
            new DeadCodeSniff([]),
            new IndentationSniff([]),
            new OperatorWhitespaceSniff([]),
            new RepeatingRValueSniff([])
        ]);
        $sniffLocator->getSyntaxTreeSniffs(Argument::any())->willReturn([
            new DuplicateAssignmentSniff([]),
            new NestingConsistencySniff([])
        ]);

        $this->linter = new Linter(
            $tokenizer,
            $parser,
            $sniffLocator->reveal()
        );
    }



    /**
     * @dataProvider getFunctionalTestFixtures
     */
    public function testLinterCreatesExpectedOutput($typoscriptFile, array $expectedWarnings)
    {
        $report = new Report();
        $config = new LinterConfiguration();

        $this->linter->lintFile(
            $typoscriptFile,
            $report,
            $config,
            new NullOutput()
        );

        $this->assertCount(count($expectedWarnings) > 0 ? 1 : 0, $report->getFiles());
        if (count($expectedWarnings) > 0)
        {
            $actualWarnings = $report->getFiles()[0]->getWarnings();
            try
            {
                $this->assertEquals($expectedWarnings, $actualWarnings);
            }
            catch (\PHPUnit_Framework_AssertionFailedError $error)
            {
                foreach($actualWarnings as $warning)
                {
                    echo $warning->getLine() . ";" . $warning->getColumn() . ";" . $warning->getMessage() . ";" .
                        $warning->getSeverity() . ";" . $warning->getSource() . "\n";
                }

                throw $error;
            }
        }
    }



    public function getFunctionalTestFixtures()
    {
        $files = glob(__DIR__ . '/Fixtures/*/*.typoscript');
        foreach ($files as $file)
        {
            $output = dirname($file) . '/output.txt';
            $outputLines = explode("\n", file_get_contents($output));
            $outputLines = array_filter($outputLines, 'strlen');

            $reports = array_map(function($line) use ($file) {
                $values = str_getcsv($line, ';');
                return new Warning(
                    $values[0],
                    $values[1],
                    $values[2],
                    $values[3],
                    $values[4]
                );
            }, $outputLines);

            yield [
                $file,
                $reports
            ];
        }
    }



}