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
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Flarum\User\User;
use Flarum\Discussion\Discussion;
use Flarum\Post\Post;

class UserPostsFeedTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    public function setUp(): void
    {
        parent::setUp();

        $this->extension('ianm-syndication');

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'other', 'password' => '$2y$10$LO59tiT7uggl6Oe23o/O6.utnF6ipngYjvMvaxo1TciKqBttDNKim', 'email' => 'other@machine.local', 'is_email_confirmed' => 1],
            ],
            Discussion::class => [
                ['id' => 1, 'title' => 'Own thread', 'slug' => 'own-thread', 'user_id' => 2, 'first_post_id' => 1, 'last_post_id' => 1, 'last_posted_at' => Carbon::now(), 'last_posted_user_id' => 2, 'created_at' => Carbon::now()->subHours(3), 'comment_count' => 1, 'is_private' => false],
                ['id' => 2, 'title' => 'Other thread', 'slug' => 'other-thread', 'user_id' => 3, 'first_post_id' => 2, 'last_post_id' => 3, 'last_posted_at' => Carbon::now(), 'last_posted_user_id' => 2, 'created_at' => Carbon::now()->subHours(2), 'comment_count' => 2, 'is_private' => false],
                ['id' => 3, 'title' => 'Private thread', 'slug' => 'private-thread', 'user_id' => 2, 'first_post_id' => 4, 'last_post_id' => 4, 'last_posted_at' => Carbon::now(), 'last_posted_user_id' => 2, 'created_at' => Carbon::now(), 'comment_count' => 1, 'is_private' => true],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 1, 'number' => 1, 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>start own</p></t>', 'created_at' => Carbon::now()->subHours(3), 'is_private' => false],
                ['id' => 2, 'discussion_id' => 2, 'number' => 1, 'user_id' => 3, 'type' => 'comment', 'content' => '<t><p>other started this</p></t>', 'created_at' => Carbon::now()->subHours(2), 'is_private' => false],
                ['id' => 3, 'discussion_id' => 2, 'number' => 2, 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>reply in other</p></t>', 'created_at' => Carbon::now()->subHours(1), 'is_private' => false],
                ['id' => 4, 'discussion_id' => 3, 'number' => 1, 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>hush this is secret</p></t>', 'created_at' => Carbon::now(), 'is_private' => false],
                ['id' => 5, 'discussion_id' => 1, 'number' => 2, 'user_id' => 2, 'type' => 'discussionRenamed', 'content' => '["old","new"]', 'created_at' => Carbon::now()->subHours(1), 'is_private' => false],
            ],
        ]);
    }

    #[Test]
    public function returns_only_comment_posts_by_user()
    {
        $response = $this->send($this->request('GET', '/rss/u/normal/posts'));
        $body = (string) $response->getBody();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('start own', $body);
        $this->assertStringContainsString('reply in other', $body);
        // Posts by other users should be excluded.
        $this->assertStringNotContainsString('other started this', $body);
        // Non-comment posts (event posts) should be excluded.
        $this->assertStringNotContainsString('discussionRenamed', $body);
    }

    #[Test]
    public function guest_does_not_see_posts_in_private_discussions()
    {
        $response = $this->send($this->request('GET', '/rss/u/normal/posts'));
        $body = (string) $response->getBody();

        $this->assertStringNotContainsString('hush this is secret', $body);
    }

    #[Test]
    public function returns_404_for_unknown_username()
    {
        $response = $this->send($this->request('GET', '/rss/u/ghost/posts'));

        $this->assertEquals(404, $response->getStatusCode());
    }

    #[Test]
    public function atom_variant_responds()
    {
        $response = $this->send($this->request('GET', '/atom/u/normal/posts'));
        $body = (string) $response->getBody();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('<feed', $body);
        $this->assertStringContainsString('start own', $body);
    }
}
