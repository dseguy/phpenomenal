<?php

declare(strict_types=1);

/**
 * phenomenal task-queue
 * ----------------------
 * A tiny, dependency-free, single-process priority task queue for PHP 8.6.
 *
 * It enqueues jobs with a priority, runs them in order, and retries the ones
 * that throw — backing off exponentially with a capped delay. Everything runs
 * in one process on one machine; there is no broker, no network, no database.
 *
 * Every compulsory PHP 8.6 feature from the phenomenal check list is used here,
 * and each is used because it genuinely makes the code better, not just to tick
 * a box:
 *
 *   1. Partial Function Application .... building the sort comparator + a clamp
 *   2. Time\Duration ................... representing + capping the retry backoff
 *   3. clamp() ......................... bounding attempt counts and concurrency
 *   4. #[\Override] on a constant ...... pinning the job schema version
 *   5. writes on const-held objects .... the runtime config, declared `const`,
 *                                        tweaked once at boot from argv
 *   6. readonly property defaults ...... queue metadata value object
 *   7. SortDirection enum .............. ordering the queue by priority
 *
 * The PHP 8.5 pipe operator (|>) shows up once too, because partial application
 * was designed to pair with it.
 *
 * Run it:
 *     php8.6 task-queue.php            # normal
 *     php8.6 task-queue.php -v         # verbose (mutates the const-held config)
 *     php8.6 task-queue.php -c 3       # raise max concurrency hint
 *
 * Lint it / check features:
 *     php8.6 -l task-queue.php
 *     php8.6 scripts/check-php86-features.php task-queue.php
 */

namespace Phenomenal\TaskQueue;

use SortDirection;
use Time\Duration;

/* -------------------------------------------------------------------------
 * 5. Writes on const-held objects (PHP 8.6)
 *
 * The runtime configuration lives in a real `const`. PHP 8.6 lets an object
 * sit in a constant expression *and* lets us mutate its (non-readonly)
 * properties directly: CONFIG->prop = ... . We flip exactly one switch at
 * boot, from argv, then treat it as read-only by convention.
 * ---------------------------------------------------------------------- */

final class RuntimeConfig
{
    public int  $maxConcurrency = 1;
    public int  $maxRetries     = 5;
    public bool $verbose        = false;
}

const CONFIG = new RuntimeConfig();

/* -------------------------------------------------------------------------
 * 6. readonly property defaults (PHP 8.6)
 *
 * Before 8.6, a readonly property could not carry a default. Now it can, which
 * is perfect for small metadata value objects that are fixed but never mutated.
 * ---------------------------------------------------------------------- */

final class QueueMeta
{
    public readonly string $engine  = 'phenomenal';
    public readonly string $version = '8.6';
}

/* -------------------------------------------------------------------------
 * 3 + 4. #[\Override] on a constant (PHP 8.6)
 *
 * The interface pins a schema id; the concrete Job overrides it and marks the
 * override explicitly, so a typo in the constant name becomes a hard error.
 * ---------------------------------------------------------------------- */

interface Versioned
{
    const string SCHEMA = 'job/v1';
}

final class Job implements Versioned
{
    #[\Override]
    const string SCHEMA = 'job/v2';

    /** @var callable(): mixed */
    private $work;

    public function __construct(
        public readonly string $name,
        callable $work,
        public readonly int $priority = 0,
        public readonly int $maxAttempts = 3,
    ) {
        $this->work = $work;
    }

    public function run(): mixed
    {
        return ($this->work)();
    }
}

/* -------------------------------------------------------------------------
 * 7. SortDirection enum (PHP 8.6)  +  1. Partial Function Application
 *
 * cmpBy() is a plain 4-argument comparator. We partially apply the first two
 * arguments (the field and the direction) and leave the two jobs open with `?`
 * placeholders — PFA hands us back an ordinary Closure that usort() can call.
 * ---------------------------------------------------------------------- */

function cmpBy(string $field, SortDirection $dir, Job $a, Job $b): int
{
    $ordering = $a->$field <=> $b->$field;

    return $dir === SortDirection::Descending ? -$ordering : $ordering;
}

/**
 * @param list<Job> $jobs
 * @return list<Job>
 */
function prioritise(array $jobs): array
{
    // Partially apply field + direction; the two comparands stay open.
    $byPriority = cmpBy('priority', SortDirection::Descending, ?, ?);

    usort($jobs, $byPriority);

    return $jobs;
}

/* -------------------------------------------------------------------------
 * 2. Time\Duration (PHP 8.6)
 *
 * Exponential backoff expressed as real durations. multiplyBy() returns a new
 * immutable Duration; the `<` operator compares two of them; and we read the
 * value back through the readonly $seconds / $nanoseconds properties to get a
 * sleep in microseconds.
 * ---------------------------------------------------------------------- */

