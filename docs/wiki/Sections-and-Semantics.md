# Sections and Semantics

Short version: **build every content band with the Section pattern.** It is already a real
`<section>` element, and a plain Group is not.

**Contents**

-   [What "semantics" means here](#what-semantics-means-here)
-   [Div vs. section](#div-vs-section)
-   [The Section pattern already does this](#the-section-pattern-already-does-this)
-   [What you get for free](#what-you-get-for-free)
-   [Nesting: which containers should be sections](#nesting-which-containers-should-be-sections)
-   [If you already built a band from a plain Group](#if-you-already-built-a-band-from-a-plain-group)
-   [Common mistakes](#common-mistakes)

## What "semantics" means here

Every block on the page becomes an HTML element. Most of the time the element does not
matter to you — a paragraph is a paragraph. But some elements carry *meaning* rather than
just appearance, and the meaning is what assistive technology, search engines and browser
reader modes actually read.

A screen reader does not see your gold band or your padding. It sees a document outline:
regions, headings, lists, links. If your page is a stack of anonymous boxes, the outline is
flat and a user navigating by structure — which is how most screen-reader users navigate a
long document — has nothing to move between except headings.

This matters more for a brand guide than for most sites. It is a long reference document
that people skim, deep-link into and return to. The outline *is* the interface.

There is also a compliance dimension. UCF is a public institution, so its web content is
expected to meet WCAG 2.1 AA. Landmark regions and a correct heading outline are part of
that, and they are the part that is trivially easy to get right while you are authoring —
and expensive to retrofit across a hundred pages later.

## Div vs. section

A **Group** block renders as a `<div>` by default. A `<div>` is a generic box: it groups
things for styling and communicates nothing at all.

A **`<section>`** says: *this is a distinct, self-contained part of the document, and it has
a heading that names it.* Combined with the H2 inside it, it becomes a navigable region
with a name.

```html
<!-- A plain Group: styled, but meaningless. -->
<div class="wp-block-group">
	<h2>Photography</h2>
	…
</div>

<!-- The Section pattern: the same look, an actual region of the document. -->
<section class="wp-block-group brand-section">
	<h2>Photography</h2>
	…
</section>
```

The visual result is identical. The difference is entirely in what the page *means*, which
is why it is easy to skip and why skipping it is invisible until someone audits the site or
tries to use it with a screen reader.

The rule of thumb: **if the box has a heading that names it, it should be a `<section>`.
If the box exists only to hold things together for spacing or color, leave it a `<div>`.**
A section without a heading is worse than a div, not better — it announces a region with no
name.

## The Section pattern already does this

You do not have to think about tag names, and you should not have to configure anything.
Insert the **Section** pattern (inserter → Patterns → *UCF Brand: Sections* → **Section**)
and you get:

-   an outer container that is already a `<section>`, already full width, and already
    carrying the band's vertical rhythm;
-   an **H2**, which names the region and drives everything described in
    [Headings and Subsections](Headings-and-Subsections);
-   an intro paragraph; and
-   an empty drop zone underneath for the group patterns that make up the section's content.

That is the whole reason the pattern exists. The `section` tag is baked into it so that
authoring correctly is the path of least resistance rather than a checklist item.

> **If you must build one by hand:** select the Group, open the sidebar, expand **Advanced**,
> and set **HTML element** to `section`. This is the same control the pattern has already
> set for you. Prefer the pattern — it also brings the spacing, the full-width behavior and
> the inset that keeps the section's content aligned with the rest of the page.

## What you get for free

Using the Section pattern is not only about correctness. Each of these follows from it:

-   **The band bleeds correctly.** It runs the full width of the content column while its
    contents stay aligned with everything above and below. Give the container a background
    from the block's color control (or one of the Group styles) and it reads as a band.
-   **Bands stack flush.** The section supplies its own vertical space, and consecutive
    sections butt up against each other with no strip of page background showing between
    them. Two hand-built Groups will show that strip.
-   **The H2 gets its numbered badge.** `01.01`, `01.02`, and so on — see
    [Headings and Subsections](Headings-and-Subsections).
-   **The section appears in the drawer sub-menu** and gets a shareable anchor link.
-   **The screen-reader outline is right** without you doing anything else.

## Nesting: which containers should be sections

Not every Group should be a section — over-applying it is its own problem, because a page of
twenty nested regions is as unnavigable as a page of none.

| Container                                              | Element     |
| ------------------------------------------------------ | ----------- |
| A top-level band with an H2 naming it                  | `section`   |
| A card, a callout, a column, a row of swatches         | `div` (default) |
| A Group that exists purely to apply a color treatment  | `div` (default) |
| A genuinely self-contained sub-topic with its own H3 heading, inside a section | `section`, if it truly stands alone — otherwise leave it a `div` |

When in doubt, leave it a `div`. The top-level bands are the ones that matter.

Two elements are handled for you and need nothing from you: the drawer is already a
`<nav>`, and the page's main content area is already inside `<main>`.

## If you already built a band from a plain Group

You do not need to rebuild it:

1. Select the outer Group (List View is the reliable way).
2. Sidebar → **Advanced** → **HTML element** → `section`.
3. Confirm it has an **H2** directly inside it. A region with no heading is not an
   improvement.
4. Check the spacing against a neighboring Section pattern — a hand-built Group will not have
   the band's padding, and you may see a gap where two bands meet.

If the band is not close to the pattern's shape, it is usually faster to insert a fresh
**Section** and move your content into its drop zone.

## Common mistakes

-   **A section with no heading.** Announces an unnamed region. Either add the H2 or make it
    a plain Group.
-   **Using H3 for a section's own heading.** The band is a section; its heading is an H2.
    Dropping it to H3 to get smaller type breaks both the outline and the drawer — change the
    font size in the sidebar instead.
-   **Wrapping every card in a section.** Cards are content inside a section, not sections.
-   **Nesting a Section pattern inside another Section pattern.** Sections stack; they do not
    contain each other. Put group patterns in the drop zone.
-   **Using a section for pure spacing.** If it has no name, it has no business being a
    region.

## Related

-   [Headings and Subsections](Headings-and-Subsections) — the H2 inside the section, and
    what it drives
-   [Patterns](Patterns) — where the Section pattern sits in the ladder
-   [Building in the Editor](Building-in-the-Editor) — Groups, styles and List View
