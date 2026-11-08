# Wonder Burrow — Ollie child theme

Applies the [native-archive draft](../../src/styles.css) design direction to a
WordPress site running the [Ollie](https://olliewp.com) block theme: Monokai
Pro Light Sun in light mode, Monokai Remastered in dark mode, monospace
metadata, hairline rules, oversized display title, and automatic dark mode via
`prefers-color-scheme`.

## How it works

- **theme.json** remaps Ollie's existing palette slots (`primary`, `main`,
  `base`, `secondary`, `tertiary`, `border-light`, …) to Monokai Pro Light Sun
  tokens, so every Ollie template, pattern, and block style picks up the light
  look without edits. Fonts switch to the system sans/mono stacks the draft uses
  (this also drops the Mona Sans webfont weight).
- **style.css** handles what theme.json can't: dark mode flips the
  `--wp--preset--color--*` custom properties to Monokai Remastered, code blocks
  use the opposite palette of the active UI mode, the radial accent wash sits
  behind the page, and a handful of `dw-*` utility classes (`dw-eyebrow`,
  `dw-mono`, `dw-display`, `dw-pills`, `dw-panel`, `dw-rule-top`,
  `dw-archive-list`) recreate the draft's signature details.
- **patterns/** ships two starting points: **Native Archive Hero** (eyebrow +
  oversized site title + mono nav) and **Native Archive List** (hairline-ruled
  Query Loop with mono dates, pill categories, and all/writing/notes filter
  pills). Find them in the pattern inserter under Featured.
- **Mixed archive.** Core Query Loop only queries one post type, so
  `functions.php` widens any query carrying the `dw-archive-list` class to
  `post` + `note` (the microblog CPT synced from Nostr), interleaved by date.
  Notes render title-less — the note text is the entry, with a mono
  "· note" label on the date line and the date linking to the permalink.
  Caveat: the filter runs on the front end only, so the Site Editor preview
  of that Query Loop shows just posts. If the `note` CPT doesn't exist
  (plugin deactivated), the query falls back to posts only.

- **The rabbit 🐇.** Each archive item gets a source-reveal button
  (`assets/rabbit.js`). It reads provenance from a read-only theme REST
  endpoint (`wonder-burrow/v1/source/<id>`) that assembles the metadata
  written by the Nostr for WP plugin (`_nostr_event_id`, synced-at, the
  site relay list), BurrowSync for Farcaster (`_farcaster_cast_hash`,
  cast URL), and BurrowSync for X (`_x_tweet_id`, tweet URL). The
  plugins need no changes — the theme only reads their post meta. Items
  with no mirrors say so honestly.
- **Light/dark toggle.** `assets/scheme-toggle.js` + the `data-dw-scheme`
  attribute on `<html>`. Defaults to the OS preference; a manual choice
  is remembered in localStorage and applies before first paint.

## Install

1. Zip this directory: `cd wordpress && zip -r wonder-burrow.zip wonder-burrow`
2. In wp-admin: **Appearance → Themes → Add New Theme → Upload Theme**, upload
   the zip, and activate. Ollie (the parent) must stay installed.
3. Nothing about your Nostr sync changes — posts are posts; only the
   presentation layer moves to the child theme.

## After activating

- Global Styles customizations are stored **per theme**, so the palette you
  customized on Ollie itself won't carry over — the child starts from this
  theme.json. Your old look stays intact on the parent theme if you ever switch
  back.
- Template and template-part edits you made in the Site Editor are also
  per-theme. Rebuild the header/homepage with the two bundled patterns, or
  copy your existing layouts across and add the `dw-*` classes where you want
  the draft's details.
- Dark mode follows the visitor's OS setting. Check any images/logos that
  assume a light background.
