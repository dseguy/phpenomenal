# task-queue.php

A small, self-contained script (`task-queue.php`) that builds a tiny in-memory
task queue, purely as a vehicle to exercise new PHP 8.6 language and standard-library
features in real, runnable code. All it does is add tasks with a name, a duration and 
a priority, sort them by duration, and log what happened. 

## What it does

1. Defines a `Logger` class and stores a single instance in a global
   constant, `LOGGER`.
2. Defines a `Labelled` interface with a `KIND` class constant, and a `Task`
   class that implements it, overrides `KIND`, and holds a name, a
   `Time\Duration`, and a `readonly` priority that defaults to `5`.
3. Defines a `TaskQueue` class that stores tasks and can return them sorted
   by duration, ascending or descending.
4. Defines `normalizePriority()`, which clamps a raw priority into the
   `1..10` range.
5. Builds three tasks (`deploy`, `lint`, `backup`), each with different
   durations and priorities, adds them to the queue, and logs each addition
   through `LOGGER`.
6. Prints the tasks sorted by duration (longest first), each line formatted
   through a partially-applied `sprintf()`.
7. Computes and prints the total scheduled time across all tasks.
8. Bumps and prints the logger's entry count by writing directly to a
   property on the object held in the `LOGGER` constant.

Run it with a PHP 8.6 build:

```sh
php task-queue.php
```

## PHP 8.6 features

This script pass the repository check and linting with PHP 8.6-beta (Sep, 20th)

```
php86 -l task-runner/task-queue.php
php86 scripts/check-php86-features.php task-runner/task-queue.php
```

| Feature | Where in the script |
|---|---|
| **Partial Function Application** (`?` placeholders in a call) | `$formatTask = sprintf('...', ?, ?, ?);` — fixes the format string and leaves the three values as holes, producing a reusable closure. |
| **`Time\Duration`** | `Task::$duration`, built via `Time\Duration::fromSeconds()` and compared with `Time\Duration::compare()` when sorting. |
| **`clamp()`** | `normalizePriority()` uses the built-in `clamp($raw, 1, 10)` instead of hand-rolled `min(max($raw, 1), 10)`. |
| **`#[\Override]` on a class constant** | `Task::KIND` is marked `#[\Override]` when overriding the `KIND` constant declared on the `Labelled` interface. |
| **Direct writes to properties on an object held by a constant** | `LOGGER->entries++;` — previously constants could hold objects (since 8.1's "new in initializers"), but writing to their properties from outside was not allowed until 8.6. |
| **`readonly` property defaults** | `Task::$priority` is `readonly` and defaults to `5` in the constructor signature. |
| **`SortDirection` enum** | `TaskQueue::sorted()` takes a `SortDirection` (`Ascending`/`Descending`) to pick sort order. |
