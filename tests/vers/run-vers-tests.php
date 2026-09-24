<?php

/*
 * Runs the VERS spec test suite (https://github.com/package-url/vers-spec/tree/main/tests)
 * against composer/semver.
 *
 * Supported test types:
 *  - from_native: the native range is parsed with VersionParser::parseConstraints and compared
 *    semantically (via Intervals) to the expected VERS string converted into a composer constraint
 *  - containment: the VERS string is converted into a composer constraint and matched against the version
 *
 * Pre-release boundary differences (composer's -dev bounds) are by design and count as passing.
 *
 * With --baseline, only results differing from the known differences recorded in the baseline
 * file fail the run. Use --update-baseline to regenerate it.
 *
 * Usage: php tests/vers/run-vers-tests.php <path-to-vers-spec/tests> [--scheme=npm,pypi] [--file=substr] [-v]
 *        [--baseline=tests/vers/baseline.json [--update-baseline]]
 */

require __DIR__ . '/../../vendor/autoload.php';

use Composer\Semver\Constraint\Constraint;
use Composer\Semver\Constraint\ConstraintInterface;
use Composer\Semver\Constraint\MatchAllConstraint;
use Composer\Semver\Constraint\MultiConstraint;
use Composer\Semver\Intervals;
use Composer\Semver\VersionParser;

$dir = null;
$schemes = null;
$fileFilter = null;
$verbose = false;
$baselineFile = null;
$updateBaseline = false;
foreach (array_slice($argv, 1) as $arg) {
    if (0 === strpos($arg, '--scheme=')) {
        $schemes = explode(',', (string) substr($arg, 9));
    } elseif (0 === strpos($arg, '--file=')) {
        $fileFilter = (string) substr($arg, 7);
    } elseif (0 === strpos($arg, '--baseline=')) {
        $baselineFile = (string) substr($arg, 11);
    } elseif ($arg === '--update-baseline') {
        $updateBaseline = true;
    } elseif ($arg === '-v' || $arg === '--verbose') {
        $verbose = true;
    } else {
        $dir = $arg;
    }
}

if ($dir === null || !is_dir($dir) || ($updateBaseline && $baselineFile === null)) {
    fwrite(STDERR, "Usage: php {$argv[0]} <path-to-vers-spec/tests> [--scheme=npm,pypi] [--file=substr] [-v] [--baseline=file [--update-baseline]]\n");
    fwrite(STDERR, "Get the suite with: git clone --depth 1 https://github.com/package-url/vers-spec.git\n");
    exit(2);
}
if ($baselineFile !== null && ($schemes !== null || $fileFilter !== null)) {
    // a filtered run would report every filtered-out baseline entry as stale
    fwrite(STDERR, "--baseline cannot be combined with --scheme or --file\n");
    exit(2);
}

final class VersConverter
{
    /** @var VersionParser */
    private $parser;

    public function __construct(VersionParser $parser)
    {
        $this->parser = $parser;
    }

    /**
     * Converts a VERS string into an equivalent composer constraint, following the containment
     * algorithm from the spec's how-to-parse.md.
     *
     * @param string $vers
     * @return ConstraintInterface
     */
    public function toConstraint($vers)
    {
        if (!preg_match('{^vers:([a-z0-9.+-]+)/(.+)$}', $vers, $m)) {
            throw new \UnexpectedValueException('Invalid VERS string: '.$vers);
        }
        if ($m[2] === '*') {
            return new MatchAllConstraint();
        }

        $items = array();
        foreach (explode('|', $m[2]) as $part) {
            if (!preg_match('{^(>=|<=|!=|<|>)?(.+)$}', $part, $pm)) {
                throw new \UnexpectedValueException('Invalid VERS constraint: '.$part);
            }
            $items[] = array($pm[1], $this->parser->normalize(rawurldecode($pm[2])));
        }
        usort($items, function ($a, $b) {
            return version_compare($a[1], $b[1]);
        });
        // some expected outputs in the suite are not simplified (e.g. <1|<2), which would be misread by the containment algorithm
        $items = $this->simplify(array_values(array_unique($items, SORT_REGULAR)));

        $equal = array();
        $notEqual = array();
        $ranges = array();
        foreach ($items as $item) {
            list($op, $version) = $item;
            if ($op === '!=') {
                $notEqual[] = new Constraint('!=', $version);
                continue;
            }
            // =, <= and >= versions are always IN
            if ($op === '' || $op === '<=' || $op === '>=') {
                $equal[] = new Constraint('==', $version);
            }
            if ($op !== '') {
                $ranges[] = array($op[0] === '<' ? '<' : '>', $version);
            }
        }

        $alternatives = $equal;
        $count = count($ranges);
        if ($count === 1) {
            $alternatives[] = new Constraint($ranges[0][0], $ranges[0][1]);
        }
        for ($i = 0; $i < $count - 1; $i++) {
            list($curOp, $curVer) = $ranges[$i];
            list($nextOp, $nextVer) = $ranges[$i + 1];
            if ($i === 0 && $curOp === '<') {
                $alternatives[] = new Constraint('<', $curVer);
            }
            if ($i === $count - 2 && $nextOp === '>') {
                $alternatives[] = new Constraint('>', $nextVer);
            }
            if ($curOp === '>' && $nextOp === '<') {
                $alternatives[] = new MultiConstraint(array(new Constraint('>', $curVer), new Constraint('<', $nextVer)), true);
            }
        }

        if (!$alternatives) {
            return $notEqual ? MultiConstraint::create($notEqual, true) : new MatchAllConstraint();
        }

        $result = MultiConstraint::create($alternatives, false);
        if ($notEqual) {
            $result = MultiConstraint::create(array_merge(array($result), $notEqual), true);
        }

        return $result;
    }

