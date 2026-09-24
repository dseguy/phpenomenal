<?php

declare(strict_types=1);

/*
 * Podcast episode state machine.
 *
 * Moves an episode from one workflow state to another, in memory, on
 * behalf of a role. The episode lives in the EPISODE constant for the
 * length of one run; nothing is persisted. Each run answers "may this
 * role move an episode from X to Y?" and prints what happened.
 *
 * Usage:
 *   php podcast-episode.php <current_state> <requested_state> <role>
 *
 * States and roles may be given by name (draft, editor) or by position
 * (1, 1). Positions are listed when an invalid value is passed.
 *
 * PHP 8.6 features used:
 *   - #[\Override] on a constant    Role subclasses override Role::NAME / Role::LEVEL
 *   - readonly property defaults    each Role declares its permitted states as a readonly default
 *   - clamp()                       validates that a state/role position is in range
 *   - Time\Duration                 reports how long the command took
 *   - SortDirection                 orders the valid options shown after bad input
 *   - Partial Function Application  resolveIndex(?, $names) builds the state/role resolvers
 *   - writes on const-held objects  the EPISODE constant's properties change as it moves
 *   - Enums with __debugInfo()      State shows its position and next moves in var_dump()
 *
 * Set DEBUG=1 in the environment to var_dump() the episode at the end of the run.
 */

// --- states -----------------------------------------------------------

enum State: string
{
    case Idea = 'idea';
    case Draft = 'draft';
    case Edit = 'edit';
    case Scheduled = 'scheduled';
    case Published = 'published';

    /** Position in the workflow, 0 = first. */
    public function position(): int
    {
        return array_search($this, self::cases(), true);
    }

    /** @return list<State> States reachable in one step from this one. */
    public function next(): array
    {
        return match ($this) {
            self::Idea => [self::Draft],
            self::Draft => [self::Idea, self::Edit],
            self::Edit => [self::Draft, self::Scheduled],
            self::Scheduled => [self::Edit, self::Published],
            self::Published => [],
        };
    }

    public function canMoveTo(self $target): bool
    {
        return in_array($target, $this->next(), true);
    }

    public function __debugInfo(): array
    {
        return [
            'value' => $this->value,
            'position' => $this->position(),
            'next' => array_map(fn (self $s) => $s->value, $this->next()),
            'terminal' => $this->next() === [],
        ];
    }
}

// --- episode --------------------------------------------------------

final class Episode
{
    public State $state = State::Idea;
    public int $transitions = 0;
    /** @var list<string> */
    public array $history = [];
}

// The one in-memory episode. The constant always points at the same
// object, but that object's properties are written as it moves.
const EPISODE = new Episode();

// --- roles ------------------------------------------------------------

/*
 * A role may only touch episodes whose current AND requested states are
 * both in its $states list. The defaults below are the whole permission
 * model: no constructor, no setters, and readonly means nothing can widen
 * a role's permissions at runtime.
 */
abstract class Role
{
    public const string NAME = 'role';
    public const int LEVEL = 0;

    /** @var list<State> */
    public readonly array $states = [];

    public function permits(State $from, State $to): bool
    {
        return in_array($from, $this->states, true)
            && in_array($to, $this->states, true);
    }
}

final class ViewOnly extends Role
{
    #[\Override]
    const string NAME = 'view';
    #[\Override]
    const int LEVEL = 0;
}

final class Editor extends Role
{
    #[\Override]
    const string NAME = 'editor';
    #[\Override]
    const int LEVEL = 1;

    public readonly array $states = [State::Idea, State::Draft, State::Edit];
}

final class Admin extends Role
{
    #[\Override]
    const string NAME = 'admin';
    #[\Override]
    const int LEVEL = 2;

    public readonly array $states = [
        State::Idea, State::Draft, State::Edit, State::Scheduled, State::Published,
    ];
}

/** @return list<class-string<Role>> Ordered by LEVEL. */
function roleClasses(): array
{
    return [ViewOnly::class, Editor::class, Admin::class];
}

// --- input parsing ----------------------------------------------------

/*
 * Resolve a name or numeric position to an index into $names.
 * Unknown names map to -1; clamp() then rejects anything out of range,
 * so a single check covers "bad name", "negative number" and "too big".
 */
function resolveIndex(string $input, array $names): ?int
{
    $input = strtolower(trim($input));
    $index = ctype_digit($input) ? (int) $input : array_search($input, $names, true);
    $index = $index === false ? -1 : $index;

    return clamp($index, 0, count($names) - 1) === $index ? $index : null;
}

// --- output helpers ---------------------------------------------------

