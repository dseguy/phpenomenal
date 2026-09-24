# Podcast Episode State Machine

A command-line tool that moves a podcast episode through its workflow. The episode is held in memory in the `EPISODE` constant, and each run checks one transition for one role and prints the result.

```
idea ⇄ draft ⇄ edit ⇄ scheduled → published
```

| Role     | Can do                                                         |
|----------|----------------------------------------------------------------|
| `view`   | nothing                                                        |
| `editor` | move between `idea`, `draft` and `edit` (can't schedule or publish) |
| `admin`  | every transition                                               |

## Running

You need Docker. The Makefile builds a `php:8.6.0beta2-cli` image and runs the script inside it.

```sh
make run CURRENT=edit REQUESTED=scheduled ROLE=admin
make run CURRENT=idea REQUESTED=draft ROLE=editor DEBUG=1   # also var_dump() the episode
make lint
make shell    # bash inside the container
```

You can give states and roles by name or by number (`make run CURRENT=3 REQUESTED=4 ROLE=2`). If you pass something invalid, the tool lists the valid options.

Exit codes: `0` for an allowed move, `1` for a denied move, `2` for invalid input.

## PHP 8.6 features

- **`#[\Override]` on a constant:** `ViewOnly`, `Editor` and `Admin` override `Role::NAME` and `Role::LEVEL`.
- **readonly property defaults:** each role sets its permitted states with `public readonly array $states = [...]`, so the permissions can't change at runtime.
- **`clamp()`:** checks that a state or role number is in range. Unknown names become `-1`, so the same check rejects them.
- **`Time\Duration`:** reports how long the command took, using `hrtime()`.
- **`SortDirection`:** orders the valid options printed after bad input. States are listed in workflow order (ascending) and roles from most to least privileged (descending).
- **Partial Function Application:** `resolveIndex(?, $stateNames)` and `resolveIndex(?, $roleNames)` fix the list argument once and give two single-argument resolvers.
- **Writes on const-held objects:** the episode is `const EPISODE = new Episode()`. An allowed move writes `EPISODE->state`, increments `EPISODE->transitions` and appends to `EPISODE->history`.
- **Enums with `__debugInfo()`:** `State` returns its value, position, next moves and whether it's terminal. With `DEBUG=1`, `var_dump(EPISODE)` shows that information for the episode's state.
