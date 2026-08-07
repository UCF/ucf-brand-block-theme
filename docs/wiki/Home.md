# UCF Brand Block Theme — Editor Guide

This wiki is for the people who **write** the brand guide, not the people who build the
theme. It covers what you can do from inside WordPress: how the editor is set up, what the
ready-made patterns are for, how the left-hand drawer builds itself, and why the headings
you choose change the navigation.

You do not need to know any code to use this theme. You do need to know a few of its
rules, because several things that look like formatting choices are actually structure.

## Start here

1. **[Building in the Editor](Building-in-the-Editor)** — the Gutenberg basics as this
   theme uses them: blocks, groups, List View, the brand palette, block styles, and the
   things you should never reach for.
2. **[Color, Guardrails and Accessibility](Color-and-Guardrails)** — why there is no color
   picker, how the four fields work, and where the guardrails stop.
3. **[Patterns: Units, Groups, Sections, Pages](Patterns)** — the four-rung pattern
   ladder in the inserter, what each rung is for, and how to combine them.
4. **[Sections and Semantics](Sections-and-Semantics)** — why you build bands with the
   **Section** pattern instead of a plain Group, and what the `<section>` tag buys you.
5. **[Headings and Subsections](Headings-and-Subsections)** — H2 is structural here. It
   builds the sub-menu, earns a numbered badge, and becomes a shareable link.
6. **[The Drawer and Page Numbers](Navigation-and-Page-Numbers)** — how the left-hand
   navigation orders itself, and the one field that controls it.

## The five rules in one screen

If you read nothing else:

-   **H2 = a navigation entry.** Every H2 on your page appears in the drawer sub-menu and
    gets a numbered badge. Use H3 for anything that should not.
-   **Set a Brand order on every page.** One number in the Brand panel. It puts the page in
    the drawer, prints its `01` label, and numbers its subsections. `0` hides the page.
-   **Build bands with the Section pattern**, not with a plain Group. The pattern is already
    a real `<section>` element; a Group is a `<div>` and means nothing to a screen reader.
-   **Never add a Custom HTML block.** If a layout seems to need one, the answer is a
    pattern or a block that already exists — ask, don't paste markup.
-   **Recolor containers, never text.** Apply *Dark*, *Paper*, *Light* or *Bold Gold* to a
    container and everything inside it recolors to a combination that was already checked for
    contrast. Set a text color by hand and you have opted that text out of the system — it
    will be wrong the moment the container changes. See
    [Color and Guardrails](Color-and-Guardrails).

## Where to ask

Anything that looks like a bug, or a layout the patterns cannot express, is an issue on
the theme repository rather than a workaround in page content. Developer-facing
documentation lives in `README.md` and `docs/architecture.md` in the theme itself.
