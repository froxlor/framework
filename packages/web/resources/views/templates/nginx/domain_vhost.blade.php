@php use Froxlor\Web\Enums\SslMode; @endphp
@foreach (['http', 'https'] as $mode)
    @if ($mode == 'https' && !$domainVhost->domainSslVhost()->exists())
        @continue
    @endif

server {
    @foreach ($domainVhost->nodeInterfaces as $listenIf)
        @if (empty($listenIf->bind_addr) or ($mode == 'http' && $listenIf->pivot->ssl_port == true) or ($mode == 'https' && $listenIf->pivot->ssl_port == false))
            @continue
        @endif
    listen {{ $listenIf->bind_addr }}:{{ $listenIf->pivot->port }};
    @endforeach

    server_name {{ $domainVhost->domain->domain }};
    root {{ $domainVhost->documentroot }};

    @if ($mode == 'http' && $domainVhost->domainSslVhost()->exists() && $domainVhost->domainSslVhost->ssl_redirect)
    location / {
        return 301 https://$host$request_uri;
    }
    @else
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    @endif
}
@endforeach
