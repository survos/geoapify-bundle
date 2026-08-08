# Geoapify Bundle

Symfony Bundle to access the API at https://www.geoapify.com/ 

The geoapify website offers 3000 free lookups per day, and it's very fast and easy to register for an API key.  The main purpose of this bundle is to simplify storing the API key in an environment variable and to cache the responses.

First, get an API key at https://myprojects.geoapify.com/projects and add it to .env.local

```bash
echo "GEOAPIFY_API_KEY=my-api-key" >> .env.local
composer req survos/geoapify-bundle
bin/console debug:config survos_geoapify --format=yaml > config/packages/survos_geoapify.yaml

```

```yaml
# config/packages/survos_geoapify.yaml
survos_geoapify:
  api_key: '%env(GEOAPIFY_API_KEY)%'
```

## Fetch strategy (cache/retry)

`GeoapifyService` fetches through `survos/fetch-bundle`'s `PersistentFetcherInterface`, not a
raw `HttpClientInterface`. If a lookup looks stale or stuck, this is where to look:

- **Cache:** keyed by the full request URL (including `apiKey`), cached **forever** — until
  the app calls `forget($url)` or passes `force_fetch: true` — in a SQLite pool at
  `var/data/fetch_cache.db` (survives `cache:clear` and process restarts). There is no TTL by
  default, so a bad/empty response for a given lat/lng or query string will keep being served
  until explicitly forgotten.
- **Retry:** up to 5 attempts, full-jitter exponential backoff (200ms base, 10s cap), on
  transport errors, HTTP 429, and 5xx — see `ExponentialBackoffRetry` in fetch-bundle.
- **Local `.wip` hosts:** routed through the Symfony CLI local proxy automatically (not
  applicable to Geoapify's real endpoint, but shared by every fetch-bundle consumer).

Before 2026-08-08 this bundle had its own `CacheInterface`/scoped-HTTP-client wiring, but the
cache reference was never actually connected — every lookup was silently uncached. See
[survos/mono@ec4f9a06](https://github.com/survos/mono/commit/ec4f9a06).

## Trivial but functional application

Requirements:

* Locally installed PHP 8
* Symfony CLI
* sed (to change /app to / without opening an editor)
* API Key 

```bash
symfony new GeoapifyDemo --webapp && cd GeoapifyDemo
echo "GEOAPIFY_API_KEY=my-api-key" >> .env.local
symfony composer req survos/geoapify-bundle
symfony console make:controller AppController
sed -i "s|/app|/|" src/Controller/AppController.php 

cat <<'EOF' > templates/app/index.html.twig
{% extends 'base.html.twig' %}
{% block body %}
{% set ip = app.request.clientIp %}
{{ isLocalhost(ip) ? "<div>Localhost has no geolocation, using value from config</div>" }}
Hello, visitor from {{ ipGeolocation(ip).country_name}} )
<pre>{{ ipGeolocation(ip)|json_encode(constant('JSON_PRETTY_PRINT')) }}</pre>

Powered by Geoapify.com <a href="https://www.geoapify.com">IP geolocation</a> web service.

{% endblock %}
EOF

symfony server:start -d
symfony open:local
```

## Notes

https://freeipapi.com/ is a free service that can be used without an API key (up to 60 requests per minute).  