    /**
     * Removes redundant constraints as per the spec's "Simplifying constraints" procedure.
     *
     * @param list<array{string, string}> $items sorted by version
     * @return list<array{string, string}>
     */
    private function simplify(array $items)
    {
        $result = array();
        foreach ($items as $item) {
            if ($item[0] === '!=') {
                $result[] = $item;
                continue;
            }
            $isUpper = $item[0] === '<' || $item[0] === '<=';
            while (null !== ($prevKey = $this->lastRangeKey($result))) {
                $prevOp = $result[$prevKey][0];
                if ($prevOp === '>' || $prevOp === '>=') {
                    if (!$isUpper) {
                        continue 2;
                    }
                    break;
                }
                if (!$isUpper) {
                    break;
                }
                array_splice($result, $prevKey, 1);
            }
            $result[] = $item;
        }

        return $result;
    }

    /**
     * @param list<array{string, string}> $items
     * @return int|null
     */
    private function lastRangeKey(array $items)
    {
        for ($i = count($items) - 1; $i >= 0; $i--) {
            if ($items[$i][0] !== '!=') {
                return $i;
            }
        }

        return null;
    }
}

/**
 * Strips -dev suffixes from numeric bounds (composer uses X-dev bounds to include pre-releases in
 * ^/~/wildcard ranges), so we can tell apart mismatches that only concern pre-release boundaries.
 *
 * @return ConstraintInterface
 */
function stripDevBounds(ConstraintInterface $c)
{
    if ($c instanceof MultiConstraint) {
        return new MultiConstraint(array_map('stripDevBounds', $c->getConstraints()), $c->isConjunctive());
    }
    if ($c instanceof Constraint && preg_match('{^(\d+(?:\.\d+)*(?:-.+)?)-dev$}', $c->getVersion(), $m)) {
        return new Constraint($c->getOperator(), $m[1]);
    }

    return $c;
}

/**
 * @return bool
 */
function equivalent(ConstraintInterface $a, ConstraintInterface $b)
{
    return Intervals::isSubsetOf($a, $b) && Intervals::isSubsetOf($b, $a);
}

/**
 * @param array<string, mixed> $test
 * @return array{string, string}
 */
function runFromNative(array $test, VersionParser $parser, VersConverter $converter)
{
    try {
        $actual = $parser->parseConstraints($test['input']['native_range']);
    } catch (\Exception $e) {
        return array(empty($test['expected_failure']) ? 'native_error' : 'pass', $e->getMessage());
    }
    if (!empty($test['expected_failure'])) {
        return array('failure_not_raised', 'parsed as '.$actual);
    }
    try {
        $expected = $converter->toConstraint($test['expected_output']);
    } catch (\Exception $e) {
        return array('vers_error', $test['expected_output'].': '.$e->getMessage());
    }

    $detail = 'composer: '.$actual.'  vs  expected: '.$test['expected_output'].' ('.$expected.')';
    if (equivalent($actual, $expected)) {
        return array('pass', $detail);
    }
    if (equivalent(stripDevBounds($actual), stripDevBounds($expected))) {
        return array('pass_dev_bounds', $detail);
    }

    return array('mismatch', $detail);
}

/**
 * @param array<string, mixed> $test
 * @return array{string, string}
 */
function runContainment(array $test, VersionParser $parser, VersConverter $converter)
{
    try {
        $constraint = $converter->toConstraint($test['input']['vers']);
        $version = new Constraint('==', $parser->normalize($test['input']['version']));
    } catch (\Exception $e) {
        return array(empty($test['expected_failure']) ? 'vers_error' : 'pass', $e->getMessage());
    }
    $matches = $constraint->matches($version);

    return array($matches === $test['expected_output'] ? 'pass' : 'wrong_result', 'got '.var_export($matches, true).' using '.$constraint);
}

$parser = new VersionParser();
$converter = new VersConverter($parser);

