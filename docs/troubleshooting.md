# Troubleshooting

- [Diagnostics and Test from your browser](#diagnostics-and-test-from-your-browser)
- [Well-known addresses](#well-known-addresses)
- [Trusted proxies](#trusted-proxies)

## Diagnostics and Test from your browser

**wordpress-code-ai → Dashboard → Diagnostics** checks the plugin and the connection from the server itself: authentication headers, database tables, tool visibility, caching and security plugins, and the sign-in documents. Each problem it finds comes with what to do. Press **Re-run** after a fix.

Its **Test from your browser** button opens a page where your own browser asks the site for the addresses an AI client uses, without your WordPress login. Those requests travel over the internet and through your CDN and firewall like any visitor's, so they can find blocks that the server cannot see from inside. The page contacts only your site. Press **Copy result** to include it when you ask for help.

The test runs from your network, so it cannot see blocks that only affect the servers of AI providers, such as a CDN setting that blocks AI bots or a rule that refuses data centre addresses. If Claude or ChatGPT cannot connect while every check passes, look for such a setting at your CDN or host.

The two sections below explain the fixes that need your host.

## Well-known addresses

AI clients find where to sign in by reading two documents at fixed addresses at the root of your domain:

- `https://example.com/.well-known/oauth-protected-resource`
- `https://example.com/.well-known/oauth-authorization-server`

The plugin answers both, but some hosts handle every address that starts with `/.well-known/` themselves, or refuse it, before WordPress runs. A server rule that refuses every path containing `/.`, common in nginx configurations, blocks them too.

- When only the addresses at the domain root are blocked, the plugin points AI clients to copies under its own connection address. Claude and ChatGPT keep working, but other clients may not.
- When the copies under the connection address are blocked as well, ChatGPT and Codex cannot connect.

To fix it:

1. Ask your host to let `/.well-known/` reach WordPress, or at least the addresses that start with `/.well-known/oauth-`. On nginx, a block like this one, placed in the site's `server` block, sends them to WordPress before any rule that refuses `/.`:

   ```nginx
   location ^~ /.well-known/oauth- {
       try_files $uri /index.php?$args;
   }
   ```

2. If your host will not change the rule, they can save both documents as ordinary files in the `.well-known` folder of your site instead. The plugin always serves the current version at `https://example.com/wp-json/wordpress-code-ai/v1/discovery/oauth-protected-resource` and `https://example.com/wp-json/wordpress-code-ai/v1/discovery/oauth-authorization-server`. Copy each one again after you change the site address; Diagnostics says when a copy is out of date.

## Trusted proxies

Behind a reverse proxy, a load balancer or a CDN such as Cloudflare, WordPress sees the proxy's address on every request. The IP allow list, the per-token rate limit and the Activity log then cannot tell visitors apart, and Diagnostics reports it under **Client IP resolves per visitor**.

The best fix is on the server, because it corrects WordPress and every other plugin at once: `mod_remoteip` on Apache, or `set_real_ip_from` and `real_ip_header` on nginx. Otherwise, tell the plugin which proxies to trust in `wp-config.php`:

```php
define( 'WORDPRESS_CODE_AI_TRUSTED_PROXIES', '173.245.48.0/20, 103.21.244.0/22' );
define( 'WORDPRESS_CODE_AI_CLIENT_IP_HEADER', 'CF-Connecting-IP' );
```

- `WORDPRESS_CODE_AI_TRUSTED_PROXIES` lists the proxies' addresses, as IP addresses or CIDR ranges separated by commas, spaces or new lines. The plugin reads the visitor's address from a header only on requests that come from one of them. The example shows two of Cloudflare's ranges; use the full list Cloudflare publishes at https://www.cloudflare.com/ips/.
- `WORDPRESS_CODE_AI_CLIENT_IP_HEADER` names the header the proxy puts the visitor's address in: `X-Forwarded-For` (the default), `CF-Connecting-IP` (Cloudflare), `X-Real-IP` or `True-Client-IP`. With `X-Forwarded-For`, the plugin reads the list from right to left and skips the trusted proxies.

The `wordpress_code_ai_client_ip` filter can also set the address.
