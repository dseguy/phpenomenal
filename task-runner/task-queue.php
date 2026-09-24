<?php

declare(strict_types=1);

/*
 * Tiny task queue exercising every PHP 8.6 item on the list:
 *   - Partial Function Application   -> $formatTask
 *   - Time\Duration                  -> Task::$duration
 *   - clamp()                        -> normalizePriority()
 *   - #[\Override] on a constant     -> Task::KIND
 *   - writes on const-held objects   -> LOGGER->entries++
 *   - readonly property defaults     -> Task::$priority
 *   - SortDirection enum             -> TaskQueue::sorted()
 *
 * Requires PHP 8.6. Verified against a real 8.6.0-dev build
 * (shivammathur/php tap); this box's default `php` is still 8.5.
 */

final class Logger
{
    public int $entries = 0;

    public function log(string $message): void
    {
        $this->entries++;
        echo "[{$this->entries}] {$message}", PHP_EOL;
    }
}

// A global constant can hold an object (allowed since 8.1's "new in
// initializers"). What's new in 8.6 is that we're now allowed to write to
// its properties from outside, e.g. `LOGGER->entries++` further down.
const LOGGER = new Logger();

interface Labelled
{
    const string KIND = 'generic';
}

final class Task implements Labelled
{
    #[\Override]
    const string KIND = 'task';

    public function __construct(
        public readonly string $name,
        public readonly Time\Duration $duration,
        // Readonly property defaults: most tasks are 'normal' priority, so bake it in.
        public readonly int $priority = 5,
    ) {
    }
}

final class TaskQueue
{
    /** @var Task[] */
    private array $tasks = [];

    public function add(Task $task): void
    {
        LOGGER->log("queued '{$task->name}' (" . $task::KIND . ')');
        $this->tasks[] = $task;
    }

    /** @return list<Task> */
    public function sorted(SortDirection $direction): array
    {
        $tasks = $this->tasks;
        usort(
            $tasks,
            fn(Task $a, Task $b) => $direction === SortDirection::Ascending
                ? Time\Duration::compare($a->duration, $b->duration)
                : Time\Duration::compare($b->duration, $a->duration),
        );
        return $tasks;
    }
}

function normalizePriority(int $raw): int
{
    // clamp() instead of hand-rolled min(max($raw, 1), 10).
    return clamp($raw, 1, 10);
}

// Partial Function Application: fix the format, leave the three values as holes.
$formatTask = sprintf('  %-10s prio=%-2d  %ds', ?, ?, ?);

$queue = new TaskQueue();
$queue->add(new Task('deploy', Time\Duration::fromSeconds(300), normalizePriority(9)));
$queue->add(new Task('lint', Time\Duration::fromSeconds(45))); // uses the readonly default (5)
$queue->add(new Task('backup', Time\Duration::fromSeconds(1800), normalizePriority(99)));

echo PHP_EOL, 'Tasks by duration (longest first):', PHP_EOL;
foreach ($queue->sorted(SortDirection::Descending) as $task) {
    echo $formatTask($task->name, $task->priority, $task->duration->seconds), PHP_EOL;
}

$totalSeconds = array_sum(array_map(
    fn(Task $t) => $t->duration->seconds,
    $queue->sorted(SortDirection::Ascending),
));
$total = Time\Duration::fromSeconds($totalSeconds);
echo PHP_EOL, 'Total scheduled time: ' . intdiv($total->seconds, 60) . ' minute(s)', PHP_EOL;

// New in 8.6: direct property writes on an object referenced by a constant.
LOGGER->entries++;
echo 'Log entries (including this manual bump): ', LOGGER->entries, PHP_EOL;
