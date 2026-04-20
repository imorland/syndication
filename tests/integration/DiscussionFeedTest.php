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

namespace IanM\FlarumFeeds\Tests\integration;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class DiscussionFeedTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    public function setUp(): void
    {
        parent::setUp();

        $this->extension('ianm-syndication');

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
            ],
            Discussion::class => [
                ['id' => 1, 'title' => 'Discussion with posts', 'slug' => 'discussion-with-posts', 'user_id' => 2, 'first_post_id' => 1, 'last_post_id' => 3, 'last_posted_at' => Carbon::now(), 'last_posted_user_id' => 2, 'created_at' => Carbon::now()->subDays(1), 'comment_count' => 3, 'is_private' => false],
                ['id' => 2, 'title' => 'Private discussion', 'slug' => 'private-discussion', 'user_id' => 2, 'first_post_id' => 4, 'last_post_id' => 4, 'last_posted_at' => Carbon::now(), 'last_posted_user_id' => 2, 'created_at' => Carbon::now(), 'comment_count' => 1, 'is_private' => true],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 1, 'number' => 1, 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>apple</p></t>', 'created_at' => Carbon::now()->subDays(1), 'is_private' => false],
                ['id' => 2, 'discussion_id' => 1, 'number' => 2, 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>banana</p></t>', 'created_at' => Carbon::now()->subHours(12), 'is_private' => false],
                ['id' => 3, 'discussion_id' => 1, 'number' => 3, 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>cherry</p></t>', 'created_at' => Carbon::now(), 'is_private' => false],
                ['id' => 4, 'discussion_id' => 2, 'number' => 1, 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>secret</p></t>', 'created_at' => Carbon::now(), 'is_private' => false],
            ],
        ]);
    }

    #[Test]
    public function returns_posts_from_discussion()
    {
        $response = $this->send($this->request('GET', '/rss/d/1'));
        $body = (string) $response->getBody();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('apple', $body);
        $this->assertStringContainsString('banana', $body);
        $this->assertStringContainsString('cherry', $body);
    }

    #[Test]
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

    #[Test]
    public function returns_404_for_nonexistent_discussion()
    {
        $response = $this->send($this->request('GET', '/rss/d/9999'));

        $this->assertEquals(404, $response->getStatusCode());
    }

    #[Test]
    public function guest_cannot_access_private_discussion()
    {
        $response = $this->send($this->request('GET', '/rss/d/2'));

        // A private discussion is hidden from guests by the API's visibility
        // scope, so the feed controller sees it as non-existent.
        $this->assertEquals(404, $response->getStatusCode());
    }

    #[Test]
    public function slug_suffix_in_id_is_accepted()
    {
        $response = $this->send($this->request('GET', '/rss/d/1-discussion-with-posts'));

        $this->assertEquals(200, $response->getStatusCode());
    }

    #[Test]
    public function atom_variant_responds()
    {
        $response = $this->send($this->request('GET', '/atom/d/1'));
        $body = (string) $response->getBody();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('<feed', $body);
        $this->assertStringContainsString('apple', $body);
    }
}
