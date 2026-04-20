<?php

/*
 * This file is part of ianm/syndication.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace IanM\FlarumFeeds\Tests\integration;

use Flarum\Testing\integration\TestCase;

class FeedsSmokeTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->extension('ianm-syndication');
    }

    /**
     * @test
     */
    public function rss_feed_responds_200_for_guest()
    {
        $response = $this->send($this->request('GET', '/rss'));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('application/rss+xml', $response->getHeaderLine('Content-Type'));
    }

    /**
     * @test
     */
    public function atom_feed_responds_200_for_guest()
    {
        $response = $this->send($this->request('GET', '/atom'));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('application/atom+xml', $response->getHeaderLine('Content-Type'));
    }
}
