{!! '<'.'?xml version="1.0" encoding="utf-8"?'.'>' !!}

<feed xmlns="http://www.w3.org/2005/Atom">

    <title><![CDATA[{!! $title !!}]]></title>
    <subtitle><![CDATA[{!! $description !!}]]></subtitle>
    <link href="{{ $self_link }}" rel="self" />
    <link href="{{ $link }}/" />
    <id>{{ $id }}</id>
    <updated>{{ $pubDate->format(DateTime::ATOM) }}</updated>

    @foreach ($entries as $entry)
    <entry>
        <title><![CDATA[{!! $entry['title'] !!}]]></title>
        <link rel="alternate" type="text/html" href="{{ $entry['link'] }}"/>
        <id>{{ $entry['id'] }}</id>
        <updated>{{ $entry['pubdate']->format(DateTime::ATOM) }}</updated>
        <content
        @if ($html)
            type="html"
        @endif><![CDATA[{!! $entry['content'] !!}]]></content>
        <author>
            <name><![CDATA[{{ $entry['author'] }}]]></name>
        </author>
    </entry>
    @endforeach

</feed>
