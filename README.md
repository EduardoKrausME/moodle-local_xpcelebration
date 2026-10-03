# XP Celebration (`local_xpcelebration`)

XP Celebration adds positive, personal visual feedback to Moodle without calculating XP and without comparing learners.
It listens for backend changes, persists celebrations in a queue, and presents them one at a time when the learner returns
to a page in the related course.

The plugin depends on `local_personalxp` for XP totals and levels. It reads those values through the public
`xp_manager` API and never treats JavaScript as the source of truth for level changes.

## What can be celebrated

The built-in semantic types are:

- `levelup` — a Personal XP level changed;
- `xpmilestone` — a configured cumulative XP milestone was crossed;
- `personalrecord` — a learner beat one of their own previous records;
- `goalcompleted` — a personal goal was completed;
- `streak` — a consistency/streak threshold was reached;
- `milestone` — an accumulated milestone was achieved;
- `questcompleted` — a teacher-configured quest was completed;
- `rewardunlocked` — a reward became available;
- `courseprogress` — an important course-progress point was reached.

Only `levelup`, configured XP milestones, and course completion are created automatically by this plugin. The other types
are deliberately exposed for companion plugins such as `block_personalstreak`, `local_rewardshop`, `local_personalgoals`,
`local_xpquests`, and `local_xpmilestones`.

## Public API

Any server-side plugin can queue a celebration:

```php
\local_xpcelebration\api::queue(
    $userid,
    $courseid,
    'questcompleted',
    'Quest completed',
    'You completed {quest} in {coursename}.',
    [
        'quest' => 'First week',
        '_theme' => 'achievement',
        '_display' => 'achievement',
        '_animation' => 'particles',
    ]
);
```

Supported placeholders include `{fullname}`, `{level}`, `{xp}`, `{previouslevel}`, `{milestone}`, and `{coursename}`.
Any additional scalar value passed in `data` can also be used as a placeholder.

Reserved presentation keys are `_priority`, `_timeexpired`, `_theme`, `_display`, and `_animation`. Themes are
`minimal`, `confetti`, `levelup`, and `achievement`. Display surfaces are `modal`, `toast`, and `achievement`.
Animations are `confetti`, `particles`, `glow`, `badge`, `progress`, and `none`.

## Queue behaviour

Each queue row starts as `pending`. The AJAX endpoint claims rows inside a database transaction with a row lock, changes
them to `shown`, records `timeshown`, and only then returns them to the browser. Reloading the page therefore cannot replay
the same queue row. Expired pending rows become `expired` and are ignored.

The browser claims only one record at a time. A second celebration is not claimed until the current card has closed, so a
navigation after the first item leaves the rest of the queue pending instead of silently consuming the whole queue.

Priorities decide which pending item appears first, while a configurable session limit prevents a long backlog from
turning into a wall of popups.

## Personal XP integration

The current `local_personalxp` release awards XP from Moodle completion, forum, quiz, and course-completion events but does
not yet emit a dedicated XP-awarded event. XP Celebration therefore observes those same source events at a lower observer
priority and reads the updated total through `local_personalxp\service\xp_manager`.

A small per-user/per-course snapshot stores only the last observed XP and level threshold. A level celebration is created
only when the backend total moves from one configured level to another. The first snapshot is a baseline, so installing
the plugin does not create celebrations for old historical levels.

The plugin also subscribes to `\local_personalxp\event\xp_awarded`, allowing a future Personal XP release to provide a
native event without changing the public celebration API.

## Motion, sound, and accessibility

Celebrations never add a blocking page backdrop or disable navigation. They automatically close, can be dismissed
immediately, and are rendered with semantic live regions or a non-modal dialog depending on the surface.

`prefers-reduced-motion: reduce` disables animation in CSS and JavaScript. Learners can also persist their own “Reduce
animations” preference. Sound is disabled by default; when enabled it is generated with the Web Audio API and does not
load external media.

All icons are inline SVG and all visual effects are local CSS/JavaScript.