/**
 * @param array<int, string> $options position => name
 * @return array<int, string>
 */
function sortOptions(array $options, SortDirection $direction): array
{
    match ($direction) {
        SortDirection::Ascending => ksort($options),
        SortDirection::Descending => krsort($options),
    };
    return $options;
}

/** @param array<int, string> $options position => name */
function printOptions(string $heading, array $options, SortDirection $direction): void
{
    $arrow = $direction === SortDirection::Ascending ? '↑' : '↓';
    echo "{$heading} ({$direction->name} {$arrow}):" . PHP_EOL;
    if ($options === []) {
        echo "  (none)" . PHP_EOL;
    }
    foreach (sortOptions($options, $direction) as $position => $name) {
        printf("  %d  %s%s", $position, $name, PHP_EOL);
    }
}

function formatDuration(Time\Duration $d): string
{
    $micro = $d->seconds * 1_000_000 + intdiv($d->nanoseconds, 1_000);
    return $micro >= 1_000
        ? sprintf('%.3f ms', $micro / 1_000)
        : sprintf('%d µs', $micro);
}

// --- main -------------------------------------------------------------

function run(array $argv): int
{
    if (count($argv) !== 4) {
        echo "Usage: php {$argv[0]} <current_state> <requested_state> <role>" . PHP_EOL . PHP_EOL;
        printOptions('States', array_map(fn (State $s) => $s->value, State::cases()), SortDirection::Ascending);
        printOptions('Roles', array_map(fn (string $r) => $r::NAME, roleClasses()), SortDirection::Descending);
        return 2;
    }

    [, $currentArg, $requestedArg, $roleArg] = $argv;

    // States list in workflow order; roles most-privileged first.
    $stateNames = array_map(fn (State $s) => $s->value, State::cases());
    $roleNames = array_map(fn (string $r) => $r::NAME, roleClasses());

    $resolveState = resolveIndex(?, $stateNames);
    $resolveRole = resolveIndex(?, $roleNames);

    $invalid = false;
    foreach (['current_state' => $currentArg, 'requested_state' => $requestedArg] as $label => $arg) {
        if ($resolveState($arg) === null) {
            echo "Invalid {$label}: '{$arg}'" . PHP_EOL;
            printOptions('Valid states', $stateNames, SortDirection::Ascending);
            $invalid = true;
        }
    }
    if ($resolveRole($roleArg) === null) {
        echo "Invalid role: '{$roleArg}'" . PHP_EOL;
        printOptions('Valid roles', $roleNames, SortDirection::Descending);
        $invalid = true;
    }
    if ($invalid) {
        return 2;
    }

    $current = State::cases()[$resolveState($currentArg)];
    $requested = State::cases()[$resolveState($requestedArg)];
    $roleClass = roleClasses()[$resolveRole($roleArg)];
    $role = new $roleClass();
    $roleName = $role::NAME;

    echo "Role:    {$roleName} (level " . $role::LEVEL . ")" . PHP_EOL;
    EPISODE->state = $current;
    echo "Episode: " . EPISODE->state->value . " → {$requested->value}" . PHP_EOL;

    // Options this role actually has from the current state.
    $allowed = [];
    foreach ($current->next() as $target) {
        if ($role->permits($current, $target)) {
            $allowed[$target->position()] = $target->value;
        }
    }

    if (!$current->canMoveTo($requested)) {
        echo "Denied:  {$current->value} cannot move to {$requested->value}; episode stays " . EPISODE->state->value . PHP_EOL;
        printOptions("Moves available to {$roleName} from {$current->value}", $allowed, SortDirection::Ascending);
        return 1;
    }

    if (!$role->permits($current, $requested)) {
        echo "Denied:  {$roleName} may not move an episode from {$current->value} to {$requested->value}; episode stays " . EPISODE->state->value . PHP_EOL;
        printOptions("Moves available to {$roleName} from {$current->value}", $allowed, SortDirection::Ascending);
        return 1;
    }

    EPISODE->state = $requested;
    EPISODE->transitions++;
    EPISODE->history[] = "{$current->value} → {$requested->value} by {$roleName}";

    echo "OK:      episode is now " . EPISODE->state->value . PHP_EOL;
    echo "History: " . implode('; ', EPISODE->history) . " (" . EPISODE->transitions . " transition)" . PHP_EOL;
    return 0;
}

$start = hrtime(true);
$status = run($argv);
$elapsed = Time\Duration::fromNanoseconds(hrtime(true) - $start);

echo "Took:    " . formatDuration($elapsed) . PHP_EOL;

if (getenv('DEBUG')) {
    var_dump(EPISODE);
}
exit($status);
