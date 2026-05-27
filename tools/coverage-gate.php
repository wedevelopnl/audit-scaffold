<?php declare(strict_types=1);

/**
 * Fails when line coverage from a Clover report drops below a threshold.
 *
 * PHPUnit has no built-in minimum-coverage gate, so the CI pipeline (and the
 * `composer coverage` script) parse the Clover report here instead of pulling in
 * an extra dependency.
 *
 * Usage: php tools/coverage-gate.php [clover-path] [threshold-percentage]
 */

$cloverPath = $argv[1] ?? 'coverage.xml';
$threshold = (float) ($argv[2] ?? 95);

if (!is_file($cloverPath)) {
    fwrite(\STDERR, sprintf("Coverage report not found: %s\n", $cloverPath));
    exit(1);
}

$xml = @simplexml_load_file($cloverPath);
if (false === $xml || !isset($xml->project->metrics)) {
    fwrite(\STDERR, sprintf("Could not parse Clover project metrics from: %s\n", $cloverPath));
    exit(1);
}

// Clover's "statements"/"coveredstatements" on the project-level metrics element
// is the line-coverage basis (each executable line is one statement).
$metrics = $xml->project->metrics;
$statements = (int) $metrics['statements'];
$covered = (int) $metrics['coveredstatements'];

if (0 === $statements) {
    fwrite(\STDERR, "No statements found in coverage report.\n");
    exit(1);
}

$percentage = $covered / $statements * 100;

printf(
    "Line coverage: %.2f%% (%d/%d statements); threshold %.2f%%\n",
    $percentage,
    $covered,
    $statements,
    $threshold,
);

if ($percentage < $threshold) {
    fwrite(\STDERR, sprintf("FAIL: coverage %.2f%% is below threshold %.2f%%\n", $percentage, $threshold));
    exit(1);
}

echo "PASS\n";
exit(0);
