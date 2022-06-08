import app from 'flarum/admin/app';

app.initializers.add('ianm-syndication', () => {
  const typeOptions = {
    atom: 'atom',
    rss: 'rss',
  };

  app.extensionData
    .for('ianm-syndication')
    .registerSetting({
      label: app.translator.trans('ianm-syndication.admin.settings.full-text.label'),
      setting: 'ianm-syndication.plugin.full-text',
      type: 'boolean',
      help: app.translator.trans('ianm-syndication.admin.settings.full-text.help'),
    })
    .registerSetting({
      label: app.translator.trans('ianm-syndication.admin.settings.html.label'),
      setting: 'ianm-syndication.plugin.html',
      type: 'boolean',
      help: app.translator.trans('ianm-syndication.admin.settings.html.help'),
    })
    .registerSetting({
      label: app.translator.trans('ianm-syndication.admin.settings.entries-count'),
      setting: 'ianm-syndication.plugin.entries-count',
      type: 'number',
      placeholder: 100,
      min: 1,
    })
    .registerSetting({
      label: app.translator.trans('ianm-syndication.admin.settings.forum-icons.label'),
      help: app.translator.trans('ianm-syndication.admin.settings.forum-icons.help'),
      setting: 'ianm-syndication.plugin.forum-icons',
      type: 'boolean',
    })
    .registerSetting({
      label: app.translator.trans('ianm-syndication.admin.settings.forum-link-format.label'),
      help: app.translator.trans('ianm-syndication.admin.settings.forum-link-format.help'),
      setting: 'ianm-syndication.plugin.forum-format',
      type: 'select',
      options: typeOptions,
      default: 'atom',
    });
});
