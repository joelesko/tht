# Contributing

THanks for your interest in helping the project! :)

## Bug Reports & Feedback

https://github.com/joelesko/tht/issues

Or join the Discord server: https://discord.gg/GuTgrxRRFC


## Contributions

At this early stage, I am mainly open to the following contributions:

- Bug reports
- Bug fixes
- Feedback on usability (setup, language, and standard library API)
- Unit test coverage (see the `testsite` readme)
- Security testing & feedback. Most sensitive operations are in `Security.php`.

And support/compatibility for:

- Nginx/PHP-FPM
- Cache module: memcached, APC, Redis
- Db module: PostgreSQL


## Usability Feedback

I am interested in feedback like the following:

- I tried doing `X` and expected `Y`, but got `Z` instead.
- I am constantly running into issue `X`.
- I still can't figure out how to do `X`.
- I had to do `X`, but it would be a lot easier if I could just do `Y` instead.
- I got error `X`, which took a long time to fix.  If it told me `Y` up front, it would have been much easier.
- `X` and `Y` are inconsistent with each other, which makes it hard to remember.
- The docs for `X` left out information that took a lot of work to figure out on my own.


## New Features

I want to lead design and implementation of new features for now, so that the core direction is consistent.

However, I'm open to design-related suggestions.



### THT Design Philosophy

In rough order of priority:

- **Secure Defaults**. Security best practices should be built-in wherever possible.  Provide warning signs ('xDanger-' prefix) and guard rails (minor inconveniences) when the user intentionally goes down a less secure path.
- **Batteries Included**. Common patterns should be included in the standard library.  As a rule of thumb, most answers on Stackoverflow.com will result in a clean, obvious solution provided by the language, not copy-and-pasted functions.
- **Usability & Ergonomics**.  Use short, but complete words for function and module names. No abbreviations, where possible.  Most syntactic sugar provides shortcuts, not invisible behavior (magic).
- **Clear Errors**.  More than half of our programming time is spent in an error state, trying to fix something we just added. Error messages should be written in clear, understandable language and suggest solutions. (Elm can provide some inspiration here.)
- **Clean, Not Pedantic**.  THT takes an opinionated stance on (hopefully) non-controversial, well-established approaches to writing code.  It provides helpful structure, while trying to avoid being unreasonably strict.  They are like guard rails: they are intended to keep you moving forward, not to cage you in.
- **Familiarity**.  We should favor design decisions that are already familiar to PHP & JavaScript developers, or web developers in general.  Unless those designs are widely considered to be flawed or conflict with THT's higher priorities (e.g. security).
- **Borrow Good Ideas**.  We are all part of a greater open source community.  Let's freely take the best ideas from other projects (giving credit where we can), and be happy to share our own solutions with other projects.
- **Can't Please Everyone**.  Programmers are an opinionated lot, and there are an infinite number of things to criticize in any programming language.  We can make good decisions and move things forward without getting bogged down in analysis paralysis or heated arguments.

See also: https://tht.dev/about/design-notes


## Writing THT Project Code

If you are writing actual THT project code, you can set this flag in your `app.jcon`:
```
_coreDevMode: true
```

This will include all stack frames in error pages, and also re-compile THT code on every refresh (byassing the cache).


### Error Handling

All errors (php & tht) are routed to this file:
- lib/core/main/Error/ErrorHandler.php

Sometimes internal THT errors will show a blank page (esp if the ErrorHandler itself has an error.)

To show raw PHP errors, you can set `DEV_ERRORS` to `true` in `Tht.php`:

```
define('DEV_ERRORS', true);
```


### Execution Flow

The main files are (in rough order of execution):

- app/public/front.php - The entry point / front controller
- lib/core/main/Tht.php - Overall logic & setup
- lib/core/main/modes/WebMode.php - Determine which page to execute based on the URL route
- lib/core/compiler/Compiler.php - Compile the page if it isn't cached.  Execute the transpiled PHP.

If the target THT needs to be compiled to PHP, the following will run:

- lib/core/compiler/1_Tokenizer.php - Break THT source into tokens, apply template function transforms (e.g. HTML)
- lib/core/compiler/2_Parser.php - Convert the tokens into an AST
- lib/core/compiler/3_EmitterPhp.php - Convert the AST to a PHP file

The parser logic for each symbol is located in these files:
- lib/core/compiler/symbols/*



### Parser

The parser uses "Top Down Operator Precedence" aka "Pratt Parsing", as described here:
http://crockford.com/javascript/tdop/tdop.html

Each symbol can have one or more of the following methods, based on its position in the source:

- asStatement: the start of a complete statement (a tree of expressions). e.g. `return`, `if`
- asLeft: at the start of an expression. e.g. `{`, `[`
- asInner: in the middle of an expression. e.g. `+`, `.`

For example, `-` (minus) can have `asLeft` (prefix, e.g. '-123') and `asInner` (infix, e.g. '45 - 23').



### Internal Code Style

Please try to stay consistent with the code that already exists.

The code doesn't follow any particular PHP standard, but I am open to doing so.

Likewise, many sections of the codebase can be refactored.  For any wide-reaching refactor, please run it by me first, but I'll probably be open to it.



### Unit Tests

Please include unit tests with any patches you submit. If you don't, then I'll have to do it, which means it'll take much longer to get included.

There is a THT app that runs all of the unit tests, located in the `tests` directory.

Just run it like a normal THT app, via `tht server`.

The tests themselves are organized into modules that can be run separate from the main file (if you don't want to wait a few seconds every time), via the "Run Individual Test File" pulldown.

The tests themselves use the [Test](https://tht.dev/manual/module/test) module for checking assertions, etc.

Don't forget to include tests that make sure errors are being triggered correctly -- not just the "happy path".


### Adding Dependencies

THT is mostly free of dependencies.  This is a big reason why it's so much smaller (and arguably faster) than similar projects.

The few that it does have are included in `lib/vendor`.

Please let me know if you plan to work on a feature that will include a new dependency.


## Performance

It's probably too early to optimize for performance.  We will wait until the implementation is stable.

The compiler itself doesn't have to be optimized much.  It is already less than a second in most cases, and this will only affect the developer when they make a change.


## Conduct

It's probably too early for a full Code of Conduct.  Essentially, all contributors should be completely civil and professional.

This is especially true when giving and receiving feedback, and expressing disagreement.  Choose your words carefully and don't be afraid to use smile emojis to lower the intensity. :)

THanks!