$categories = array(
    'pass' => 'pass',
    'pass_dev_bounds' => 'equivalent except pre-release bounds (-dev)',
    'mismatch' => 'semantic mismatch',
    'native_error' => 'composer failed to parse native range',
    'vers_error' => 'composer failed to parse expected VERS versions',
    'failure_not_raised' => 'expected failure not raised',
    'wrong_result' => 'wrong containment result',
    'skipped' => 'skipped (unsupported test type)',
);

$totals = array_fill_keys(array_keys($categories), 0);
$failures = array();
$known = array();

$files = glob(rtrim($dir, '/').'/*.json') ?: array();
sort($files);
foreach ($files as $file) {
    if ($fileFilter !== null && false === strpos(basename($file), $fileFilter)) {
        continue;
    }
    $data = json_decode((string) file_get_contents($file), true);
    if (!isset($data['tests'])) {
        continue;
    }

    $fileTotals = array_fill_keys(array_keys($categories), 0);
    foreach ($data['tests'] as $test) {
        $type = $test['test_type'];
        $input = $test['input'];

        if ($type === 'from_native') {
            $scheme = $input['scheme'];
            $subject = $input['native_range'];
        } elseif ($type === 'containment') {
            $scheme = preg_match('{^vers:([^/]+)/}', $input['vers'], $m) ? $m[1] : '?';
            $subject = $input['vers'].' contains '.$input['version'];
        } else {
            $fileTotals['skipped']++;
            continue;
        }
        if ($schemes !== null && !in_array($scheme, $schemes, true)) {
            continue;
        }

        list($result, $detail) = $type === 'from_native'
            ? runFromNative($test, $parser, $converter)
            : runContainment($test, $parser, $converter);

        $fileTotals[$result]++;
        if ($result !== 'pass') {
            $failures[$result][] = sprintf('[%s] %s  =>  %s', basename($file, '.json'), $subject, $detail);
        }
        if ($result !== 'pass' && $result !== 'pass_dev_bounds') {
            $key = $subject;
            for ($n = 2; isset($known[basename($file)][$key]); $n++) {
                $key = $subject.' #'.$n;
            }
            $known[basename($file)][$key] = $result;
        }
    }

    $ran = array_sum($fileTotals) - $fileTotals['skipped'];
    if ($ran === 0) {
        continue;
    }
    $summary = array();
    foreach ($fileTotals as $cat => $n) {
        if ($n && $cat !== 'skipped') {
            $summary[] = "$cat=$n";
        }
    }
    printf("%-40s %4d tests  %s\n", basename($file), $ran, implode(' ', $summary));
    foreach ($fileTotals as $cat => $n) {
        $totals[$cat] += $n;
    }
}

foreach ($categories as $cat => $label) {
    if (empty($failures[$cat])) {
        continue;
    }
    printf("\n== %s (%d) ==\n", $label, count($failures[$cat]));
    $list = $verbose ? $failures[$cat] : array_slice($failures[$cat], 0, 15);
    echo implode("\n", $list), "\n";
    if (count($list) < count($failures[$cat])) {
        printf("... %d more, use -v to see all\n", count($failures[$cat]) - count($list));
    }
}

echo "\n== Totals ==\n";
foreach ($categories as $cat => $label) {
    if ($totals[$cat] && $cat !== 'skipped') {
        printf("%-50s %5d\n", $label, $totals[$cat]);
    }
}

if ($baselineFile === null) {
    exit(empty($known) ? 0 : 1);
}

if ($updateBaseline) {
    ksort($known);
    file_put_contents($baselineFile, json_encode($known, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n");
    echo "\nBaseline written to $baselineFile\n";
    exit(0);
}

$baseline = is_file($baselineFile) ? json_decode((string) file_get_contents($baselineFile), true) : array();
$diff = array();
foreach ($known as $file => $results) {
    foreach ($results as $subject => $result) {
        if (!isset($baseline[$file][$subject])) {
            $diff[] = sprintf('new      [%s] %s  =>  %s', $file, $subject, $result);
        } elseif ($baseline[$file][$subject] !== $result) {
            $diff[] = sprintf('changed  [%s] %s  =>  %s (was %s)', $file, $subject, $result, $baseline[$file][$subject]);
        }
    }
}
foreach ($baseline as $file => $results) {
    foreach ($results as $subject => $result) {
        if (!isset($known[$file][$subject])) {
            $diff[] = sprintf('resolved [%s] %s  (was %s)', $file, $subject, $result);
        }
    }
}

if ($diff) {
    printf("\n== Differences from baseline (%d) ==\n%s\n", count($diff), implode("\n", $diff));
    echo "\nIf these are expected, run with --update-baseline and commit the result.\n";
    exit(1);
}

echo "\nAll results match the baseline.\n";
exit(0);
