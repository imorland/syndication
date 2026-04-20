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

class LastDiscussionsFeedTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    public function setUp(): void
    {
        parent::setUp();

        $this->extension('ianm-syndication');

        // "A" was created first but replied to most recently.
        // "B" was created later but has no new replies.
        // Activity feed should put A first; creation feed should put B first.
        $this->prepareDatabase([
            'users' => [
                $this->normalUser(),
            ],
            'discussions' => [
                ['id' => 1, 'title' => 'Older thread A', 'slug' => 'older-a', 'user_id' => 2, 'first_post_id' => 1, 'last_post_id' => 2, 'last_posted_at' => Carbon::now(), 'last_posted_user_id' => 2, 'created_at' => Carbon::now()->subDays(5), 'comment_count' => 2, 'is_private' => false],
                ['id' => 2, 'title' => 'Newer thread B', 'slug' => 'newer-b', 'user_id' => 2, 'first_post_id' => 3, 'last_post_id' => 3, 'last_posted_at' => Carbon::now()->subHours(1), 'last_posted_user_id' => 2, 'created_at' => Carbon::now()->subHours(1), 'comment_count' => 1, 'is_private' => false],
            ],
            'posts' => [
                ['id' => 1, 'discussion_id' => 1, 'number' => 1, 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>first post of A</p></t>', 'created_at' => Carbon::now()->subDays(5), 'is_private' => false],
                ['id' => 2, 'discussion_id' => 1, 'number' => 2, 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>recent reply in A</p></t>', 'created_at' => Carbon::now(), 'is_private' => false],
                ['id' => 3, 'discussion_id' => 2, 'number' => 1, 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>first post of B</p></t>', 'created_at' => Carbon::now()->subHours(1), 'is_private' => false],
            ],
        ]);
    }

    /**
     * @test
     */
    public function orders_by_creation_date_descending()
    {
        $response = $this->send($this->request('GET', '/rss/discussions'));
        $body = (string) $response->getBody();

        $this->assertEquals(200, $response->getStatusCode());

        $posA = strpos($body, 'Older thread A');
        $posB = strpos($body, 'Newer thread B');

        $this->assertNotFalse($posA);
        $this->assertNotFalse($posB);
        // B was created more recently than A, so it should appear first in a
        // creation-ordered feed (unlike the activity feed where A would win).
        $this->assertLessThan($posA, $posB);
    }

    /**
     * @test
     */
    public function uses_first_post_content_not_last()
    {
        $response = $this->send($this->request('GET', '/rss/discussions'));
        $body = (string) $response->getBody();

        $this->assertStringContainsString('first post of A', $body);
        $this->assertStringNotContainsString('recent reply in A', $body);
    }
}
