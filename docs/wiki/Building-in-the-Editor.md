# Building in the Editor

The basics of working in the WordPress block editor (Gutenberg) as this theme uses it. If
you have written a page in WordPress before, most of this will be familiar — the parts that
are specific to the brand theme are called out.

**Contents**

-   [Everything is a block](#everything-is-a-block)
-   [Groups: the container you will use most](#groups-the-container-you-will-use-most)
-   [List View is not optional](#list-view-is-not-optional)
-   [The inserter: blocks vs. patterns](#the-inserter-blocks-vs-patterns)
-   [Color: use block styles, not the color picker](#color-use-block-styles-not-the-color-picker)
-   [Text styles](#text-styles)
-   [Width and alignment](#width-and-alignment)
-   [The page hero](#the-page-hero)
-   [Custom blocks in this theme](#custom-blocks-in-this-theme)
-   [The Badge format](#the-badge-format)
-   [Things not to do](#things-not-to-do)

## Everything is a block

A page is a stack of blocks. A paragraph is a block, a heading is a block, an image is a
block, and a container that holds other blocks is also a block. There is no HTML to write
and no theme options screen — what you see in the canvas is what the page will be.

Three ways to add one:

-   Type `/` at the start of an empty paragraph and start typing the block's name.
-   Click the **+** at the top left of the editor to open the inserter.
-   Click the **+** that appears between two blocks to insert at that exact spot.

Select a block and its settings appear in two places: a small **toolbar** floating above it
(alignment, bold, link — the things you change constantly) and the **sidebar** on the right
(colors, spacing, borders, typography — the things you change once). If the sidebar is
hidden, the gear icon in the top right brings it back.

## Groups: the container you will use most

A **Group** block wraps other blocks so they can be moved, spaced and colored as one unit.
Almost every piece of layout in this theme is a Group holding other things.

To make one: select two or more blocks (click the first, shift-click the last), then choose
**Group** from the toolbar's options menu. To get inside one, click its child directly, or
use List View.

Groups are what carry the color treatments described below. When you want "this whole area
on a dark background," you are wrapping it in a Group and giving that Group a style — not
setting a background on each block inside it.

> Groups also come in **Row** and **Stack** flavors, which lay their children out
> horizontally or vertically. Use them for arrangement; use the plain Group for anything
> you want to color or pad as a band.

## List View is not optional

Once a page has nested Groups and Columns, clicking around the canvas gets unreliable — you
select the paragraph when you wanted the Group that holds it.

Open **List View** (the icon at the top left that looks like stacked lines, or
<kbd>Shift</kbd>+<kbd>Alt</kbd>+<kbd>O</kbd>). It shows the page as an outline. Click any
entry to select exactly that block, drag entries to reorder, and expand the arrows to see
what is nested where.

Get in the habit of building with it open. Nearly every "I can't select that" and "why did
it go inside the card" problem is solved by List View.

## The inserter: blocks vs. patterns

The inserter has two tabs that do different jobs:

-   **Blocks** — one thing at a time. A paragraph, an image, a Group.
-   **Patterns** — a pre-built arrangement of many blocks, dropped in ready to edit.

**Reach for a pattern first.** This theme ships patterns for the shapes the brand guide
actually uses, filed under four categories that start with *UCF Brand:*. Once inserted, a
pattern is ordinary blocks — edit the text, swap the images, delete rows you do not need.
It does not stay linked to anything.

See **[Patterns](Patterns)** for what each one is and when to use it.

## Color: use block styles, not the color picker

This is the rule that keeps the guide looking like one document.

The theme registers four **compositions** as Group block styles. Select a Group, open
**Styles** in the sidebar, and pick one:

| Style         | What it is                                       |
| ------------- | ------------------------------------------------ |
| **Light**     | The default light field                          |
| **Paper**     | A soft off-white field                           |
| **Dark**      | Black field, light type                          |
| **Bold Gold** | Gold field                                       |

Each also has an **+ Accent** version (*Paper + Accent*, *Dark + Accent*, …) which adds a
3px gold rule down the leading edge.

A composition sets more than a background. It also sets what has to be true of everything
sitting on it — body copy, links, rules, the small label styles. That is why a card set to
*Light* stays legible when you drop it inside a *Dark* section: the card re-declares the
whole set, so nothing inherits the wrong color.

**What this means for you:** to recolor an area, change the Group's style. Do not set a text
color on the paragraphs inside it. Hand-set text colors survive the change of composition
and win over it, so a paragraph you colored grey stays grey-on-black when the section goes
dark — and it looked fine when you set it.

Two more Group styles exist for specific jobs:

-   **On Dark** — a dark treatment for a band that is not one of the four compositions.
-   **Halftone** — the halftone texture treatment.
-   **Type Specimen** — the frame used by the typography specimen rows.

The color pickers themselves are restricted to the 19 brand colors, with custom colors
turned off, so you cannot pick something off-brand. Use them for things whose subject *is*
a color — a swatch, a deliberate accent fill — not for body copy.

## Text styles

Paragraphs and Headings carry four registered styles, again under **Styles** in the sidebar:

| Style       | Use it for                                                          |
| ----------- | -------------------------------------------------------------------- |
| **Lead**    | The opening sentence or two of a section — larger, sets up what follows |
| **Eyebrow** | A small uppercase label above a heading                              |
| **Meta**    | Small mono type — numbers, codes, captions, credits                  |
| **Muted**   | De-emphasized body copy: same size and face, one step down           |

These take their color from whatever composition encloses them, which is exactly why you
use them instead of picking grey from the palette.

## Width and alignment

Content sits in a wide column by default. Two controls change that:

-   **Alignment** on the block toolbar — *Wide* and *Full width* on Groups and images.
    *Full width* is what makes a section bleed to the edges of the content area.
-   **Reading Width**, a block style available on Group, Columns, Paragraph, Heading and
    List. It pulls the block in to a narrower measure for long-form reading. Use it on
    stretches of body copy; leave layout blocks at the default.

## The page hero

Every page opens with a full-bleed hero: the featured image behind a scrim, an eyebrow line,
the page title, an accent rule, and two lines of copy.

You edit all of it **directly in the canvas** — there is no separate hero panel:

-   **The image** is the page's Featured Image. Click it in the hero to replace it.
-   **The title** is the page title. Click it and type.
-   **The deck** is the first paragraph under the rule. Click it and write.
-   **The closing note** is the second paragraph. Same.

Two things are deliberately not editable. The **eyebrow** — `Brand Guidelines · Section 05`
— is generated from the page's Brand order, so it can never disagree with the drawer (see
[The Drawer and Page Numbers](Navigation-and-Page-Numbers)). And the accent rule is fixed.
You also cannot add, remove or reorder anything in the hero; it is the same shape on every
page on purpose.

If a page has no featured image, the hero renders on a black field. That is a fallback, not
a look — set an image.

> **Why the hero appears above your content but isn't in it:** pages open with **Show
> template** on, so you can see the whole page while you write. Everything outside the hero
> and the content area is dimmed and inert. That is expected. If your page suddenly starts
> at the title with no hero, someone has switched "Show template" off in the editor
> preferences — it is a per-user setting.

## Custom blocks in this theme

Beyond core WordPress blocks, the theme adds a few of its own. Find them in the inserter by
name:

| Block              | What it does                                                                     |
| ------------------ | -------------------------------------------------------------------------------- |
| **Color Swatches** | A grid of swatches. Only accepts Color Swatch children.                          |
| **Color Swatch**   | One color: the chip, its name, HEX/RGB/CMYK/Pantone, a usage note, and its measured contrast |
| **Tabs**           | A tab set. Add **Tab** children; each has a label and a panel you fill freely.   |

A Color Swatch takes its chip color from a palette name, not a pasted hex, so if a brand
color is ever adjusted the swatch follows.

Tabs become a stack of headings and panels on narrow screens — that is by design, not a
bug. Do not put anything in a tab panel that only makes sense next to another tab.

## The Badge format

Select a run of text in any paragraph, heading or list item and click **Badge** on the
rich-text toolbar (under the ˅ if the toolbar is crowded). It wraps that text in a small
uppercase chip and opens a swatch popover so you can choose the tone. Tones are mutually
exclusive — picking a second replaces the first.

Use it for short status-like labels inside running text. It is not a heading and does not
appear in navigation.

## Things not to do

-   **Do not add a Custom HTML block.** No page in this guide contains one, and that is a
    standard, not an accident. If content needs structure the blocks do not offer, that is a
    request for a new pattern or block — file it rather than pasting markup.
-   **Do not set text or background colors to fake a treatment.** Use the Group styles.
-   **Do not use a heading level to get a size.** Heading levels are structure here — see
    [Headings and Subsections](Headings-and-Subsections). If you want smaller type on a real
    H2, change its size in the sidebar and leave the level alone.
-   **Do not build a band out of a bare Group.** Use the Section pattern; see
    [Sections and Semantics](Sections-and-Semantics).
-   **Do not paste from Word with formatting.** Paste as plain text
    (<kbd>Cmd/Ctrl</kbd>+<kbd>Shift</kbd>+<kbd>V</kbd>) and apply the theme's styles. Pasted
    inline colors and fonts are exactly the hard-coded values the system is built to avoid.

## Saving and previewing

**Save draft** keeps work private; **Preview** opens the page as visitors will see it;
**Publish** / **Update** makes it live. A page must be **published** to appear in the
drawer — a draft with a Brand order set will not show up there until it is.
