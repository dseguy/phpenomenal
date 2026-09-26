<?php

declare(strict_types=1);

/**
 * Stampede — The PHP 8.6 Feature Derby
 *
 * PHPenomenal 8.6 entry: a terminal race where each runner is a PHP 8.6 feature,
 * implemented with those features.
 *
 * Run: herd php stampede/index.php [--direction=asc|desc]
 */

use Io\Poll\Context as PollContext;
use Io\Poll\Event as PollEvent;
use Time\Duration;

// ---------------------------------------------------------------------------
// Domain — readonly defaults, Override constants, const-held object, enums
// ---------------------------------------------------------------------------

enum Weather: string
{
    case Clear = 'clear';
    case Dusty = 'dusty';
    case Electric = 'electric';

    /** @return list<string> */
    public function __debugInfo(): array
    {
        return [match ($this) {
            self::Clear => 'clear skies over the RFC paddock',
            self::Dusty => 'dust from stampeding elePHPants',
            self::Electric => 'air crackling with partial applications',
        }];
    }
}

final class ToteBoard
{
    public function __construct(
        public string $headline = 'Gates open.',
        public Weather $weather = Weather::Clear,
        /** @var list<string> */
        public array $ticker = [],
    ) {}

    public function call(string $line): void
    {
        $this->ticker[] = $line;
        $this->headline = $line;
    }
}

/** PHP 8.6: the constant is fixed, but the object inside can still be written. */
const TOTE = new ToteBoard();

abstract class Engine
{
    public const NAME = 'base';

    public const VERSION = '0.0';

    abstract public function thrust(): float;
}

final class StampedeEngine extends Engine
{
    #[\Override]
    public const NAME = 'stampede';

    #[\Override]
    public const VERSION = '8.6';

    public function thrust(): float
    {
        return 1.12;
    }
}

final class TurboEngine extends Engine
{
    #[\Override]
    public const NAME = 'turbo';

    #[\Override]
    public const VERSION = '8.6-turbo';

    public function thrust(): float
    {
        return 1.28;
    }
}

readonly class Runner
{
    /** PHP 8.6: class-body default on a readonly property (not a promoted param). */
    public float $position = 0.0;

    public function __construct(
        public string $name,
        public string $slug = 'unknown',
        public Engine $engine = new StampedeEngine(),
    ) {}

    public function withPosition(float $position): self
    {
        return clone($this, ['position' => $position]);
    }
}

// ---------------------------------------------------------------------------
// Race card — trim() form-feed + StreamErrorMode
// ---------------------------------------------------------------------------

function embeddedRaceCard(): string
{
    // \f is intentional: PHP 8.6 trim() strips form feeds.
    return "PFA|Partial Function Application\n"
        ."CLAMP|clamp()\f\n"
        ."DURATION|Time\\Duration\n"
        ."OVERRIDE|#[\\Override] on constants\n"
        ."CONSTOBJ|Writes on const-held objects\n"
        ."READONLY|Readonly property defaults\n"
        ."SORTDIR|SortDirection enum\n"
        ."POLL|Io\\Poll API\f\n"
        ."STREAM|Stream error modes\n";
}

/** @return list<array{code: string, name: string}> */
function loadRaceCard(): array
{
    $context = stream_context_create([
        'stream' => ['error_mode' => StreamErrorMode::Exception],
    ]);

    try {
        fopen(__DIR__.'/.no-such-stampede-card', 'r', false, $context);
    } catch (StreamException $e) {
        TOTE->call('No external card ('.$e->getMessage().') — using built-in field.');
    }

    $lines = preg_split("/\R/", embeddedRaceCard()) ?: [];
    $clean = array_values(array_filter(
        array_map(trim(...), $lines),
        static fn (string $line): bool => $line !== '',
    ));

    return array_map(
        static function (string $line): array {
            [$code, $name] = array_pad(explode('|', $line, 2), 2, 'Unknown');

            return ['code' => $code, 'name' => $name];
        },
        $clean,
    );
}

// ---------------------------------------------------------------------------
// Pipeline — PFA, pipes, clamp, SortDirection
// ---------------------------------------------------------------------------

/** @param list<array{code: string, name: string}> $rows
 *  @return list<Runner> */
