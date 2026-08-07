# Contributing to the UCF Brand Block Theme

Thank you for your interest in contributing to this project! If you are a developer for UCF and want to contribute to this theme, we'd love to hear from you.

This document outlines the best ways to submit new ideas or inform us of bugs. Please take a moment to review these guidelines before submitting new issues or pull requests in order to make the contribution process easy and effective for everyone involved.

## Quick links

-   [Using the issue tracker](#using-the-issue-tracker)
-   [Bug reports](#bug-reports)
-   [Feature requests](#feature-requests)
-   [Pull requests](#pull-requests)
-   [Tests](#tests)
-   [Code standards and style guides](#code-standards-and-style-guides)

---

## Using the issue tracker

The [issue tracker](https://github.com/UCF/ucf-brand-block-theme/issues) in GitHub is the preferred channel for [bug reports](#bug-reports), [feature requests](#feature-requests) and [submitting pull requests](#pull-requests).

Please do not use the issue tracker for personal support requests.

## Bug reports

A bug is a demonstrable problem that is caused by the code in the repository. Concise and thorough bug reports will help us fix reported problems more quickly and effectively.

### Before submitting a bug report

1. **Use the GitHub issue search** — check if the issue has already been reported. Feel free to comment on the existing issue if it is still open and you have new information to share.
2. **Check if the issue has been fixed** — if you're not running the latest version of the theme, please check your code against the repo's `main` branch first.

### Submit a bug report

If you've followed the steps above and have a valid bug report to submit, [create a new issue in GitHub](https://github.com/UCF/ucf-brand-block-theme/issues/new?template=bug_report.md).

Add a descriptive, understandable title and details about the bug in the description field, following the template provided. What steps will reproduce the issue? What browser(s) and OS experience the problem? What would you expect to be the outcome?

## Feature requests

Feature requests are welcome. Before you submit one, take a moment to review the [escalation ladder in `docs/architecture.md`](docs/architecture.md#the-escalation-ladder): new color or typography variants should reuse existing `theme.json` tokens and registered block styles before anything new is added. This keeps the theme small and on-brand.

[Submit a feature request](https://github.com/UCF/ucf-brand-block-theme/issues/new?template=feature_request.md) using the provided template.

## Pull requests

Good pull requests — patches, improvements, new features — are a fantastic help.

Please ask first before embarking on any significant pull request (e.g. implementing features, refactoring code), otherwise you risk spending a lot of time working on something that the project's maintainers might not want to merge.

### Getting started

This theme lives at `wp-content/themes/ucf-brand-block-theme` in a WordPress install.

1. Fork and clone the repository.
2. Install dependencies:
    ```
    npm install
    ```
3. Build the compiled assets (both the block scripts and the stylesheet):
    ```
    npm run build
    ```
    The `build/` directory is **committed** so the theme can be deployed without a build step. Always run `npm run build` and commit the updated output as part of any change that touches anything under `src/`.

Other useful scripts:

-   `npm test` — run the two fast suites (~3 seconds, no Docker).
-   `npm run test:php` — the PHP unit suite only.
-   `npm run test:js` — the block and markup suites only.
-   `npm run test:integration` — the WordPress integration suite. Needs Docker; boots the
    environment, runs the tests, and stops it again.
-   `npm run test:integration:only` — the same tests against an already-running environment,
    for a fast loop while iterating (`npm run env:start` / `npm run env:stop` around it).
-   `npm run test:a11y` — the accessibility suite (axe-core through Playwright). Needs Docker
    and a Playwright browser: run `npx playwright install chromium` once, or set
    `PW_CHANNEL=chrome` to drive a Chrome you already have.
-   `npm run env:seed` / `npm run test:a11y:only` — seed once, then audit in a loop.
-   `npm run test:all` — everything, integration and accessibility included.
-   `npm run start` — rebuild block and editor scripts on change.
-   `npm run watch` — rebuild the stylesheet on change.
-   `npm run lint:js` — lint everything under `src/blocks/`, `src/js/` and `tests/js/`.
-   `npm run lint:version` — check that `style.css` and `package.json` agree on the version.
-   `npm run format` — format JS, JSON, and YAML to the WordPress standard.

`composer.json` pins `config.platform.php` to **8.1**, the version `style.css` declares as the
minimum. Without it, Composer resolves against whatever PHP the developer happens to be
running and can lock a package that needs something newer — which installs fine locally and
then fails for everyone else with "your lock file does not contain a compatible set of
packages". Run `composer update` on the pinned platform, not on your own.

`composer audit` is clean. `npm audit` is not — it reports around forty advisories across the
JavaScript dev tree, nearly all of them deep in `@wordpress/scripts`' dependency graph and
predating this theme. None of it reaches `build/`, since the build externalizes `@wordpress/*`
to the globals WordPress already ships. Worth a look, not a blocker.

The theme's version lives in **two** places — the `Version:` header in `style.css`, which is
what WordPress reads, and `version` in `package.json`. Bump both together; `npm run
lint:version` fails if they drift.

### Submitting a pull request

1. Create a new branch off of `main` for your work.
2. Make your changes, following the [code standards](#code-standards-and-style-guides) below.
3. Add tests — see [Tests](#tests) below for what your change needs.
4. Run `npm run build` and commit the regenerated assets.
5. Run `npm test` and make sure both suites pass.
6. Open a pull request against `main` with a clear description, following the pull request template.

## Tests

New code ships with its test. Two of the four cases are automatic, so this is less work than
it sounds:

| You added                   | You write                                                                                             |
| --------------------------- | ----------------------------------------------------------------------------------------------------- |
| A function in `includes/`   | A case in `tests/php/`, or `tests/integration/` if it needs WordPress — nothing enforces this for you |
| A block in `src/blocks/`    | An entry in `tests/js/helpers/register-blocks.js`, and — if no pattern or template renders it — a page in `tests/a11y/seed.php`. Both fail until you do |
| A pattern in `patterns/`    | Nothing. The markup sweep and the accessibility suite both read the directory — just run them          |
| A template part in `parts/` | Nothing. Same sweep                                                                                   |
| A `register_block_style()`  | Nothing, unless it is on a block type the accessibility seeder has no sample markup for — then the seed fails until you add one |

Anything that genuinely needs WordPress — a meta query, the `render_block` filter, a real
`WP_Query` — goes in `tests/integration/`, which runs against a real WordPress under wp-env.
Do not mock those into the fast suite; a test that mocks the thing it is testing tests the
mock. Note that `npm test` deliberately never touches Docker, so the integration and
accessibility tiers are separate commands.

Four rules are worth knowing before you write anything:

-   **Prove a new test can fail.** Break the code it covers, watch it go red, then restore it.
    Two tests in this repo have passed against nothing at all — one because blocks silently
    failed to register, one because an XSS payload sat outside the range being escaped. Both
    looked green.
-   **Use annotations, not attributes.** PHPUnit is pinned to `^9.6` because WordPress's test
    suite calls an API that PHPUnit removed in 10. Write `@dataProvider` and `@covers`, not
    `#[DataProvider]`.
-   **Don't check markup with a block's `isValid`.** `parse()` recovers mismatched markup by
    migrating it through the block's `deprecated` array and then reports `isValid: true` — the
    exact bug this theme once shipped in `section-index.php` passes that check. Use
    `isValidBlockContent()` instead.
-   **An accessibility audit of the wrong page passes.** A seeded page that 404s renders the
    (perfectly accessible) 404 template; a variant page whose sample markup went stale renders
    in default colors. So the suite asserts the HTTP status and the expected `is-style-*` class
    before it trusts a result. Anything you add there should hold the same line.

CI runs the two fast suites on **every commit** (`.github/workflows/ci.yml`), and adds the
WordPress integration and accessibility suites on **pull requests and main**
(`.github/workflows/ci-full.yml`). The fast workflow also rebuilds and fails if the committed
`build/` output is stale. Linting and formatting run there too, but as an advisory job — they
report findings and never fail the build.

The accessibility job does gate, and it posts its findings as a comment on the pull request —
one comment, edited in place, grouped by finding rather than by test, with the measured
contrast ratio and both hex values so the result can go straight to a designer.

[`tests/README.md`](tests/README.md) covers what each suite is for and the non-obvious parts of
the setup, including what each guard in the accessibility suite exists to prevent.

## Code standards and style guides

This project follows the [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/):

-   **PHP** — WordPress PHP standards (tabs, Yoda conditions, escaped output). Verified with PHPCS.
-   **JavaScript & JSON** — formatted with Prettier using the shared `@wordpress/prettier-config` (`npm run format`) and linted with `@wordpress/scripts` (`npm run lint:js`).
-   **SCSS** — declarations are kept alphabetical; block styles live in `src/scss/`, not in the block folders.
-   **Indentation** — tabs, enforced by `.editorconfig`.

Architectural conventions specific to this theme — the token escalation ladder, the `--brand-*` role layer, static-only custom blocks, the one-topic-per-file rule for `includes/`, and the pattern serialization rules — are documented in [`docs/architecture.md`](docs/architecture.md). Please review it before opening a pull request that adds patterns, blocks, block styles, or PHP.
