# Contributing

THanks for your interest in helping the project! :)

## Contributions

At this early stage, I am mainly open to the following contributions:
- Bug reports
- Bug fixes
- Windows support
- Feedback on usability (setup, language, and standard library API)
- Unit test coverage (see the `testsite` readme)
- Security testing & feedback. Most sensitive operations are in the `Security.php` module.

## New Features
I want to lead design and implementation of new features for now, so that the core direction is consistent.

However, I'm open to design-related suggestions.

## THT Design Philosophy

In rough order of priority:

- Secure Defaults. Security best practices should be built-in wherever possible.  Provide warning signs ('dangerDanger-' prefix) and guard rails (minor inconveniences) when the user intentionally goes down a less secure path.
- Batteries Included. Common patterns should be included in the standard library.  As a rule of thumb, most answers on Stackoverflow.com will result in a clean, obvious solution provided by the language, not copy-and-pasted functions.
- Usability and ergonomics.
- Clear Errors.  During about 80% of our programming time, we are in an error state, trying to fix something we just added. Error messages should be written in clear, understandable language and suggest solutions. (Elm can provide some inspiration here.)
- Familiar to Web Developers.  We should favor design decisions that are already familiar to JavaScript developers first, then web developers in general.  Unless those designs are widely considered to be flawed (e.g. ASI) or comnflict with THT's higher priorities (e.g. security).
- Take inspiration from successful libraries and languages.  We are all part of a greater open source community.  Let's freely take the best ideas from other projects (giving credit where we can), and be happy to share our own solutions with other projects.

## Compiler

The main files are (in rough order of execution):

- thtApp.php - the entry point in Document Root
- Tht - overall logic & setup
- WebMode - determine which page to execute based on the rourte
- Source - compile the page or if it isn't cached
- Tokenizer - break source into tokens, apply template function transforms (e.g. HTML)
- Parser - convert the symbols into an AST
- Symbols - the parser logic for each symbol
- EmitterPhp - convert the AST to a PHP file
- ErrorHandler - all errors (php & tht) are routed here


The parser uses "Top Down Operator Precedence" aka "Pratt Parser", as described here:
http://crockford.com/javascript/tdop/tdop.html


## Performance
It's probably too early to optimize for performance.  We will wait until the implementation is stable.

The compiler itself doesn't have to be optimized much.  It is already less than a second in most cases, and this will only affect the developer when they make a change.

## Conduct
It's probably too early for a full Code of Conduct.  Essentially, all contributors should be completely civil and professional.

This is especially true when giving and receiving feedback, and expressing disagreement.  Choose your words carefully and don't be afraid to use smile emojis to lower the intensity. :)

THanks!
