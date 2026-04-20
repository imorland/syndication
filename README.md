[![license](https://img.shields.io/static/v1?label=license&message=CeCILL-B&color=blue)](https://github.com/imorland/syndication/blob/1.x/LICENSE)
[![Latest Stable Version](https://img.shields.io/packagist/v/ianm/syndication.svg)](https://packagist.org/packages/ianm/syndication)
[![Total Downloads](https://img.shields.io/packagist/dt/ianm/syndication.svg)](https://packagist.org/packages/ianm/syndication)
[![Backend CI](https://github.com/imorland/syndication/actions/workflows/backend.yml/badge.svg?branch=1.x)](https://github.com/imorland/syndication/actions/workflows/backend.yml)

# Syndication for [Flarum](https://flarum.org)

Brings RSS and Atom feeds to Flarum.

Based on [`amaurycarrade/flarum-ext-syndication`](https://github.com/AmauryCarrade/flarum-ext-syndication), abandoned since 2019. This fork, and the changes required to bring the extension back to life, were sponsored by [010101](https://discuss.flarum.org/u/010101).

### Installation

```bash
composer require ianm/syndication:"*"
```

### Feeds

| Route | Contents |
| --- | --- |
| `/atom` | Latest discussions with activity (the `/` page as a feed) |
| `/atom/discussions` | Newly created discussions across the forum |
| `/atom/d/{id}` | Recent posts in a single discussion (e.g. `/atom/d/21-discussion-slug`) |
| `/atom/u/{username}/posts` | Recent posts by a single user |
| `/atom/t/{slug}` | Latest discussions with activity in a tag *(requires `flarum/tags`)* |
| `/atom/t/{slug}/discussions` | Newly created discussions in a tag *(requires `flarum/tags`)* |

Replace `atom` with `rss` on any of these URLs to get the RSS variant.

Discussion-list feeds accept `?sort=latest|top|newest|oldest` to reorder and `?q=<search>` to filter. Both can be combined: `?sort=<sorting>&q=<search>`.

Feeds are linked in the page head for autodiscovery.

### Settings

| Key | Default | Effect |
| --- | --- | --- |
| Full-Text Feeds | off | When off, post content is truncated to ~400 characters. When on, the full body is included. |
| Formatted Feeds (HTML) | off | When off, HTML is stripped and entities are decoded so content is plain text. When on, the original HTML is passed through inside a CDATA block (Atom `<content type="html">`). |
| Entries per feed | 100 | Maximum number of items in each feed response. |
| Show feed links on the forum | off | Displays an RSS icon on the All Discussions, Tag and Discussion pages. |
| Forum feed format | Atom | Which format the forum icons link to — Atom or RSS. |

### Compatibility notes

- **`blomstra/search`** — Tag feeds work with Blomstra's search extension installed. Tag filtering is passed through `filter[tag]` (handled by core's `TagFilterGambit` as a `FilterInterface`) rather than gambit-encoded in `filter[q]`, which previously caused Blomstra's Elasticsearch override to return empty results. See [#20](https://github.com/imorland/syndication/pull/20).

### Links

- [Flarum Discuss post](https://discuss.flarum.org/d/27687)
- [Source code on GitHub](https://github.com/imorland/syndication)
- [Report an issue](https://github.com/imorland/syndication/issues)
- [Packagist](https://packagist.org/packages/ianm/syndication)
