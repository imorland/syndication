<?php

/*
 * This file is part of ianm/syndication.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace IanM\FlarumFeeds\Tests\integration;

use Carbon\Carbon;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;

class TagsFeedTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    public function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-tags', 'ianm-syndication');

        $this->prepareDatabase([
            'users' => [
                $this->normalUser(),
            ],
            'tags' => [
                ['id' => 1, 'name' => 'General', 'slug' => 'general', 'description' => null, 'color' => '#000', 'position' => 0, 'parent_id' => null, 'is_restricted' => false, 'is_hidden' => false],
                ['id' => 2, 'name' => 'News', 'slug' => 'news', 'description' => null, 'color' => '#000', 'position' => 1, 'parent_id' => null, 'is_restricted' => false, 'is_hidden' => false],
            ],
            'discussions' => [
                ['id' => 1, 'title' => 'General discussion', 'slug' => 'general-discussion', 'user_id' => 2, 'first_post_id' => 1, 'last_post_id' => 1, 'last_posted_at' => Carbon::now()->subHours(2), 'last_posted_user_id' => 2, 'created_at' => Carbon::now()->subHours(2), 'comment_count' => 1, 'is_private' => false],
                ['id' => 2, 'title' => 'News announcement', 'slug' => 'news-announcement', 'user_id' => 2, 'first_post_id' => 2, 'last_post_id' => 2, 'last_posted_at' => Carbon::now()->subHours(1), 'last_posted_user_id' => 2, 'created_at' => Carbon::now()->subHours(1), 'comment_count' => 1, 'is_private' => false],
                ['id' => 3, 'title' => 'Untagged thread', 'slug' => 'untagged-thread', 'user_id' => 2, 'first_post_id' => 3, 'last_post_id' => 3, 'last_posted_at' => Carbon::now(), 'last_posted_user_id' => 2, 'created_at' => Carbon::now(), 'comment_count' => 1, 'is_private' => false],
            ],
            'posts' => [
                ['id' => 1, 'discussion_id' => 1, 'number' => 1, 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>general body</p></t>', 'created_at' => Carbon::now()->subHours(2), 'is_private' => false],
                ['id' => 2, 'discussion_id' => 2, 'number' => 1, 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>news body</p></t>', 'created_at' => Carbon::now()->subHours(1), 'is_private' => false],
                ['id' => 3, 'discussion_id' => 3, 'number' => 1, 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>untagged body</p></t>', 'created_at' => Carbon::now(), 'is_private' => false],
            ],
            'discussion_tag' => [
                ['discussion_id' => 1, 'tag_id' => 1],
                ['discussion_id' => 2, 'tag_id' => 2],
            ],
        ]);
    }

    /**
     * @test
     */
    public function tag_activity_feed_returns_only_tagged_discussions()
    {
        $response = $this->send($this->request('GET', '/rss/t/general'));
        $body = (string) $response->getBody();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('General discussion', $body);
        $this->assertStringNotContainsString('News announcement', $body);
        $this->assertStringNotContainsString('Untagged thread', $body);
    }

    /**
     * @test
     */
    public function tag_discussions_feed_returns_only_tagged_discussions()
    {
        $response = $this->send($this->request('GET', '/rss/t/news/discussions'));
        $body = (string) $response->getBody();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('News announcement', $body);
        $this->assertStringNotContainsString('General discussion', $body);
        $this->assertStringNotContainsString('Untagged thread', $body);
    }

    /**
     * @test
     */
    public function unknown_tag_returns_404()
    {
        $response = $this->send($this->request('GET', '/rss/t/nope'));

        $this->assertEquals(404, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function atom_variant_responds()
    {
        $response = $this->send($this->request('GET', '/atom/t/general'));
        $body = (string) $response->getBody();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('<feed', $body);
        $this->assertStringContainsString('General discussion', $body);
    }

    /**
     * @test
     *
     * Regression guard for the blomstra/search compatibility fix (PR #20).
     *
     * We intercept what the controller asks of the ApiClient and verify that
     * tag filtering is done via `filter[tag]` (which the core TagFilterGambit
     * implements as a `FilterInterface`) and NOT via `filter[q]=tag:slug`.
     *
     * The latter form used to be hijacked by blomstra/search's ApiClient
     * override and rerouted to its Elasticsearch endpoint, which does not
     * parse `tag:` gambits and returned empty feeds.
     */
    public function tag_filter_is_sent_via_filter_tag_not_filter_q()
    {
        $captured = null;

        // Wrap the ApiClient to record the query params passed on the /discussions call.
        $this->app()->getContainer()->extend(
            \Flarum\Api\Client::class,
            function (\Flarum\Api\Client $inner) use (&$captured) {
                return new class($inner, $captured) extends \Flarum\Api\Client {
                    private $inner;
                    private $params = [];
                    public $captured;

                    public function __construct(\Flarum\Api\Client $inner, &$captured)
                    {
                        $this->inner = $inner;
                        $this->captured = &$captured;
                    }

                    public function withQueryParams(array $params): \Flarum\Api\Client
                    {
                        $this->params = $params;

                        return $this;
                    }

                    public function withParentRequest(\Psr\Http\Message\ServerRequestInterface $request): \Flarum\Api\Client
                    {
                        $this->inner = $this->inner->withParentRequest($request);

                        return $this;
                    }

                    public function withBody(array $body): \Flarum\Api\Client
                    {
                        $this->inner = $this->inner->withBody($body);

                        return $this;
                    }

                    public function withActor(\Flarum\User\User $actor): \Flarum\Api\Client
                    {
                        $this->inner = $this->inner->withActor($actor);

                        return $this;
                    }

                    public function get(string $path): \Psr\Http\Message\ResponseInterface
                    {
                        if ($path === '/discussions') {
                            $this->captured = $this->params;
                        }

                        return $this->inner->withQueryParams($this->params)->get($path);
                    }
                };
            }
        );

        $this->send($this->request('GET', '/rss/t/general'));

        $this->assertNotNull($captured, 'ApiClient->get(/discussions) was never called');
        $this->assertArrayHasKey('filter', $captured);
        $this->assertArrayHasKey('tag', $captured['filter'], 'Tag should be passed via filter[tag]');
        $this->assertEquals('general', $captured['filter']['tag']);
        $this->assertArrayNotHasKey('q', $captured['filter'], 'filter[q] must be omitted when there is no free-text term');
    }
}
