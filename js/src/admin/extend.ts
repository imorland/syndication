import app from 'flarum/admin/app';
import Extend from 'flarum/common/extenders';

const typeOptions = {
  atom: 'atom',
  rss: 'rss',
};

export default [
  new Extend.Admin()
    .setting(() => ({
      label: app.translator.trans('ianm-syndication.admin.settings.full-text.label'),
      setting: 'ianm-syndication.plugin.full-text',
      type: 'boolean',
      help: app.translator.trans('ianm-syndication.admin.settings.full-text.help'),
    }))
    .setting(() => ({
      label: app.translator.trans('ianm-syndication.admin.settings.html.label'),
      setting: 'ianm-syndication.plugin.html',
      type: 'boolean',
      help: app.translator.trans('ianm-syndication.admin.settings.html.help'),
    }))
    .setting(() => ({
      label: app.translator.trans('ianm-syndication.admin.settings.entries-count'),
      setting: 'ianm-syndication.plugin.entries-count',
      type: 'number',
      placeholder: 100,
      min: 1,
    }))
    .setting(() => ({
      label: app.translator.trans('ianm-syndication.admin.settings.forum-icons.label'),
      help: app.translator.trans('ianm-syndication.admin.settings.forum-icons.help'),
      setting: 'ianm-syndication.plugin.forum-icons',
      type: 'boolean',
    }))
    .setting(() => ({
      label: app.translator.trans('ianm-syndication.admin.settings.forum-link-format.label'),
      help: app.translator.trans('ianm-syndication.admin.settings.forum-link-format.help'),
      setting: 'ianm-syndication.plugin.forum-format',
      type: 'select',
      options: typeOptions,
      default: 'atom',
    })),
];
