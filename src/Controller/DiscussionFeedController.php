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

use Flarum\Api\Client as ApiClient;
use Flarum\Http\Exception\RouteNotFoundException;
use Flarum\Http\UrlGenerator;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\User;
use Illuminate\Contracts\View\Factory;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface as Request;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Displays feed for a given topic.
 */
class DiscussionFeedController extends AbstractFeedController
{
    protected $routeName = 'discussion';

    public function __construct(Factory $view, ApiClient $api, TranslatorInterface $translator, SettingsRepositoryInterface $settings, UrlGenerator $url)
    {
        parent::__construct($view, $api, $translator, $settings, $url);
    }

    /**
     * @param Request $request
     *
     * @return array
     */
    protected function getFeedContent(Request $request)
    {
        $query_params = $request->getQueryParams();
        $discussion_id = (int) Arr::get($query_params, 'id');

        $actor = $this->getActor($request);

        $discussion = $this->getDiscussionsDocument($request, $actor, [
            'id'   => $discussion_id,
            'page' => [
                'limit' => 1,
            ],
        ])->data;

        $posts = $this->getPostsDocument($request, $actor, [
            'filter' => [
                'discussion' => $discussion_id,
            ],
            'page' => [
                'offset' => 0,
                'limit'  => $this->getSetting('entries-count'),
            ],
            'sort' => '-createdAt',
        ]);

        $entries = [];
        $lastModified = null;

        foreach ($posts->data as $post) {
            if ($post->attributes->contentType != 'comment') {
                continue;
            }

            $entries[] = [
                'title'       => $discussion->attributes->title,
                'content'     => $this->summarize($this->stripHTML($post->attributes->contentHtml)),
                'link'        => $this->url->to('forum')->route('discussion', ['id' => $discussion->attributes->slug, 'near' => $post->attributes->number]),
                'id'          => $this->url->to('forum')->route('discussion', ['id' => $discussion->id, 'near' => $post->attributes->number]),
                'pubdate'     => $this->parseDate($post->attributes->createdAt),
                'author'      => isset($post->relationships->user) ? $this->getRelationship($posts, $post->relationships->user)->username : '[deleted]',
            ];

            $modified = $this->parseDate(Arr::get($post->attributes, 'editedAt', $post->attributes->createdAt));

            if ($lastModified === null || $lastModified < $modified) {
                $lastModified = $modified;
            }
        }

        return [
            'title'        => $this->translator->trans('ianm-syndication.forum.feeds.titles.discussion_title', ['{discussion_name}' => $discussion->attributes->title]),
            'description'  => $this->translator->trans('ianm-syndication.forum.feeds.titles.discussion_subtitle', ['{discussion_name}' => $discussion->attributes->title]),
            'link'         => $this->url->to('forum')->route('discussion', ['id' => $discussion->attributes->slug]),
            'pubDate'      => new \DateTime(),
            'lastModified' => $lastModified,
            'entries'      => $entries,
        ];
    }

    /**
     * Get the result of an API request to show a discussion.
     *
     * @param Request $request
     * @param User    $actor
     * @param array   $params
     *
     * @throws RouteNotFoundException
     *
     * @return object
     */
    protected function getDiscussionsDocument(Request $request, User $actor, array $params)
    {
        return $this->getAPIDocument($request, '/discussions/'.$params['id'], $actor, $params);
    }

    /**
     * Get the result of an API request to list a discussion posts.
     *
     * @param Request $request
     * @param User    $actor
     * @param array   $params
     *
     * @throws RouteNotFoundException
     *
     * @return object
     */
    protected function getPostsDocument(Request $request, User $actor, array $params)
    {
        return $this->getAPIDocument($request, '/posts', $actor, $params);
    }

    protected function getPermalink(array $queryParams, string $feedType): string
    {
        // Remove the slug from the Id if it is present.
        // The Id of the feed should not change if the discussion is renamed.
        $id = strstr($queryParams['id'], '-', true);
        if ($id != false) {
            $queryParams['id'] = $id;
        }

        return parent::getPermalink($queryParams, $feedType);
    }
}
