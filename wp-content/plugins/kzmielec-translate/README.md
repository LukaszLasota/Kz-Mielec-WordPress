# Kzmielec Translate

Migration tool: fills the English, Ukrainian and Spanish versions of the site's
content from the Polish original, using the DeepL API.

**This plugin is meant to be deleted.** It translates the content once and then
has nothing left to do. Nothing the site needs at runtime lives here — the
safety net that keeps translated content out of the sitemaps when Polylang is
inactive sits in the theme (`Kzmielec\Core\TranslationGuard`), precisely so that
this plugin can be switched off without consequence.

## Plugin Info

- **Version:** 1.0.0
- **Requires PHP:** 8.0+
- **Requires WordPress:** 6.4+
- **Depends on:** Polylang (to link translations), Yoast SEO (to carry SEO fields)
- **License:** GPL-2.0-or-later

## Usage

```bash
wp kzmielec-translate run --lang=en                    # report only
wp kzmielec-translate run --lang=en --stub --execute   # write, no API key needed
wp kzmielec-translate run --lang=uk --execute          # write, via DeepL
```

| option | meaning |
|---|---|
| `--lang=<slug>` | target language: `en`, `uk` or `es` (required) |
| `--post-type=<list>` | comma-separated; default `page,meetings,comparison_topic` |
| `--execute` | write to the database — without it the command only reports |
| `--force` | overwrite translations that already exist; requires `--execute` |
| `--stub` | use the deterministic stand-in instead of DeepL, for testing without a key |

**Reporting is the default and writing needs `--execute`, deliberately:** a
mistyped command should cost nothing, and 58 posts across three languages is not
something to create by accident.

The DeepL API key is entered on its own settings screen and stored as an option.
It is **never committed** — on production it is added to `wp-config.php` by hand.

## Architecture

```
app/
├── Cli/TranslateCommand.php     # the one entry point
├── Admin/DeeplSettings.php      # API key field and a usage readout
├── Services/
│   ├── TranslatorInterface.php  # contract shared by the real translator and the stub
│   ├── DeeplClient.php          # REST client
│   ├── StubTranslator.php       # deterministic stand-in: marks text instead of translating
│   ├── BlockSafeText.php        # translates text inside block markup, never the markup
│   ├── Glossary.php             # versioned term list, pushed to DeepL
│   ├── LinkRemapper.php         # points internal links at the same language
│   └── SegmentStore.php         # keeps source and translation side by side for review
└── Translators/
    ├── PostTranslator.php       # title, content, slug, and the language link itself
    ├── TermTranslator.php       # taxonomy terms, linked across languages
    ├── ChurchesTranslator.php   # the serialised `churches` array in post meta
    ├── PageMetaTranslator.php   # the two page fields that decide how a page renders
    ├── MeetingMetaTranslator.php
    └── YoastTranslator.php      # SEO title and meta description, nothing else
```

### Why the pieces are separate

**`BlockSafeText` walks the parsed block tree** instead of translating the post
content as one string. Sending block markup to a translation API returns markup
the API has rearranged: attributes reordered, comment delimiters "corrected",
self-closing tags expanded. Translating only the text nodes leaves the markup
byte-identical, so a translated post opens in the editor as blocks rather than as
one broken HTML lump.

**`Glossary` is versioned in the repository** — 30 terms per language pair, in
`glossary/pl-{en,uk,es}.csv` — and uploaded to DeepL before a run. Without it,
the same term drifts between posts: the church's own name, the names of the
denominations in the comparison table, and the words for the offices and
services. A reader who meets two names for one thing stops trusting the page.

**`TermTranslator` was the piece the original design forgot.** Translating posts
without their taxonomy terms produces translated content filed under Polish
categories, which is invisible until the accordion on the comparison page renders
nine Polish headings above English rows.

**`SegmentStore` exists because machine translation has to be reviewable.** It
stores every segment against its source, so a later pass can find and correct one
sentence without re-translating a page. Two known corrections were driven from
it: Scripture quotations, which DeepL paraphrases instead of quoting the
published translation, and the Spanish register, where the default came out more
formal than the congregation's own voice.

**`StubTranslator` exists so the whole pipeline can run without an API key.**
The interface is what makes that possible, and it is why `--stub` is a real
option rather than a mock in a test.

## Known limits

- DeepL is **not deterministic**. Two runs on the same source can differ in
  wording, so a script that matches DeepL's literal output — as the Scripture
  substitution does — may miss on a re-run and needs its report read, not
  assumed.
- Scripture quotations are corrected by a separate pass in `scripts/`, not here.
  See the [project README](../../../README.md).

## Code Quality

Unlike the other three plugins, this one carries **no `composer.json` and no
PHPStan or PHPCS configuration**. It has no assets to build and it is scheduled
for removal once the content is filled, so it was never wired into the quality
gates. That is a gap, not a design decision: while the plugin is still here, its
PHP is unchecked.
