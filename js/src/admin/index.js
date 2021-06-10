import app from 'flarum/admin/app';

app.initializers.add('ianm-syndication', () => {
    app.extensionData
        .for('ianm-syndication')
        .registerSetting({
            label: app.translator.trans('ianm-syndication.admin.settings.full-text.label'),
            setting: 'ianm-syndication.plugin.full-text',
            type: 'boolean',
        })
        .registerSetting(function () {
            return (
                <div>
                    <p>{app.translator.trans('ianm-syndication.admin.settings.full-text.help')}</p>
                    <p>{app.translator.trans('ianm-syndication.admin.settings.full-text.recommendation')}</p>
                </div>
            );
        })
        .registerSetting({
            label: app.translator.trans('ianm-syndication.admin.settings.html.label'),
            setting: 'ianm-syndication.plugin.html',
            type: 'boolean',
        })
        .registerSetting(function () {
            return (
                <div>
                    <p>{app.translator.trans('ianm-syndication.admin.settings.html.help')}</p>
                </div>
            );
        })
        .registerSetting({
            label: app.translator.trans('ianm-syndication.admin.settings.entries-count'),
            setting: 'ianm-syndication.plugin.entries-count',
            type: 'integer',
        });
});
