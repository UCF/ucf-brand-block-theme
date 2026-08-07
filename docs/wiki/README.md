# `docs/wiki/` — source for the GitHub wiki

End-user (content editor) documentation. These files are the source of truth for the
repository's GitHub wiki; the wiki itself is a separate git repository that mirrors them.

**This is not developer documentation.** Nobody reading these pages is expected to open a
PHP file. Developer-facing material stays in `README.md`, `CONTRIBUTING.md` and
`docs/architecture.md`.

## Files

| File                            | Wiki page                       |
| ------------------------------- | ------------------------------- |
| `Home.md`                       | Landing page                    |
| `Building-in-the-Editor.md`     | Gutenberg basics for this theme |
| `Color-and-Guardrails.md`       | The palette, compositions, accessibility |
| `Patterns.md`                   | The four-rung pattern ladder    |
| `Sections-and-Semantics.md`     | Why bands are `<section>`       |
| `Headings-and-Subsections.md`   | H2 as structure                 |
| `Navigation-and-Page-Numbers.md`| The drawer and Brand order      |
| `_Sidebar.md`                   | Wiki sidebar navigation         |

The filename is the wiki page name, so links between pages are written without the `.md`
extension (`[Patterns](Patterns)`) — that is what resolves on the wiki. It also means
**renaming a file breaks every inbound link**; if you rename one, grep this folder for the
old name.

`_Sidebar.md` is a GitHub wiki convention: it renders as the sidebar on every page and is
never a page of its own.

## Publishing to the wiki

The wiki is the `.wiki.git` sibling of this repository —
`https://github.com/UCF/ucf-brand-block-theme.wiki.git` — and must be initialized once
through the repository's Wiki tab before it can be cloned.

```bash
git clone https://github.com/UCF/ucf-brand-block-theme.wiki.git /tmp/theme-wiki
cp docs/wiki/*.md /tmp/theme-wiki/
cd /tmp/theme-wiki && git add -A && git commit -m "Update editor guide" && git push
```

Do not edit pages in the GitHub web UI — those edits land in the wiki repository only and
are lost the next time this folder is copied over.

## Keeping it honest

These pages describe behavior editors depend on. When a change alters any of the following,
the corresponding page changes in the same PR:

-   the Brand panel or the `ucf_brand_number` meta → `Navigation-and-Page-Numbers.md`
-   H2 anchors, the subsection badge, or the drawer sub-nav → `Headings-and-Subsections.md`
-   the pattern set or the pattern categories → `Patterns.md`
-   registered block styles, or which blocks ship → `Building-in-the-Editor.md`
-   the palette, the `$treatments` / `$compositions` maps, or a new composition →
    `Color-and-Guardrails.md`. **The contrast table on that page is a published claim** —
    recompute it when a treatment's role assignment or a palette value changes.
-   the Section pattern's structure → `Sections-and-Semantics.md`
