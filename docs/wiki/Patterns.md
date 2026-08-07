# Patterns: Units, Groups, Sections, Pages

A **pattern** is a ready-made arrangement of blocks. You insert it, and from that moment it
is ordinary content — edit the text, swap the colors, delete the rows you do not need. It
stays yours; nothing syncs back and nothing updates underneath you.

This theme's patterns are organized as an **atomic ladder**: four categories that go from
the smallest reusable piece to a whole page. They appear in the inserter's **Patterns** tab
under these names.

```
Units      →   Groups        →   Sections          →   Pages
one piece      several pieces    a full-width band     a whole layout
               that read as
               one component
```

**Contents**

-   [Why a ladder](#why-a-ladder)
-   [Units](#units)
-   [Groups](#groups)
-   [Sections](#sections)
-   [Pages](#pages)
-   [How to combine them](#how-to-combine-them)
-   [Editing a pattern after you insert it](#editing-a-pattern-after-you-insert-it)
-   [What patterns deliberately do not carry](#what-patterns-deliberately-do-not-carry)
-   [When no pattern fits](#when-no-pattern-fits)

## Why a ladder

Each rung is built from the rung below it. That is the whole idea:

-   A **Unit** is one primitive — a card, a swatch, a specimen row. It is not a layout.
-   A **Group** clusters units into something that reads as a single component.
-   A **Section** is a full-width band with its own heading, intro and space for groups.
-   A **Page** is a stack of sections.

Because everything is built from the same small set of primitives, a change to the design
system reaches all of it, and two editors building the same kind of content independently
end up with the same markup. It also tells you where to start: pick the largest rung that
matches what you are building, then fill it from the rung below.

## Units

The smallest reusable pieces. Insert one when you need a single component inside content
you have already built.

| Pattern         | What it is                                                                                          |
| --------------- | --------------------------------------------------------------------------------------------------- |
| **Detail Card** | A bordered card with an accent header bar over an open body. Use it for *Do* / *Don't* pairs, callouts, and anything with a labeled header over free content. |
| **List Card**   | A bordered card of numbered rows — a number beside a short title and a description, rows divided by a rule. Use it for steps, checklists and short reference lists. |

Both cards **follow the composition they sit in**. Select the card, change its style
(*Light*, *Paper*, *Dark*, *Bold Gold*) and the header bar, the outline and the rules all
recolor together. There is no separate "dark card" pattern to hunt for — there is one card,
and you set its style.

**Detail Card** ships with an empty body. That is intentional: drop whatever the section
needs into it — paragraphs, a list, an image, even another unit.

**List Card** ships with three starter rows. Add a row by duplicating an existing one; each
row carries its own bottom rule, and the last row in the card should have that rule turned
off (select the row, and clear its bottom border width). Nothing else needs adjusting.

## Groups

Several units arranged into one component.

| Pattern            | What it is                                                                                        |
| ------------------ | ------------------------------------------------------------------------------------------------- |
| **Index**          | A numbered index of a section's contents — a lead-in heading beside a divided list of entries, each with a number, title and short description. |
| **Color Swatches** | The swatch grid, pre-filled with the core brand colors.                                           |
| **Type Specimens** | One row per typeface, showing the face at working size.                                           |
| **Type Scale**     | The full type scale, each row rendered *in* the size it documents.                                |

**Index** is what you put at the top of a long section so a reader can see its shape. Its
entry numbers (`02.01`, `02.02`, …) are typed by you — match them to the section's real
subsection badges so the index and the page agree.

**Color Swatches** and **Type Scale** are the two places where naming colors and sizes
directly is correct, because the color *is* the subject. Do not treat them as precedent for
anything else.

Note that **Index** and **List Card** titles are H3 and H4 on purpose — they are content
inside a section, not sections themselves, and they must stay out of the drawer's sub-menu.
See [Headings and Subsections](Headings-and-Subsections).

## Sections

Full-width content bands. This is the rung you build most pages out of.

| Pattern     | What it is                                                                                                    |
| ----------- | ------------------------------------------------------------------------------------------------------------- |
| **Section** | An H2 with its automatic subsection badge, an intro paragraph, and an empty drop zone underneath for group patterns. |

The Section pattern is a real `<section>` element with the right vertical rhythm already
set. **Use it rather than building a band out of a plain Group** — the reasons, and what
you lose if you don't, are in [Sections and Semantics](Sections-and-Semantics).

To make a Section read as a colored band, select its outer container and give it a
background from the block's color control, or apply one of the Group styles. It bleeds to
the full width of the content column and keeps its inner content aligned with the rest of
the page automatically.

## Pages

Whole-page compositions — a stack of sections you can drop in and fill.

This category exists in the inserter and will hold the standard page layouts as they are
finalized. Until then, build a page by stacking **Section** patterns.

## How to combine them

The normal way to build a page:

1. Fill in the hero — image, title, deck, closing note. (See
   [Building in the Editor](Building-in-the-Editor#the-page-hero).)
2. Insert a **Section** pattern for your first named topic. Retype its H2 and its intro.
3. Inside that Section's drop zone, insert the **Group** patterns the topic needs — an
   Index, a set of swatches, a specimen table.
4. Where a group needs a piece it does not ship with, insert a **Unit** — a Detail Card,
   a List Card — into it.
5. Repeat from step 2 for each remaining topic.

Work with **List View** open. When you insert a pattern while a block is selected, it lands
after that block at that block's level of nesting, which is rarely where you want it inside
a Section. Use the **+** button that appears inside the Section's empty drop zone, or drag
the pattern into place in List View afterward.

## Editing a pattern after you insert it

A pattern is a starting point, not a template you are locked to:

-   **Rewrite every word.** The copy that ships is placeholder.
-   **Delete what you do not need.** Rows, columns, whole cards — select and remove.
-   **Duplicate what you need more of.** Select a row, then **Duplicate** from the toolbar
    options menu (<kbd>Cmd/Ctrl</kbd>+<kbd>Shift</kbd>+<kbd>D</kbd>). Duplicating an existing
    row is always better than building one from scratch, because it keeps the spacing and
    borders that make the pattern hold together.
-   **Change the style, not the colors.** Select the container and switch its composition.

## What patterns deliberately do not carry

Every pattern here is built from ordinary WordPress blocks with their ordinary controls —
there is no hidden styling attached. Spacing, borders and type all come from the blocks'
own sidebar controls, which is why you can take a pattern apart and everything still
behaves.

The one thing patterns avoid on purpose is **fixed color**. None of them names a color for
its body copy or its rules; they read whatever composition encloses them. That is what lets
you drop any pattern into a Dark or Bold Gold group and have it invert correctly instead of
going grey-on-black.

The practical consequence for you: **if you find yourself setting a text color inside a
pattern, stop.** Change the pattern's style instead. A hand-set color looks right today and
is the thing that breaks when the surrounding section is recolored.

## When no pattern fits

Build it from a Section plus core blocks, using the sidebar controls for spacing and
borders. If you find yourself fighting the editor — wanting a Custom HTML block, or pasting
markup — that is the signal that a pattern is missing. File it as a request on the theme
repository rather than working around it in page content; a one-off built by hand is the
thing that drifts out of the brand system first.