function backoffFor(int $attempt): Duration
{
    $base = Duration::fromMilliseconds(50);
    $cap  = Duration::fromSeconds(2);

    $delay = $base->multiplyBy(2 ** $attempt);

    // Duration implements comparison, so `<` works directly.
    return $delay < $cap ? $delay : $cap;
}

function sleepFor(Duration $d): void
{
    $micros = $d->seconds * 1_000_000 + intdiv($d->nanoseconds, 1_000);
    if ($micros > 0) {
        usleep($micros);
    }
}

/* -------------------------------------------------------------------------
 * The worker.
 * ---------------------------------------------------------------------- */

final class Worker
{
    public function __construct(private readonly QueueMeta $meta = new QueueMeta())
    {
    }

    /**
     * @param list<Job> $jobs
     */
    public function run(array $jobs): void
    {
        $jobs = prioritise($jobs);

        // PFA + clamp(): a reusable closure that pins any attempt count into a
        // sane range. `?` is the open value; min/max are fixed up front.
        $saneAttempts = clamp(?, min: 1, max: CONFIG->maxRetries);

        // The PHP 8.5 pipe operator paired with PFA: count the urgent jobs.
        $urgent = $jobs
            |> array_filter(?, static fn (Job $j): bool => $j->priority > 0)
            |> count(...);

        $this->line(sprintf(
            "%s v%s — %d job(s), %d urgent, concurrency hint %d",
            $this->meta->engine,
            $this->meta->version,
            count($jobs),
            $urgent,
            clamp(CONFIG->maxConcurrency, min: 1, max: 32),
        ));

        foreach ($jobs as $job) {
            $this->process($job, $saneAttempts);
        }
    }

    private function process(Job $job, \Closure $saneAttempts): void
    {
        $attempts = $saneAttempts($job->maxAttempts);

        for ($attempt = 0; $attempt < $attempts; $attempt++) {
            try {
                $result = $job->run();
                $this->line(sprintf(
                    "  [ok]   %-14s (priority %d) -> %s on attempt %d/%d",
                    $job->name,
                    $job->priority,
                    var_export($result, true),
                    $attempt + 1,
                    $attempts,
                ));

                return;
            } catch (\Throwable $e) {
                $last = $attempt + 1 === $attempts;
                $wait = backoffFor($attempt);

                if ($last) {
                    $this->line(sprintf(
                        "  [FAIL] %-14s gave up after %d attempt(s): %s",
                        $job->name,
                        $attempts,
                        $e->getMessage(),
                    ));

                    return;
                }

                $this->debug(sprintf(
                    "  [retry]%-14s attempt %d failed (%s), backing off %dms",
                    $job->name,
                    $attempt + 1,
                    $e->getMessage(),
                    $wait->seconds * 1_000 + intdiv($wait->nanoseconds, 1_000_000),
                ));

                sleepFor($wait);
            }
        }
    }

    private function line(string $msg): void
    {
        echo $msg, PHP_EOL;
    }

    private function debug(string $msg): void
    {
        if (CONFIG->verbose) {
            echo $msg, PHP_EOL;
        }
    }
}

/* -------------------------------------------------------------------------
 * Demo: only runs when the file is executed directly, not when included.
 * ---------------------------------------------------------------------- */

function main(array $argv): int
{
    // Mutate the const-held config once, from the command line.
    foreach ($argv as $i => $arg) {
        if ($arg === '-v') {
            CONFIG->verbose = true;
        }
        if ($arg === '-c' && isset($argv[$i + 1])) {
            CONFIG->maxConcurrency = (int) $argv[$i + 1];
        }
    }

    // A tiny flaky-job factory: fails `$failFirst` times, then returns a value.
    $flaky = static function (string $label, int $failFirst) {
        $seen = 0;

        return static function () use (&$seen, $label, $failFirst): string {
            $seen++;
            if ($seen <= $failFirst) {
                throw new \RuntimeException("transient error #{$seen}");
            }

            return "{$label}-done";
        };
    };

    $jobs = [
        new Job('send-email',  $flaky('email', failFirst: 2), priority: 1, maxAttempts: 4),
        new Job('resize-image', $flaky('image', failFirst: 0), priority: 0),
        new Job('charge-card',  $flaky('charge', failFirst: 99), priority: 5, maxAttempts: 2),
        new Job('warm-cache',   $flaky('cache', failFirst: 1), priority: 0, maxAttempts: 3),
    ];

    (new Worker())->run($jobs);

    return 0;
}

if (\PHP_SAPI === 'cli' && isset($argv) && realpath($argv[0]) === __FILE__) {
    exit(main($argv));
}