function materializeRunners(array $rows): array
{
    return array_map(
        static function (array $row): Runner {
            $slug = $row['code']
                |> strtolower(?)
                |> str_replace(['\\', '#', '[', ']', '(', ')', ' '], '-', ?)
                |> trim(?, '-');

            $engine = mt_rand(0, 1) === 1
                ? new TurboEngine()
                : new StampedeEngine();

            return new Runner(name: $row['name'], slug: $slug, engine: $engine);
        },
        $rows,
    );
}

/**
 * @param list<Runner> $runners
 * @return list<Runner>
 */
function tick(
    /** @param list<Runner> */
    array $runners,
): array {
    return array_map(
        static function (Runner $r): Runner {
            $surge = mt_rand(10, 300) / 100;
            $step = $r->engine->thrust() * $surge * 1.7;

            if (mt_rand(1, 100) <= 20) {
                $step *= mt_rand(160, 320) / 100;
            }
            if (mt_rand(1, 100) <= 12) {
                $step *= mt_rand(15, 45) / 100;
            }

            return $r->withPosition(clamp($r->position + $step, 0.0, 100.0));
        },
        $runners,
    );
}

/**
 * @param list<Runner> $runners
 * @return list<Runner>
 */
function standings(array $runners, SortDirection $direction = SortDirection::Descending): array
{
    $sorted = $runners;
    usort(
        $sorted,
        static function (Runner $a, Runner $b) use ($direction): int {
            $cmp = $a->position <=> $b->position;

            return $direction === SortDirection::Descending ? -$cmp : $cmp;
        },
    );

    return $sorted;
}

/** @param list<Runner> $runners */
function renderTrack(array $runners, int $frame, int $total, Runner $leader): string
{
    $ordered = standings($runners, SortDirection::Descending);
    $width = 42;
    $lines = [
        sprintf(
            '  frame %02d/%02d  weather=%s  leader=%s',
            $frame,
            $total,
            TOTE->weather->value,
            $leader->slug,
        ),
    ];

    foreach ($ordered as $rank => $r) {
        $filled = clamp((int) round(($r->position / 100) * $width), 0, $width);
        $bar = str_repeat('=', $filled).str_repeat('.', $width - $filled);
        $glyph = $r->position >= 100.0 ? '🏆' : '🐘';
        $lines[] = sprintf(
            '  #%d %-12s [%s]%s %5.1f  %s',
            $rank + 1,
            $r->slug,
            $bar,
            $glyph,
            $r->position,
            $r->engine::NAME,
        );
    }

    return implode("\n", $lines);
}

// ---------------------------------------------------------------------------
// Clock — Io\Poll + Time\Duration
// ---------------------------------------------------------------------------

/**
 * @param list<Runner> $runners
 * @return array{runners: list<Runner>, elapsed: Duration}
 */
function runFrames(array $runners, bool $animate): array
{
    $pair = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
    if ($pair === false) {
        throw new RuntimeException('Could not open animation pipe.');
    }

    [$reader, $writer] = $pair;
    stream_set_blocking($reader, false);
    stream_set_blocking($writer, false);

    $poll = new PollContext();
    $poll->add(new StreamPollHandle($reader), [PollEvent::Read], ['role' => 'frame-wake']);

    $frameBudget = Duration::fromMilliseconds(200);
    $elapsed = Duration::fromNanoseconds(0);
    $maxFrames = 40;
    $previousLeader = null;

    // clamp() also works on DateTime — snap a late start into today's window.
    $start = clamp(
        new DateTimeImmutable('yesterday 18:00:00'),
        new DateTimeImmutable('today 00:00:00'),
        new DateTimeImmutable('today 23:59:59'),
    );
    TOTE->call('Official start '.$start->format('H:i:s').' (clamp on DateTime)');

    for ($frame = 1; $frame <= $maxFrames; $frame++) {
        $t0 = hrtime(true);

        if ($frame % 5 === 0) {
            fwrite($writer, '!');
        }

        foreach ($poll->wait($frameBudget) as $watcher) {
            if ($watcher->hasTriggered(PollEvent::Read)) {
                fread($reader, 64);
            }
        }

        $runners = tick($runners);

        if ($frame === 14) {
            TOTE->weather = Weather::Dusty;
            TOTE->call('Dust storm — const TOTE weather rewritten.');
        }
        if ($frame === 28) {
            TOTE->weather = Weather::Electric;
            TOTE->call('Electric air — PFA sparks on the rails.');
        }

        $leader = standings($runners)[0];
        if ($previousLeader !== null && $previousLeader !== $leader->slug) {
            TOTE->call("Lead change! {$leader->slug} takes over from {$previousLeader}");
        }
        $previousLeader = $leader->slug;

        $track = renderTrack($runners, $frame, $maxFrames, $leader);
        if ($animate) {
            if ($frame > 1) {
                echo "\033[".(substr_count($track, "\n") + 2)."A";
            }
            echo preg_replace('/$/m', "\033[K", $track)."\n\033[K\n";
        }

        $elapsed = $elapsed->add(Duration::fromNanoseconds(hrtime(true) - $t0));

        if ($leader->position >= 100.0) {
            TOTE->call("{$leader->name} storms the finish — frame {$frame}");
            break;
        }
    }

    if ($leader->position < 100.0) {
        TOTE->call("Photo finish at frame {$maxFrames}");
    }

    fclose($reader);
    fclose($writer);

    $elapsed = $elapsed->add(
        Duration::fromMicroseconds(250)->multiplyBy($frame),
    );

    return ['runners' => $runners, 'elapsed' => $elapsed];
}

