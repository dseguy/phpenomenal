<?php

// PHPenomenal 8.6 Submission: DataMutator
// Requires the hypothetical PHP 8.6 engine to compile.

use Time\Duration;

// ------------------------------------------------------------------------------
// PHP 8.6 Feature: #[\Override] on a constant
// ------------------------------------------------------------------------------
interface MutatorContract {
    const string HANDLER = 'DefaultHandler';
}

class DataMutator implements MutatorContract {
    #[\Override]
    const string HANDLER = 'DataMutatorHandler'; // Valid in 8.6!

    // ------------------------------------------------------------------------------
    // PHP 8.6 Feature: readonly property defaults
    // ------------------------------------------------------------------------------
    public function __construct(
        public readonly int $maxLimit = 100, // Readonly properties can now have defaults
        public readonly string $mode = 'standard'
    ) {}
}

// ------------------------------------------------------------------------------
// PHP 8.6 Feature: writes on const-held objects
// ------------------------------------------------------------------------------
class ExecutionMetrics {
    public int $mutations = 0;
    public int $clampsTriggered = 0;
}

// Defining an object as a constant
const GLOBAL_METRICS = new ExecutionMetrics();

// ------------------------------------------------------------------------------
// PHP 8.6 Feature: Partial Function Application
// ------------------------------------------------------------------------------
function calculateBase(int $val, int $multiplier, int $divisor, int $addend): int {
    return intdiv($val * $multiplier, $divisor) + $addend;
}

// Partially apply the function using the new placeholder syntax (?)
// We fix the multiplier to 3, divisor to 2, and addend to 5.
 $mutatorFunc = calculateBase(?, 3, 2, 5);

// ------------------------------------------------------------------------------
// Main Application Logic
// ------------------------------------------------------------------------------
 $input = $argv[1] ?? "10,20,30,40,50,60,70,80,90,100";
 $dirArg = $argv[2] ?? 'asc';

// PHP 8.6 Feature: Time\Duration
 $timeout = new Duration(seconds: 5);
echo "PHPenomenal 8.6 DataMutator\n";
echo "Timeout set to: {$timeout->seconds} seconds\n";

 $config = new DataMutator(maxLimit: 75);

 $numbers = array_map('intval', explode(',', $input));
 $results = [];

foreach ($numbers as $num) {
    // PHP 8.6 Feature: writes on const-held objects
    // We can modify the properties of GLOBAL_METRICS even though it's a const.
    GLOBAL_METRICS->mutations++;

    // Execute the partially applied function
    $calc = $mutatorFunc($num);

    // PHP 8.6 Feature: clamp()
    // Ensures the calculated value stays between 0 and the config's maxLimit.
    $clamped = clamp($calc, 0, $config->maxLimit);

    if ($calc !== $clamped) {
        GLOBAL_METRICS->clampsTriggered++;
    }

    $results[] = $clamped;
}

// PHP 8.6 Feature: SortDirection enum
 $direction = strtolower($dirArg) === 'desc' 
    ? SortDirection::Descending 
    : SortDirection::Ascending;

if ($direction === SortDirection::Ascending) {
    sort($results);
} else {
    rsort($results);
}

// ------------------------------------------------------------------------------
// Output Results
// ------------------------------------------------------------------------------
echo "Input Data: $input\n";
echo "Sort Direction: " . $direction->name . "\n";
echo "Output Data: " . implode(', ', $results) . "\n";
echo "Total Mutations: " . GLOBAL_METRICS->mutations . "\n";
echo "Clamps Triggered: " . GLOBAL_METRICS->clampsTriggered . "\n";
echo "Handler Used: " . DataMutator::HANDLER . "\n";