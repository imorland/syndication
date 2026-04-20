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

class FeedFormatTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    public function setUp(): void
    {
        parent::setUp();

        $this->extension('ianm-syndication');

        // Content deliberately contains HTML tags and entities so we can assert
        // how the plain-text and html-passthrough render modes differ.
        $this->prepareDatabase([
            'users' => [
                $this->normalUser(),
            ],
            'discussions' => [
                ['id' => 1, 'title' => 'Tom & Jerry: "A Tale"', 'slug' => 'tom-and-jerry', 'user_id' => 2, 'first_post_id' => 1, 'last_post_id' => 1, 'last_posted_at' => Carbon::now(), 'last_posted_user_id' => 2, 'created_at' => Carbon::now()->subHours(1), 'comment_count' => 1, 'is_private' => false],
            ],
            'posts' => [
                ['id' => 1, 'discussion_id' => 1, 'number' => 1, 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>hello <em>world</em> &amp; friends</p></t>', 'created_at' => Carbon::now()->subHours(1), 'is_private' => false],
            ],
        ]);
    }

    /**
     * @test
     */
    public function atom_entry_has_published_and_updated_dates()
    {
        $response = $this->send($this->request('GET', '/atom'));
        $body = (string) $response->getBody();

        $this->assertEquals(200, $response->getStatusCode());

        // Atom 1.0 spec (RFC 4287): some feed readers require <published> on
        // every entry to display a first-posted date. <updated> alone is
        // treated as "last modified" and can confuse readers.
        $this->assertMatchesRegularExpression('#<entry>.*<published>[^<]+</published>.*</entry>#s', $body);
        $this->assertMatchesRegularExpression('#<entry>.*<updated>[^<]+</updated>.*</entry>#s', $body);
    }

    /**
     * @test
     */
    public function atom_content_declares_type_attribute()
    {
        // Default html setting is off, so content should be plain text.
        $response = $this->send($this->request('GET', '/atom'));
        $body = (string) $response->getBody();

        // Atom <content> defaults to type="text" when the attribute is absent,
        // but some validators (and some readers) require an explicit type.
        $this->assertStringContainsString('<content type="text">', $body);
        $this->assertStringNotContainsString('<content type="html">', $body);
    }

    /**
     * @test
     */
    public function atom_content_declares_html_type_when_html_setting_enabled()
    {
        $this->setting('ianm-syndication.plugin.html', '1');

        $response = $this->send($this->request('GET', '/atom'));
        $body = (string) $response->getBody();

        $this->assertStringContainsString('<content type="html">', $body);
        $this->assertStringNotContainsString('<content type="text">', $body);
    }

    /**
     * @test
     */
    public function plain_text_mode_decodes_entities()
    {
        // html setting is off by default.
        $response = $this->send($this->request('GET', '/atom'));
        $body = (string) $response->getBody();

        // The post body contains `<em>world</em> &amp; friends`. In plain-text
        // mode we strip tags AND decode entities, so the ampersand should be
        // rendered literally inside the CDATA block rather than as `&amp;`.
        $this->assertStringContainsString('hello world & friends', $body);
        $this->assertStringNotContainsString('&amp; friends', $body);
    }

    /**
     * @test
     */
    public function html_mode_preserves_markup_and_entities()
    {
        $this->setting('ianm-syndication.plugin.html', '1');

        $response = $this->send($this->request('GET', '/atom'));
        $body = (string) $response->getBody();

        // In html passthrough mode the `<em>` should survive. Because it is
        // inside CDATA the feed reader will render it as HTML.
        $this->assertStringContainsString('<em>world</em>', $body);
    }

    /**
     * @test
     */
    public function rss_feed_decodes_entities_in_plain_text_mode()
    {
        $response = $this->send($this->request('GET', '/rss'));
        $body = (string) $response->getBody();

        $this->assertStringContainsString('hello world & friends', $body);
        $this->assertStringNotContainsString('&amp; friends', $body);
    }
}
