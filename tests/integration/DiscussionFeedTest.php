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

class DiscussionFeedTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    public function setUp(): void
    {
        parent::setUp();

        $this->extension('ianm-syndication');

        $this->prepareDatabase([
            'users' => [
                $this->normalUser(),
            ],
            'discussions' => [
                ['id' => 1, 'title' => 'Discussion with posts', 'slug' => 'discussion-with-posts', 'user_id' => 2, 'first_post_id' => 1, 'last_post_id' => 3, 'last_posted_at' => Carbon::now(), 'last_posted_user_id' => 2, 'created_at' => Carbon::now()->subDays(1), 'comment_count' => 3, 'is_private' => false],
                ['id' => 2, 'title' => 'Private discussion', 'slug' => 'private-discussion', 'user_id' => 2, 'first_post_id' => 4, 'last_post_id' => 4, 'last_posted_at' => Carbon::now(), 'last_posted_user_id' => 2, 'created_at' => Carbon::now(), 'comment_count' => 1, 'is_private' => true],
            ],
            'posts' => [
                ['id' => 1, 'discussion_id' => 1, 'number' => 1, 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>apple</p></t>', 'created_at' => Carbon::now()->subDays(1), 'is_private' => false],
                ['id' => 2, 'discussion_id' => 1, 'number' => 2, 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>banana</p></t>', 'created_at' => Carbon::now()->subHours(12), 'is_private' => false],
                ['id' => 3, 'discussion_id' => 1, 'number' => 3, 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>cherry</p></t>', 'created_at' => Carbon::now(), 'is_private' => false],
                ['id' => 4, 'discussion_id' => 2, 'number' => 1, 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>secret</p></t>', 'created_at' => Carbon::now(), 'is_private' => false],
            ],
        ]);
    }

    /**
     * @test
     */
    public function returns_posts_from_discussion()
    {
        $response = $this->send($this->request('GET', '/rss/d/1'));
        $body = (string) $response->getBody();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('apple', $body);
        $this->assertStringContainsString('banana', $body);
        $this->assertStringContainsString('cherry', $body);
    }

    /**
     * @test
     */
    public function orders_posts_by_created_at_descending()
    {
        $response = $this->send($this->request('GET', '/rss/d/1'));
        $body = (string) $response->getBody();

        $posCherry = strpos($body, 'cherry');
        $posApple = strpos($body, 'apple');

        $this->assertNotFalse($posCherry);
        $this->assertNotFalse($posApple);
        // cherry (newest) should appear before apple (oldest) in the feed.
        $this->assertLessThan($posApple, $posCherry);
    }

    /**
     * @test
     */
    public function returns_404_for_nonexistent_discussion()
    {
        $response = $this->send($this->request('GET', '/rss/d/9999'));

        $this->assertEquals(404, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function guest_cannot_access_private_discussion()
    {
        $response = $this->send($this->request('GET', '/rss/d/2'));

        // A private discussion is hidden from guests by the API's visibility
        // scope, so the feed controller sees it as non-existent.
        $this->assertEquals(404, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function slug_suffix_in_id_is_accepted()
    {
        $response = $this->send($this->request('GET', '/rss/d/1-discussion-with-posts'));

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function atom_variant_responds()
    {
        $response = $this->send($this->request('GET', '/atom/d/1'));
        $body = (string) $response->getBody();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('<feed', $body);
        $this->assertStringContainsString('apple', $body);
    }
}
