import app from 'flarum/forum/app';
import addFeedIcons from './addFeedIcons';

app.initializers.add('ianm-syndication', () => {
  addFeedIcons();
});
