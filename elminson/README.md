# phenomenal task-queue

A tiny, dependency-free, single-process **priority task queue** for **PHP 8.6**.

It enqueues jobs with a priority, runs them highest-priority first, and retries
the ones that throw — backing off exponentially with a capped delay. Everything
runs in one process on one machine: no broker, no network, no database, no
Composer packages.

This is my entry for [**phenomenal**](https://github.com/dseguy/phpenomenal) —
*write PHP 8.6 before it arrives.*

## Why a task queue?

Retry-with-backoff job runners are the one place where almost every new 8.6
feature earns its keep at once — durations, clamping, sort order, and small
immutable value objects all show up naturally. So the features aren't bolted
on to tick boxes; each one replaces code that used to be clumsier.

## The 8.6 features it uses

| # | Feature | Where it's used |
|---|---------|-----------------|
| 1 | **Partial Function Application** (`?`, `...`) | Building the `usort` comparator from `cmpBy()`, and a reusable `clamp(?, min:, max:)` closure |
| 2 | **`Time\Duration`** | Representing and capping the exponential retry backoff |
| 3 | **`clamp()`** | Bounding attempt counts and the concurrency hint |
| 4 | **`#[\Override]` on a constant** | Pinning `Job::SCHEMA` as an explicit override of `Versioned::SCHEMA` |
| 5 | **Writes on const-held objects** | `const CONFIG = new RuntimeConfig()`, mutated once at boot from `argv` |
| 6 | **readonly property defaults** | The `QueueMeta` value object |
| 7 | **`SortDirection` enum** | Ordering the queue by priority |

Bonus: the **PHP 8.5 pipe operator** (`|>`) appears once, paired with partial
application to count urgent jobs — the pairing 8.6's PFA was designed for.

## Run it

```bash
php8.6 task-queue.php          # run the demo
php8.6 task-queue.php -v       # verbose: show each retry + backoff
php8.6 task-queue.php -c 3     # raise the concurrency hint (clamped to 1..32)
```

Expected output (abridged):

```
phenomenal v8.6 — 4 job(s), 2 urgent, concurrency hint 1
  [ok]   charge-card    ...        # priority 5 runs first
  [ok]   send-email     ...        # succeeds on its 3rd attempt
  [ok]   warm-cache     ...        # succeeds on its 2nd attempt
  [ok]   resize-image   ...
```

(`charge-card` is wired to always fail, so with `-v` you'll see it exhaust its
attempts and back off between them before giving up.)

## Check it

```bash
php8.6 -l task-queue.php                               # lint
php8.6 scripts/check-php86-features.php task-queue.php # feature checklist
```

The entry is deliberately named `task-queue.php` so the repo's
`check-php86-features.php` finds it with no arguments.

## Getting PHP 8.6 before release

```bash
# macOS (Homebrew)
brew install shivammathur/php/php@8.6

# Docker
docker run --rm -v "$PWD":/app -w /app php:8.6-rc-cli php task-queue.php
```

## How it's structured

Single file, read top to bottom:

1. `RuntimeConfig` + `const CONFIG` — the mutable-at-boot configuration.
2. `QueueMeta` — readonly metadata with defaults.
3. `Versioned` / `Job` — the job model and its overridden schema constant.
4. `cmpBy()` / `prioritise()` — priority ordering via `SortDirection` + PFA.
5. `backoffFor()` / `sleepFor()` — backoff built on `Time\Duration`.
6. `Worker` — the run loop: sort, then run each job with clamped retries.
7. `main()` — the demo, run only when the file is executed directly.

## License

Public domain / do whatever you like with it. Shared so it can be displayed on
the phenomenal repo.
