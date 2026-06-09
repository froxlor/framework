@php use Froxlor\Web\Enums\SslMode; @endphp
@foreach (['http', 'https'] as $mode)
    @if ($mode == 'https' && !$domainVhost->domainSslVhost()->exists())
        @continue
    @endif

        <?php $bindPort = ""; ?>
    @foreach ($domainVhost->nodeInterfaces as $listenIf)
        {{-- check for valid ports --}}
        @if (empty($listenIf->bind_addr) or ($mode == 'http' && $listenIf->pivot->ssl_port == true) or ($mode == 'https' && $listenIf->pivot->ssl_port == false))
            @continue
        @endif
        {{-- create listen-statements --}}
            <?php $bindPort .= $listenIf->bind_addr . ":" . $listenIf->pivot->port . " "; ?>
        # Listen {{ $listenIf->bind_addr }}:{{ $listenIf->pivot->port }}
    @endforeach

    {{-- create vhost container --}}
    # {{ $mode }} vhost for domain {{ $domainVhost->domain->domain }}
    <VirtualHost {{ trim($bindPort) }}>
        ServerName {{ $domainVhost->domain->domain }}

        @if ($mode == 'http' && $domainVhost->domainSslVhost()->exists() && $domainVhost->domainSslVhost->ssl_redirect)
            <IfModule mod_rewrite.c>
                RewriteEngine On
                RewriteCond %{HTTPS} off
                @if ($domainVhost->domainSslVhost->ssl_mode == SslMode::Auto->value)
                    RewriteCond %{REQUEST_URI} !^/.well-known/acme-challenge/
                @endif
                RewriteRule ^.*$ https://%{HTTP_HOST}%{REQUEST_URI} [R=301,QSA,L]
            </IfModule>
            <IfModule !mod_rewrite.c>
                Redirect permanent / https://{{ $domainVhost->domain->domain }}/
            </IfModule>
        @else
            DocumentRoot "{{ $domainVhost->documentroot }}"
            <Directory "{{ $domainVhost->documentroot }}">
            # ...
            </Directory>
        @endif
    </VirtualHost>
@endforeach
