# Headings and Subsections

In this theme a heading level is **structure, not size**. An H2 is a navigable subsection of
the page; H3 and below are content inside one. Choosing the wrong level does not just look
different — it changes the navigation, the numbering and the search results.

**Contents**

-   [The one rule](#the-one-rule)
-   [What an H2 does](#what-an-h2-does)
-   [The subsection badge](#the-subsection-badge)
-   [Anchor links](#anchor-links)
-   [Choosing a level](#choosing-a-level)
-   [Getting the size you want without changing the level](#getting-the-size-you-want-without-changing-the-level)
-   [Headings and search](#headings-and-search)
-   [Duplicate headings](#duplicate-headings)
-   [Checklist](#checklist)

## The one rule

> **An H2 is a drawer entry. Use H3 for anything that should not appear in the drawer.**

Everything on this page follows from that.

## What an H2 does

Write an H2 in page content and four things happen, none of which you configure:

1. **It joins the drawer sub-menu.** When a reader is on your page, the drawer expands the
   current page's entry and lists every H2 beneath it. That list is never authored — it is
   read from your headings each time the page loads.
2. **It highlights as the reader scrolls.** The sub-menu entry for whichever H2 is currently
   in view is marked as current, so the drawer tracks the reader's position.
3. **It gets a numbered badge** — see below.
4. **It becomes a link target**, both for the drawer and for anyone who wants to share a
   link to that exact part of the page.

The page title is the H1 and is generated for you. **Never write an H1 in page content** —
there should be exactly one per page, and the title already is it.

## The subsection badge

Each H2 renders with a small gold-on-black chip beside it:

```
┌───────┐
│ 03.02 │  Photography
└───────┘
```

The first half is the **page's Brand order** (see
[The Drawer and Page Numbers](Navigation-and-Page-Numbers)). The second half is the H2's
position on the page, counted automatically from the top: the first H2 is `.01`, the second
`.02`, and so on.

Three consequences worth knowing:

-   **You never type these numbers.** Insert a new H2 in the middle of a page and everything
    below it renumbers itself.
-   **A page with no Brand order shows no badges at all.** The chip needs the page number to
    exist. If your badges are missing, the Brand order is unset — that is the fix.
-   **The numbers you type into an Index pattern are not automatic.** If a section opens with
    an Index listing `03.01`, `03.02`, …, those are hand-typed. Reorder the page's H2s and you
    have to update the Index to match.

The badge sits beside the heading on desktop and stacks above it on narrow screens.

## Anchor links

Every H2 gets a link target derived from its text, and the theme generates it on the server —
so the link exists in the page as delivered, and works whether or not JavaScript runs.

Hover an H2 on the front end and a link glyph appears. That is the shareable address of that
subsection: `/brand-guidelines/photography/#usage-rights`.

Two practical notes:

-   **Renaming a heading changes its link.** Anyone who bookmarked or shared the old address
    will land at the top of the page instead of the subsection. Renaming is fine — just know
    that is the cost, and mention it if a heading is widely linked.
-   **You can set the link yourself.** Select the heading, sidebar → **Advanced** →
    **HTML anchor**, and type a value. An anchor you set always wins over the generated one.
    Use this to keep an old address working after a rename: rename the heading, then set its
    anchor to the previous slug.

## Choosing a level

| Level  | Use it for                                                                             | Appears in the drawer? |
| ------ | -------------------------------------------------------------------------------------- | ---------------------- |
| **H1** | Nothing. The page title is the H1 and is generated.                                    | —                      |
| **H2** | A named subsection of the page — something a reader would want to jump straight to.    | **Yes**                |
| **H3** | A topic inside a subsection.                                                           | No                     |
| **H4** | A topic inside an H3. Card and index titles.                                           | No                     |

The test for H2: *would someone want a link straight to this?* If yes, H2. If it only makes
sense in the flow of the subsection above it, H3.

**Do not skip levels.** An H2 followed by an H4 leaves a hole in the outline that screen
readers report as a missing level. Go H2 → H3 → H4 in order.

The patterns already follow this: List Card row titles are H3, Index entry titles are H4,
and accordion headings are H3 — all specifically so they stay out of the drawer sub-menu and
the badge numbering. If you change one of those levels to H2 you will get a drawer full of
card titles.

## Getting the size you want without changing the level

This is the most common reason people reach for the wrong level, so it has its own answer:

**Select the heading, open the sidebar, and change its font size under Typography.** The
level and the size are separate controls. A real H2 rendered at a smaller size is correct;
an H3 standing in for an H2 is not.

The same applies in reverse — if an H3 needs to feel more prominent, make it bigger. Do not
promote it.

## Headings and search

Site search resolves to a *subsection*, not just a page. A result lists up to three matching
H2s underneath it, each linked to its own anchor, with the query highlighted in a snippet of
that section's text.

That means **your H2 wording is search result wording**. Write headings that name their
subject plainly — "Photography Usage Rights" finds and reads better than "A Few Notes."
Content that sits above the page's first H2 is not part of any subsection and will not be
matched into one, which is another reason to open a page's body with a Section.

Nothing is indexed and there is no reindex step — sections are read from the page as it is
now, so a heading edit is live in search as soon as you update the page.

## Duplicate headings

Two H2s with the same text on one page are allowed, and the second one's link target gets a
`-2` appended automatically. It works, but the drawer sub-menu will show the same label
twice with no way to tell them apart. Prefer distinct headings.

## Checklist

Before publishing a page:

-   [ ] No H1 in the content — the title is the only one.
-   [ ] Every H2 is something a reader would want to link to directly.
-   [ ] Nothing that is *not* a subsection is an H2 (card titles, callout headers, index rows).
-   [ ] No skipped levels: H2 → H3 → H4.
-   [ ] The subsection badges are showing. If they are not, set the page's Brand order.
-   [ ] Any hand-typed numbers in an Index pattern match the badges on the page.
-   [ ] Open the page and check the drawer — the sub-menu should read like a table of
      contents for what you just wrote. If it does not, the headings are wrong, not the drawer.

## Related

-   [Sections and Semantics](Sections-and-Semantics) — the `<section>` that should wrap each H2
-   [The Drawer and Page Numbers](Navigation-and-Page-Numbers) — where the badge's first
    number comes from
