[![MIT license](https://img.shields.io/static/v1?label=license&message=CeCILL-B&color=blue)](https://github.com/imorland/syndication/blob/master/LICENSE)
[![Latest Stable Version](https://img.shields.io/packagist/v/ianm/syndication.svg)](https://packagist.org/packages/ianm/syndication)
[![Total Downloads](https://img.shields.io/packagist/dt/ianm/syndication.svg)](https://packagist.org/packages/ianm/syndication)
[![Compatibility](https://flarum-badge-api.davwheat.dev/v1/compat-latest/ianm/syndication)](https://flarum-badge-api.davwheat.dev/v1/compat-latest/ianm/syndication)

# Syndication for [Flarum](https://flarum.org)

Brings RSS and Atom feeds to Flarum.

Based on [`amaurycarrade/flarum-ext-syndication`](https://github.com/AmauryCarrade/flarum-ext-syndication), seemingly now abandoned.  This fork, and the changes required to bring this extension back to life were sponsored by [010101](https://discuss.flarum.org/u/010101)

Compatible with Flarum v1.0 and above

### Installation

```bash
composer require ianm/syndication:"*"
```

### Usage

This extension adds the following feeds to Flarum:

- `/atom`: feed with the last discussions with activity (the `/` page as an Atom feed);
- `/atom/discussions`: feed with the newly created discussions in the forum;
- `/atom/t/tag`: feed with the last discussions in the given tag (the `/t/tag` page as an Atom feed);
- `/atom/t/tag/discussions`: feed with the newly created discussions in the given tag;
- `/atom/d/21-discussion-slug`: feed with the recent posts in the given discussion.

You can replace `atom` by `rss` in the URLs above to get RSS feeds instead. The tags-related feeds are only available if `flarum/tags` is installed and enabled.

You can also add `?sort=latest|top|newest|oldest` to the discussions lists feeds to sort the feed, and `?q=<search>` to filter it. Or both using `?sort=<sorting>&q=<search>`.

Feeds are linked in the pages for autodiscovery. This said, they are not dynamically updated as the page change (except when fully reloaded), earlier because of this [Firefox bug](https://bugzilla.mozilla.org/show_bug.cgi?id=380639), and now because even Firefox no longer display RSS feeds in the browser.

### Links

- [Flarum Discuss post](https://discuss.flarum.org/d/27687)
- [Source code on GitHub](https://github.com/imorland/syndication)
- [Report an issue](https://github.com/imorland/syndication)
- [Packagist](https://packagist.org/packages/ianm/syndication)
- [Extiverse](https://extiverse.com/extension/ianm/syndication)