// ---------------------------------------------------------------------------
// CLI
// ---------------------------------------------------------------------------

function formatDuration(Duration $d): string
{
    $ms = intdiv($d->nanoseconds, 1_000_000);

    return sprintf('%d.%03ds', $d->seconds, $ms);
}

/**
 * @param array{direction?: SortDirection, animate?: bool} $options
 */
function runStampede(array $options = []): void
{
    $direction = $options['direction'] ?? SortDirection::Descending;
    $animate = $options['animate'] ?? true;

    TOTE->headline = 'Gates open.';
    TOTE->weather = Weather::Clear;
    TOTE->ticker = [];

    TOTE->call(sprintf(
        'Gates open — %s / %s',
        StampedeEngine::NAME,
        StampedeEngine::VERSION,
    ));

    if ($animate) {
        echo "\n  STAMPEDE — PHP 8.6 Feature Derby\n\n";
    }

    $race = runFrames(materializeRunners(loadRaceCard()), $animate);
    $podium = standings($race['runners'], $direction);

    TOTE->call('Official result board posted.');

    echo "  ── Ticker ─────────────────────────────────────────\n";
    foreach (TOTE->ticker as $line) {
        echo "  · {$line}\n";
    }

    echo "\n  ── Podium ({$direction->name}) ─────────────────────────\n";
    foreach ($podium as $index => $runner) {
        echo sprintf(
            "  #%d  %-36s  %6.2f  %s\n",
            $index + 1,
            $runner->name,
            $runner->position,
            $runner->engine::NAME,
        );
    }

    echo "\n  ── Timing ─────────────────────────────────────────\n";
    echo '  total '.formatDuration($race['elapsed'])."\n";
    echo '  weather ';
    var_dump(TOTE->weather);
    echo "\n";
}

function printHelp(): void
{
    $method = new ReflectionFunction('tick');
    foreach ($method->getParameters() as $parameter) {
        $doc = $parameter->getDocComment();
        if ($doc !== false) {
            echo '  param $'.$parameter->getName().' doc: '.$doc."\n";
        }
    }

    echo <<<'HELP'

  Usage:
    herd php stampede/index.php [--direction=asc|desc]

  Options:
    --direction    SortDirection for the podium (default desc)
    --help         Show this help

HELP;
}

/** @param list<string> $argv */
function cliMain(array $argv): int
{
    $opts = getopt('', ['direction::', 'help']);

    if ($opts === false || isset($opts['help'])) {
        printHelp();

        return isset($opts['help']) ? 0 : 1;
    }

    $direction = match (strtolower((string) ($opts['direction'] ?? 'desc'))) {
        'asc', 'ascending' => SortDirection::Ascending,
        default => SortDirection::Descending,
    };

    runStampede(['direction' => $direction, 'animate' => true]);

    return 0;
}

exit(cliMain($argv));
