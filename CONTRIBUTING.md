# Contributing to the UCF Brand Block Theme

Thank you for your interest in contributing to this project! If you are a developer for UCF and want to contribute to this theme, we'd love to hear from you.

This document outlines the best ways to submit new ideas or inform us of bugs. Please take a moment to review these guidelines before submitting new issues or pull requests in order to make the contribution process easy and effective for everyone involved.

## Quick links

-   [Using the issue tracker](#using-the-issue-tracker)
-   [Bug reports](#bug-reports)
-   [Feature requests](#feature-requests)
-   [Pull requests](#pull-requests)
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

Feature requests are welcome. Before you submit one, take a moment to review the [escalation ladder in `README.md`](README.md): new color or typography variants should reuse existing `theme.json` tokens and registered block styles before anything new is added. This keeps the theme small and on-brand.

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
    The `build/` directory and `assets/css/main.css` are **committed** so the theme can be deployed without a build step. Always run `npm run build` and commit the updated output as part of any change that touches `blocks/` or `src/scss/`.

Other useful scripts:

-   `npm run start` — rebuild block scripts on change.
-   `npm run watch` — rebuild the stylesheet on change.
-   `npm run lint:js` — lint the block sources.
-   `npm run format` — format JS, JSON, and YAML to the WordPress standard.

### Submitting a pull request

1. Create a new branch off of `main` for your work.
2. Make your changes, following the [code standards](#code-standards-and-style-guides) below.
3. Run `npm run build` and commit the regenerated assets.
4. Open a pull request against `main` with a clear description, following the pull request template.

## Code standards and style guides

This project follows the [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/):

-   **PHP** — WordPress PHP standards (tabs, Yoda conditions, escaped output). Verified with PHPCS.
-   **JavaScript & JSON** — formatted with Prettier using the shared `@wordpress/prettier-config` (`npm run format`) and linted with `@wordpress/scripts` (`npm run lint:js`).
-   **SCSS** — declarations are kept alphabetical; block styles live in `src/scss/`, not in the block folders.
-   **Indentation** — tabs, enforced by `.editorconfig`.

Architectural conventions specific to this theme (the token escalation ladder, static-only custom blocks, and pattern serialization rules) are documented in `README.md`. Please review it before opening a pull request that adds patterns, blocks, or block styles.
