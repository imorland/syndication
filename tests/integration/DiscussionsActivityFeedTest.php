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

class DiscussionsActivityFeedTest extends TestCase
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
                ['id' => 1, 'title' => 'Public discussion A', 'slug' => 'public-discussion-a', 'user_id' => 2, 'first_post_id' => 1, 'last_post_id' => 1, 'last_posted_at' => Carbon::now()->subHours(2), 'last_posted_user_id' => 2, 'created_at' => Carbon::now()->subHours(2), 'comment_count' => 1, 'is_private' => false],
                ['id' => 2, 'title' => 'Public discussion B', 'slug' => 'public-discussion-b', 'user_id' => 2, 'first_post_id' => 2, 'last_post_id' => 2, 'last_posted_at' => Carbon::now()->subHours(1), 'last_posted_user_id' => 2, 'created_at' => Carbon::now()->subHours(1), 'comment_count' => 1, 'is_private' => false],
                ['id' => 3, 'title' => 'Private discussion', 'slug' => 'private-discussion', 'user_id' => 2, 'first_post_id' => 3, 'last_post_id' => 3, 'last_posted_at' => Carbon::now(), 'last_posted_user_id' => 2, 'created_at' => Carbon::now(), 'comment_count' => 1, 'is_private' => true],
            ],
            'posts' => [
                ['id' => 1, 'discussion_id' => 1, 'number' => 1, 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>body of public A</p></t>', 'created_at' => Carbon::now()->subHours(2), 'is_private' => false],
                ['id' => 2, 'discussion_id' => 2, 'number' => 1, 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>body of public B</p></t>', 'created_at' => Carbon::now()->subHours(1), 'is_private' => false],
                ['id' => 3, 'discussion_id' => 3, 'number' => 1, 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>body of private</p></t>', 'created_at' => Carbon::now(), 'is_private' => true],
            ],
        ]);
    }

    /**
     * @test
     */
    public function guest_sees_public_discussions_only()
    {
        $response = $this->send($this->request('GET', '/rss'));
        $body = (string) $response->getBody();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('Public discussion A', $body);
        $this->assertStringContainsString('Public discussion B', $body);
        $this->assertStringNotContainsString('Private discussion', $body);
    }

    /**
     * @test
     */
    public function rss_feed_has_valid_shape()
    {
        $response = $this->send($this->request('GET', '/rss'));
        $body = (string) $response->getBody();

        $this->assertStringContainsString('<?xml', $body);
        $this->assertStringContainsString('<rss', $body);
        $this->assertStringContainsString('<channel>', $body);
        $this->assertStringContainsString('<item>', $body);
    }

    /**
     * @test
     */
    public function atom_feed_has_valid_shape()
    {
        $response = $this->send($this->request('GET', '/atom'));
        $body = (string) $response->getBody();

        $this->assertStringContainsString('<?xml', $body);
        $this->assertStringContainsString('<feed', $body);
        $this->assertStringContainsString('<entry>', $body);
    }

    /**
     * @test
     */
    public function entries_count_setting_limits_items()
    {
        $this->setting('ianm-syndication.plugin.entries-count', '1');

        $response = $this->send($this->request('GET', '/rss'));
        $body = (string) $response->getBody();

        $this->assertEquals(1, substr_count($body, '<item>'));
    }

    /**
     * @test
     */
    public function last_modified_header_is_set()
    {
        $response = $this->send($this->request('GET', '/rss'));

        $this->assertNotEmpty($response->getHeaderLine('Last-Modified'));
    }

    /**
     * @test
     */
    public function feed_orders_by_last_posted_at_descending()
    {
        $response = $this->send($this->request('GET', '/rss'));
        $body = (string) $response->getBody();

        $posA = strpos($body, 'Public discussion A');
        $posB = strpos($body, 'Public discussion B');

        $this->assertNotFalse($posA);
        $this->assertNotFalse($posB);
        // B was posted more recently than A, so it should appear first.
        $this->assertLessThan($posA, $posB);
    }
}
