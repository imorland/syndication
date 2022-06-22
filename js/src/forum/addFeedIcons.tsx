import ItemList from 'flarum/common/utils/ItemList';
import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import IndexPage from 'flarum/forum/components/IndexPage';
import type Mithril from 'mithril';
import DiscussionPage from 'flarum/forum/components/DiscussionPage';
import DiscussionControls from 'flarum/forum/utils/DiscussionControls';
import LinkButton from 'flarum/common/components/LinkButton';
import Discussion from 'flarum/common/models/Discussion';

export default function addFeedIcons() {
  extend(IndexPage.prototype, 'actionItems', function (items: ItemList<Mithril.Children>) {
    if (!app.forum.attribute('ianm-syndication.plugin.forum-icons')) {
      return;
    }

    if (items.has('refresh')) items.setPriority('refresh', 100);
    if (items.has('markAllAsRead')) items.setPriority('markAllAsRead', 90);

    const format = app.forum.attribute('ianm-syndication.plugin.forum-format');

    let url = app.forum.attribute('baseUrl') + '/' + format;

    if ('flarum-tags' in flarum.extensions && this.currentTag()) {
      url = url + '/t/' + this.currentTag().slug();
    }

    items.add('rss-feed', <LinkButton icon="fas fa-rss" className="Button Button--icon" href={url} target="_blank" />, 105);
  });

  extend(DiscussionControls, 'userControls', function (items: ItemList<Mithril.Children>, discussion: Discussion) {
    if (!app.forum.attribute('ianm-syndication.plugin.forum-icons')) {
      return;
    }

    const format = app.forum.attribute('ianm-syndication.plugin.forum-format');

    items.add(
      'rss-link',
      <LinkButton icon="fas fa-rss" href={app.forum.attribute('baseUrl') + `/${format}/d/` + discussion.id()} external={true} target="_blank">
        {app.translator.trans('ianm-syndication.forum.discussion.feed_link')}
      </LinkButton>
    );
  });
}
