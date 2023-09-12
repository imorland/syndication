<?php

/*
 * Copyright or © or Copr. flarum-ext-syndication contributor : Amaury
 * Carrade (2016)
 *
 * https://amaury.carrade.eu
 *
 * This software is a computer program whose purpose is to provides RSS
 * and Atom feeds to Flarum.
 *
 * This software is governed by the CeCILL-B license under French law and
 * abiding by the rules of distribution of free software.  You can  use,
 * modify and/ or redistribute the software under the terms of the CeCILL-B
 * license as circulated by CEA, CNRS and INRIA at the following URL
 * "http://www.cecill.info".
 *
 * As a counterpart to the access to the source code and  rights to copy,
 * modify and redistribute granted by the license, users are provided only
 * with a limited warranty  and the software's author,  the holder of the
 * economic rights,  and the successive licensors  have only  limited
 * liability.
 *
 * In this respect, the user's attention is drawn to the risks associated
 * with loading,  using,  modifying and/or developing or reproducing the
 * software by the user in light of its specific status of free software,
 * that may mean  that it is complicated to manipulate,  and  that  also
 * therefore means  that it is reserved for developers  and  experienced
 * professionals having in-depth computer knowledge. Users are therefore
 * encouraged to load and test the software's suitability as regards their
 * requirements in conditions enabling the security of their systems and/or
 * data to be ensured and,  more generally, to use and operate it in the
 * same conditions as regards security.
 *
 * The fact that you are presently reading this means that you have had
 * knowledge of the CeCILL-B license and that you accept its terms.
 *
 */

namespace IanM\FlarumFeeds\Controller;

use DateTime;
use Flarum\Post\CommentPost;
use Flarum\Post\PostRepository;
use Flarum\User\UserRepository;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Displays feed for a given topic.
 */
class UserPostsFeedController extends AbstractFeedController
{
    protected $routeName = 'user_posts';

    protected function getFeedContent(Request $request): array
    {
        $username = Arr::get($request->getQueryParams(), 'username');

        $actor = $this->getActor($request);

        $user = resolve(UserRepository::class)->findOrFailByUsername($username);
        $posts = resolve(PostRepository::class)
            ->queryVisibleTo($actor)
            ->with(['discussion'])
            ->where('type', CommentPost::$type)
            ->where('user_id', $user->id)
            ->limit($this->getSetting('entries-count'))
            ->orderBy('created_at', 'desc')
            ->setModel(new CommentPost())
            ->get();

        $entries = [];
        $lastModified = null;

        /** @var CommentPost $post */
        foreach ($posts as $post) {
            $entries[] = [
                'title'       => $post->discussion->user_id === $user->id
                    ? $post->discussion->title
                    : $this->translator->trans('ianm-syndication.forum.feeds.entries.user_posts.title_reply', ['{discussion}' => $post->discussion->title]),
                'content'     => $this->summarize($this->stripHTML($post->formatContent($request))),
                'link'        => $this->url->to('forum')->route('discussion', ['id' => $post->discussion->slug, 'near' => $post->number]),
                'id'          => $this->url->to('forum')->route('discussion', ['id' => $post->discussion->id, 'near' => $post->number]),
                'pubdate'     => $this->parseDate($post->created_at->format(DateTime::RFC3339)),
                'author'      => $username,
            ];

            $modified = $post->edited_at ?? $post->created_at;

            if ($lastModified === null || $lastModified < $modified) {
                $lastModified = $modified;
            }
        }

        return [
            'title'        => $this->translator->trans('ianm-syndication.forum.feeds.titles.user_title', ['{username}' => $username]),
            'description'  => $this->translator->trans('ianm-syndication.forum.feeds.titles.user_subtitle', ['{username}' => $username]),
            'link'         => $this->url->to('forum')->route('user', ['username' => $username]),
            'pubDate'      => new \DateTime(),
            'lastModified' => $lastModified,
            'entries'      => $entries,
        ];
    }
}
