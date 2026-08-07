# Color, Guardrails and Accessibility

The color system in this theme is built so that **the accessible choice is the only choice
you are offered.** You do not check contrast, you do not look up a hex, and you do not
decide what grey a caption should be. You pick a *field* — Light, Paper, Dark, Bold Gold —
and everything that sits on it is already decided and already verified.

This page explains what those guardrails are, why they exist, and the two places where they
stop and your judgment starts.

**Contents**

-   [The short version](#the-short-version)
-   [Guardrail 1: the palette is closed](#guardrail-1-the-palette-is-closed)
-   [Guardrail 2: you pick a field, not a color](#guardrail-2-you-pick-a-field-not-a-color)
-   [Guardrail 3: the pairings were solved once](#guardrail-3-the-pairings-were-solved-once)
-   [Why nesting works](#why-nesting-works)
-   [The Bold Gold field, and what it teaches](#the-bold-gold-field-and-what-it-teaches)
-   [Where the guardrails stop](#where-the-guardrails-stop)
-   [Scalability: why this survives a rebrand](#scalability-why-this-survives-a-rebrand)
-   [The palette](#the-palette)
-   [Practical rules](#practical-rules)

## The short version

> **Recolor containers. Never recolor text.**

Select a Group, choose *Dark* from **Styles**, and the body copy, the lead, the eyebrow, the
captions, the links, the rules and the accent all change together to the combination that
was tested for that background. Select a paragraph and set its color by hand and you have
opted that paragraph out of the system permanently — including out of every future change to
it.

## Guardrail 1: the palette is closed

The theme ships 19 brand colors, and it turns off everything else WordPress would normally
offer:

-   **WordPress's default palette is off.** No "Vivid Cyan Blue," no "Luminous Vivid Amber."
-   **Custom colors are off.** There is no hex field and no eyedropper anywhere in the editor.
-   **Default gradients and duotones are off.** One gradient exists — the hero scrim.

So the first guardrail is simply that **you cannot introduce a color that is not UCF's.**
An editor working fast, pasting from a deck, or matching a photo cannot put a fifth blue
into the guide, because there is nowhere to type one.

This is the shallow win, though. Being on-brand and being legible are different problems,
and the palette alone solves only the first.

## Guardrail 2: you pick a field, not a color

The real mechanism is that color is expressed as **roles**, not as values.

Four **compositions** are available as Group block styles:

| Composition   | The field it paints    |
| ------------- | ---------------------- |
| **Light**     | White                  |
| **Paper**     | Soft off-white         |
| **Dark**      | Black                  |
| **Bold Gold** | Gold                   |

Each one declares a full set of roles for what sits on that field:

```
accent      the highlight color for this field
on-accent   the text that stays legible when that accent is used as a background
body        body copy
body-muted  de-emphasized body copy
lead        the opening, larger copy
eyebrow     the small uppercase label
meta        small mono type — numbers, captions, credits
line        hairlines and rules
link        links
```

Every style, pattern and block in the theme reads those **roles**. Nothing reads a color.
The `Muted` text style does not mean "grey" — it means "whatever *body-muted* is on the
field I am currently sitting on." That is why applying `Muted` is safe and picking grey is
not.

The rule that makes this hold: **anything that sets a background must also say how text
sits on it.** A background with no declared text treatment inherits the enclosing one, which
is how a light card ends up carrying a dark section's grey copy. In this theme that
combination cannot be produced by an editor, because the background and the treatment are
the same choice.

## Guardrail 3: the pairings were solved once

Accessibility is not a review step here. It is baked into the table that defines those
roles, and every text pairing in it clears **WCAG AA (4.5:1)** for normal-size text:

| Field                 | body  | muted | meta  | link  | eyebrow |
| --------------------- | ----- | ----- | ----- | ----- | ------- |
| **Light** (white)     | 17.4  | 7.3   | 7.3   | 5.9   | 5.9     |
| **Paper** (off-white) | 15.7  | 6.6   | 6.6   | 5.3   | 5.3     |
| **Dark** (black)      | 21.0  | 10.0  | 6.1   | 10.9  | 11.5    |
| **Bold Gold**         | 11.5  | 9.5   | 9.5   | 11.5  | 11.5    |

Nothing in that table is close to the line, and none of it is your responsibility. You get
it by choosing a composition.

Two of the values in it exist because the obvious choice failed and was rejected:

-   On the **Dark** field, body copy is **white** rather than the secondary grey — the grey
    technically passed but read as washed out against black, so the role was reassigned.
    `meta` keeps its grey (6.1:1) so the hierarchy survives.
-   On the **Bold Gold** field, `meta` and `body-muted` **give up grey entirely.** See below.

## Why nesting works

Compositions are implemented as inherited custom properties rather than as descendant
rules, and that distinction is what makes them composable.

A rule like `.is-style-dark p { color: #B3B3B3 }` keeps matching a paragraph even when that
paragraph sits inside a *Light* card nested in the dark section — inheritance never beats a
matching selector. That produced grey-on-white in a shipped page.

Because each composition instead *re-declares all nine roles*, a Light card inside a Dark
section resets every one of them at the card boundary. Nesting resolves correctly in either
direction, to any depth, regardless of source order.

**What this means for you:** you can drop any pattern into any composition and it will
recolor correctly. You never need a "dark version" of a card — there is one card, and it
takes the field it lands in.

## The Bold Gold field, and what it teaches

Gold is a light, saturated field, and it is the constrained one. Nothing lighter than the
body text color clears 4.5:1 on it — the standard secondary grey lands at **4.01:1** and the
standard link blue at **3.20:1**. Both fail.

The system's answer is not to ship a slightly-darker grey and hope, and not to let `Muted`
quietly fail on one field. It is that on gold, **de-emphasis stops being a lightness
problem and becomes a typographic one**: `meta` and `body-muted` take the full-strength text
color and lean on the mono family and the smaller size to sit back instead.

That is the design principle worth taking away from this whole page: when a field cannot
support a treatment, the treatment changes its *method* rather than lowering its contrast.
Apply the same thinking to anything you build — if text is hard to read on your background,
the fix is the background or the type, never a lighter grey.

The gold field also forces the accent to the deeper gold, because a gold rule on a gold
field is invisible.

## Where the guardrails stop

Two honest limits. Both are conventions the tooling cannot enforce for you.

**1. The palette prevents off-brand, not low-contrast.**
The block color controls still let you put any palette color on any other. Gold text on a
white background is **1.83:1** and is entirely pickable. The system stops you from being
*off-brand*; it does not stop you from being *unreadable*. That is precisely why the rule is
"use the block styles, not the color picker" — the styles are the tested paths through the
palette, and the picker is the raw material.

Use the color controls for things whose subject *is* a color: a swatch, a deliberate accent
fill on a card header. Not for body copy, and not to fake a treatment.

**2. Hairlines are deliberately faint.**
Rules and borders sit around 1.3:1 against their field. That is correct — they are
decorative separators, not meaningful graphics — but it has one consequence: **a hairline
can never be the only thing that distinguishes two things.** If a state, a selection or a
category matters, it needs a label, a weight change or a badge as well as a rule.

Beyond color, the two accessibility decisions that stay yours are the [heading
outline](Headings-and-Subsections) and [section
semantics](Sections-and-Semantics). Color is handled; structure is not.

## Scalability: why this survives a rebrand

The same indirection that produces accessibility produces maintainability — they are one
mechanism read from two directions.

Because no pattern, block or stylesheet names a color:

-   **Adjusting a brand color** is one edit in the token file. Every swatch, rule, badge and
    caption using it follows, including in the editor. Nothing has to be found and replaced,
    because there are no copies.
-   **Adding a fifth composition** is one row in each of three places — and every pattern
    that already exists works inside it immediately, on the day it is added, because none of
    them ever asked what color it was.
-   **Swapping the typefaces** works the same way, for the same reason: the font families are
    named `display`, `body` and `mono` rather than by typeface, so replacing the fonts is a
    token change and no pattern, template or stylesheet is touched.

The cost of that is the discipline this page describes. Every hand-set color in page content
is a copy that will not follow — a paragraph you colored grey in 2026 stays that exact grey
through every future change, and it is invisible until the day the design moves and it is
the only thing that did not.

## The palette

The 19 tokens, grouped by what they are for. You will use almost none of them directly.

| Group | Tokens |
| --- | --- |
| **Brand** | Bold Gold, Bold Gold (Deep), UCF Black, White |
| **Fields** | Ink, Paper Alt, Card (Dark) |
| **Accent blues** | Horizon Blue, Horizon Blue (Tint) |
| **Links** | Link Blue, Link Blue (Hover) |
| **Text** | Body Text, Secondary Text, Secondary Text (On Dark), Meta Text (On Dark) |
| **Rules** | Hairline, Hairline (On Dark) |
| **Status** | Success, Danger |

Note that the text, rule and status tokens exist so the *compositions* can point at them.
Reaching past a composition to apply "Secondary Text" by hand is the exact move this page
asks you not to make — you get the same result today and none of the safety.

Status colors are the one intentional exception to composition-following: **Success** and
**Danger** must stay recognizable and must *not* invert with the field, because a green that
turns red-ish on a dark background is worse than a green that is slightly off.

## Practical rules

-   **To recolor an area:** select its container, change its **Style**. Not its text color.
-   **For de-emphasized copy:** use the **Muted** text style, not a grey.
-   **For a caption, credit or number:** use the **Meta** style, not a small grey paragraph.
-   **For a label above a heading:** use the **Eyebrow** style.
-   **Use the color controls only where the color is the point** — swatches, an accent fill.
-   **Never paste colored text from another document.** Paste as plain text
    (<kbd>Cmd/Ctrl</kbd>+<kbd>Shift</kbd>+<kbd>V</kbd>) and apply a style. Pasted inline color
    is the one way off-palette values get into the guide.
-   **If something is hard to read, change the field or the type — never the grey.**

## Related

-   [Building in the Editor](Building-in-the-Editor#color-use-block-styles-not-the-color-picker) — where these styles live in the UI
-   [Patterns](Patterns#what-patterns-deliberately-do-not-carry) — why no pattern names a color
-   [Sections and Semantics](Sections-and-Semantics) — the other half of accessibility
-   [Headings and Subsections](Headings-and-Subsections) — the heading outline
