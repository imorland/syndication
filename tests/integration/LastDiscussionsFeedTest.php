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
            User::class => [
                $this->normalUser(),
            ],
            Discussion::class => [
                ['id' => 1, 'title' => 'Older thread A', 'slug' => 'older-a', 'user_id' => 2, 'first_post_id' => 1, 'last_post_id' => 2, 'last_posted_at' => Carbon::now(), 'last_posted_user_id' => 2, 'created_at' => Carbon::now()->subDays(5), 'comment_count' => 2, 'is_private' => false],
                ['id' => 2, 'title' => 'Newer thread B', 'slug' => 'newer-b', 'user_id' => 2, 'first_post_id' => 3, 'last_post_id' => 3, 'last_posted_at' => Carbon::now()->subHours(1), 'last_posted_user_id' => 2, 'created_at' => Carbon::now()->subHours(1), 'comment_count' => 1, 'is_private' => false],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 1, 'number' => 1, 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>first post of A</p></t>', 'created_at' => Carbon::now()->subDays(5), 'is_private' => false],
                ['id' => 2, 'discussion_id' => 1, 'number' => 2, 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>recent reply in A</p></t>', 'created_at' => Carbon::now(), 'is_private' => false],
                ['id' => 3, 'discussion_id' => 2, 'number' => 1, 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>first post of B</p></t>', 'created_at' => Carbon::now()->subHours(1), 'is_private' => false],
            ],
        ]);
    }

    #[Test]
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

    #[Test]
    public function uses_first_post_content_not_last()
    {
        $response = $this->send($this->request('GET', '/rss/discussions'));
        $body = (string) $response->getBody();

        $this->assertStringContainsString('first post of A', $body);
        $this->assertStringNotContainsString('recent reply in A', $body);
    }
}
