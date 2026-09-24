# Stampede

A tiny PHP 8.6 demo for the [PHPenomenal 8.6](https://www.exakat.io/phpenomenal-8-6-a-challenge-to-for-phps-next-release/) challenge.

It runs a fake horse race in your terminal. Each “horse” is named after a PHP 8.6 feature — and the race code itself **actually uses** those features.

## Run it

Needs PHP 8.6+:

```bash
herd php stampede.php
```

Every run is random. The race takes about **five seconds** so you can watch places change live.

## How the race works

Track goes from **0 to 100**.

1. Built-in list of runners (feature names).
2. Everyone starts at **0**.
3. Each step is paced by `Time\Duration` + `Io\Poll` (~200ms).
4. Runners surge/stumble randomly; board re-sorts with `SortDirection`.
5. Hit **100** → finished. Others ranked by how far they got.

## How it uses PHP 8.6

| Feature | Where |
|---|---|
| Partial application (`?`) + pipes | Building each runner’s slug |
| `clamp()` | Keep position on 0…100; also clamp a DateTime into today |
| `Time\Duration` | Frame wait + timing math (`add` / `multiplyBy`) |
| `Io\Poll` | Race clock that drives each live redraw |
| `#[\Override]` on constants | `StampedeEngine` / `TurboEngine` `NAME` + `VERSION` |
| Writes on const-held objects | Mid-race `TOTE->weather = …` / `TOTE->call(…)` |
| Readonly property defaults | `Runner` defaults for slug, position, engine |
| `SortDirection` | Live board order + final podium (`--direction`) |
| Stream error mode | Failed open with `StreamErrorMode::Exception` |
| Enum `__debugInfo()` | Weather dump at the end |
| Parameter doc comments | On `tick()`; shown via `--help` |
| `trim()` form-feed | Built-in card lines include `\f` |

## Options

```bash
herd php stampede.php --direction=desc
herd php stampede.php --help
```

## Files

- `stampede.php` — the whole app
- `README.md` — this
