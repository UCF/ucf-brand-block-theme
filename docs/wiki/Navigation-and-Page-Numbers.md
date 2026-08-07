# The Drawer and Page Numbers

The dark panel down the left side of every page is the **drawer** — the site's entire
navigation. You do not build it. It builds itself from your pages, in the order you give
them, and the number you set on a page is the single control for all of it.

**Contents**

-   [What the drawer is made of](#what-the-drawer-is-made-of)
-   [Brand order: the one field](#brand-order-the-one-field)
-   [What one number controls](#what-one-number-controls)
-   [How pages qualify for the drawer](#how-pages-qualify-for-the-drawer)
-   [Numbering in practice](#numbering-in-practice)
-   [Adding, reordering and removing a page](#adding-reordering-and-removing-a-page)
-   [The sub-menu](#the-sub-menu)
-   [Behavior: sticky, mobile, search](#behavior-sticky-mobile-search)
-   [Troubleshooting](#troubleshooting)

## What the drawer is made of

Top to bottom:

1. **The site title and tagline.**
2. **A search box**, scoped to the guide.
3. **The section menu** — one entry per numbered page, each showing its number and title.
   This is the part that generates itself.
4. **A footer block** with the owning unit and the version.

When you are *on* one of those pages, its entry expands to list that page's H2s. That
sub-list also generates itself, from your headings — see
[Headings and Subsections](Headings-and-Subsections).

**There is no menu to edit.** The menu is not a WordPress menu, and there is no Appearance →
Menus step. If a page is not in the drawer, the fix is on the page, not in a menu screen.

## Brand order: the one field

Open a page in the editor. In the right-hand sidebar, on the **Page** tab, find the panel
titled **Brand**. It holds one field:

> **Brand order** — *Orders this page in the drawer and prints as its label (1 → 01). Leave
> 0 to hide it from the drawer.*

Type a whole number. Save. That is the entire configuration.

If you do not see the Brand panel: make sure you are on the **Page** tab and not the
**Block** tab, and that you have a page selected rather than a block. The panel only appears
on pages.

## What one number controls

The same value drives four separate things, which is exactly why there is only one field —
they cannot fall out of step with each other.

| It sets | Example |
| --- | --- |
| **The page's position in the drawer** | `3` puts it third |
| **The page's label in the drawer** | `03` |
| **The hero eyebrow line** | `Brand Guidelines · Section 03` |
| **The first half of every subsection badge on the page** | `03.01`, `03.02`, … |

So a page with no Brand order does not just go missing from the menu — it also loses its
eyebrow line and all of its subsection badges. If a page looks like it has lost its
numbering, this field is almost always the reason.

## How pages qualify for the drawer

A page appears in the section menu when **all** of the following are true:

-   It is a **Page** (not a post).
-   It is **published**. Drafts and pending pages do not appear, even with a number set.
-   It is **top level** — it has no parent page. Child pages never appear in the drawer.
-   Its **Brand order is 1 or higher**. `0` — the default — hides it.
-   It is **not the site's front page**. The home page is reached from the site title.

Fail any one of those and the page is simply absent, with no warning.

## Numbering in practice

-   **Numbers are positions, not labels you invent.** `1` sorts first and prints as `01`.
-   **Leave gaps.** Number your sections 10, 20, 30, 40 rather than 1, 2, 3, 4 and inserting
    a new section between two existing ones costs one edit instead of renumbering everything
    below it. The printed labels will then read `10`, `20`, `30` — decide up front whether you
    want gapless printed labels or cheap insertion; you cannot have both.
-   **Duplicates are allowed but ambiguous.** Two pages sharing a number sort alphabetically
    by title and both print the same label. Avoid it.
-   **Numbers above 99** print in full (`100`), which will look out of place against
    two-digit neighbors.

## Adding, reordering and removing a page

**To add a section:**

1. Create a new **Page**. Leave its parent unset.
2. Set its **Brand order**.
3. Fill in the hero — featured image, title, deck, closing note.
4. Write the body using the **Section** pattern for each named topic.
5. **Publish.** It appears in the drawer immediately, on every page of the site.

**To reorder:** change the Brand order on the pages that moved and update each one. Nothing
else needs touching. Remember that the printed labels move with the positions — a page that
was `04` and is now `02` will show `02` everywhere, including its subsection badges and any
Index pattern that references them by hand.

**To remove a page from the drawer without deleting it:** set its Brand order to `0`. The
page stays published and reachable by direct link, but leaves the menu, loses its eyebrow
number and loses its subsection badges.

## The sub-menu

The nested list under the current page's drawer entry is derived from that page's H2s each
time the page loads. You never author it, and there is nothing to enable.

-   Every **H2** in the content becomes an entry.
-   **H3 and below do not.**
-   The entry for whichever H2 is currently in view is highlighted as the reader scrolls.
-   Each entry links to that heading's anchor, so it is a shareable address.

If the sub-menu reads wrong, the headings are wrong. Full detail in
[Headings and Subsections](Headings-and-Subsections).

## Behavior: sticky, mobile, search

-   **It follows you down the page** and stops when the footer begins, so the footer is never
    covered.
-   **On narrow screens the drawer is hidden** behind a menu button in a bar at the top of
    the page. Tapping it slides the drawer in over the content; tapping the dimmed area or
    the × closes it.
-   **The search box searches the guide**, and results resolve to subsections: each result
    lists up to three matching H2s from that page, linked straight to them. Nothing needs
    indexing — edit a heading and search reflects it as soon as you update the page.

## Troubleshooting

**My page is not in the drawer.**
Run the [qualification list](#how-pages-qualify-for-the-drawer) — in practice it is nearly
always one of: still a draft, Brand order left at `0`, or the page has a parent set.

**My page is in the wrong place.**
Two pages share a number, so they are sorting alphabetically. Give them distinct numbers.

**The subsection badges are missing.**
The page has no Brand order. Set one.

**The hero eyebrow says nothing / just the guide name.**
Same cause. The eyebrow is generated from the Brand order.

**The drawer sub-menu is empty on a page with plenty of headings.**
Those headings are H3 or lower. Only H2s produce sub-menu entries.

**The sub-menu lists card titles and other things that are not sections.**
Those are H2s that should be H3 or H4. Change the level and use the sidebar's font size
control if you needed them to look bigger.

**I changed a heading and someone's bookmark broke.**
Expected — the anchor comes from the heading text. Set the old value as the heading's
**HTML anchor** under Advanced to restore it.

**I renumbered pages and an Index pattern is now wrong.**
Index entry numbers are typed by hand. Update them to match the badges on the page.

## Related

-   [Headings and Subsections](Headings-and-Subsections) — what fills the sub-menu
-   [Building in the Editor](Building-in-the-Editor#the-page-hero) — the hero the number
    also feeds
-   [Sections and Semantics](Sections-and-Semantics) — the bands those headings live in
