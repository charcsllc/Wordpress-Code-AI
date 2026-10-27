# wordpress-code-ai

> Connect Claude, ChatGPT, Cursor, Gemini, n8n, and any MCP-compatible AI to your WordPress site. Manage your entire site by chat — content, media, SEO, analytics, WooCommerce, and more. **280+ tools. 16 resources. Free.**

**Plugin page:** https://github.com/charcsllc/wordpress-code-ai  
**License:** GPL-2.0-or-later  
**Requires:** WordPress 6.0+, PHP 7.4+

---

> **The plugin is in this repository.**
> The plugin code is in the [`wordpress-code-ai/`](wordpress-code-ai/) folder, and [`wordpress-code-ai.zip`](wordpress-code-ai.zip) is the ready-to-install package.

---

## Table of Contents

- [What This Is](#what-this-is)
- [Installation](#installation)
- [Setup](#setup)
- [Connecting Clients](#connecting-clients)
  - [Claude Desktop](#claude-desktop-oauth--recommended)
  - [Claude Code (CLI)](#claude-code-cli)
  - [Cursor](#cursor)
  - [Windsurf](#windsurf)
  - [Cline / Roo Code](#cline--roo-code)
  - [ChatGPT (OpenAI)](#chatgpt-openai)
  - [Gemini CLI](#gemini-cli)
  - [n8n](#n8n)
  - [Manus](#manus)
- [Tools](#tools)
- [Resources](#resources)
- [Skills](#skills)
- [Security](#security)
- [Updating from Easy MCP AI](#updating-from-easy-mcp-ai)
- [Credits](#credits)
- [License](#license)

---

## What This Is

wordpress-code-ai exposes your WordPress site as a **remote MCP server** over HTTPS. Any MCP-compatible AI client — assistant or autonomous agent — connects directly to your site and gets scoped, capability-checked access to content, media, users, SEO data, analytics, and WooCommerce — all over the Model Context Protocol.

Install the plugin, generate a token, paste your endpoint URL into your AI client. Nothing else runs on your server. No Node.js, no proxy, no sidecar process.

---

## Installation

### Method 1 — ZIP Upload (Recommended)

1. Download [`wordpress-code-ai.zip`](wordpress-code-ai.zip) from this repository (open the file on GitHub and click **Download raw file**).
2. Go to **Plugins → Add New Plugin → Upload Plugin** in your WordPress admin.
3. Upload the ZIP and click **Install Now**, then **Activate**.

### Method 2 — Manual Copy

1. Copy the [`wordpress-code-ai/`](wordpress-code-ai/) folder into `wp-content/plugins/` on your server.
2. Go to **Plugins** in your WordPress admin and click **Activate** under **wordpress-code-ai**.

---

## Setup

### Step 1 — Find Your Endpoint URL

After activation, go to **wordpress-code-ai → Dashboard** in your WordPress admin. Your MCP endpoint is shown there:

```
https://${WP_SITE_URL}/wp-json/wordpress-code-ai/v1/mcp
```

### Step 2 — Choose a Connection Method

**Path A — OAuth (Recommended)**

Use this if your AI client supports OAuth (Claude Desktop, Claude.ai, Claude Code, Cursor, ChatGPT, Google Antigravity). No token needed.

1. Copy your endpoint URL from the Dashboard.
2. Open your AI client's MCP settings and paste the URL.
3. The client will redirect you to your WordPress site's OAuth consent screen.
4. Log in to WordPress, review the permission scopes, click **Approve**.
5. Done — the client receives a short-lived token that refreshes automatically.

Manage or revoke connected clients anytime under **wordpress-code-ai → Connections**, on the OAuth tab.

**Path B — Manual Token (Bearer)**

Use this if your client does not support OAuth (Windsurf, Cline, Roo Code, n8n, Gemini CLI, Manus).

1. Go to **wordpress-code-ai → Connections → API tokens** in your WordPress admin.
2. Click **Create New Token**.
3. Set a name (e.g. `Cursor — My Mac`).
4. Choose the WordPress user whose capabilities the token inherits (Administrator = full access).
5. Leave **Allowed Tools** blank for all tools, or select specific tools to restrict access.
6. Click **Create Token** and copy the value — it starts with `wpmcp_` and is shown only once.

### Step 3 — Connect Your AI Client

See the [Connecting Clients](#connecting-clients) section below and the `config/` folder for ready-to-use JSON configs.

---

## Connecting Clients

### Claude Desktop (OAuth — Recommended)

Claude Desktop supports OAuth 2.1 one-click connection. No token needed.

1. Go to **Settings → Connectors**.
2. Click **Add connector**.
3. Paste your MCP endpoint URL as the server URL.
4. Set the name to `WordPress` (or anything you like).
5. Click **Save** then **Connect** — OAuth is handled automatically, no token needed.

Or add manually to `claude_desktop_config.json` (see `config/claude-desktop.json`):

```json
{
  "mcpServers": {
    "wordpress": {
      "command": "npx",
      "args": [
        "mcp-remote",
        "https://${WP_SITE_URL}/wp-json/wordpress-code-ai/v1/mcp",
        "--header",
        "Authorization: Bearer ${WP_API_TOKEN}"
      ]
    }
  }
}
```

### Claude Code (CLI)

Claude Code supports OAuth. Add the server from the terminal, then run `/mcp` inside Claude Code and sign in to WordPress:

```bash
claude mcp add --transport http wordpress https://${WP_SITE_URL}/wp-json/wordpress-code-ai/v1/mcp
```

To use an API token instead, add the header:

```bash
claude mcp add --transport http wordpress https://${WP_SITE_URL}/wp-json/wordpress-code-ai/v1/mcp \
  --header "Authorization: Bearer ${WP_API_TOKEN}"
```

To share the server with everyone working on a project, add it to `.mcp.json` in the project root instead. Claude Code fills in `${WP_SITE_URL}` and `${WP_API_TOKEN}` from your environment, so the token stays out of the file:

```json
{
  "mcpServers": {
    "wordpress": {
      "type": "http",
      "url": "https://${WP_SITE_URL}/wp-json/wordpress-code-ai/v1/mcp",
      "headers": {
        "Authorization": "Bearer ${WP_API_TOKEN}"
      }
    }
  }
}
```

### Cursor

Add to `~/.cursor/mcp.json` (see `config/cursor.json`):

```json
{
  "mcpServers": {
    "wordpress": {
      "url": "https://${WP_SITE_URL}/wp-json/wordpress-code-ai/v1/mcp",
      "headers": {
        "Authorization": "Bearer ${WP_API_TOKEN}"
      }
    }
  }
}
```

Cursor also supports OAuth — paste your MCP URL in the Cursor MCP settings without a token to trigger the OAuth flow.

### Windsurf

Add to `~/.codeium/windsurf/mcp_config.json` (see `config/windsurf.json`):

```json
{
  "mcpServers": {
    "wordpress": {
      "serverUrl": "https://${WP_SITE_URL}/wp-json/wordpress-code-ai/v1/mcp",
      "headers": {
        "Authorization": "Bearer ${WP_API_TOKEN}"
      }
    }
  }
}
```

### Cline / Roo Code

Add via **Cline → MCP Servers → Add Server** or edit `cline_mcp_settings.json` (see `config/cline.json`):

```json
{
  "mcpServers": {
    "wordpress": {
      "autoApprove": [],
      "disabled": false,
      "transportType": "streamableHttp",
      "url": "https://${WP_SITE_URL}/wp-json/wordpress-code-ai/v1/mcp",
      "headers": {
        "Authorization": "Bearer ${WP_API_TOKEN}"
      }
    }
  }
}
```

### ChatGPT (OpenAI)

1. Go to **Settings → Apps → Advanced settings** and enable **Developer Mode**.
2. Go to **Create apps**, enter a name (e.g. `WordPress`), and paste your MCP endpoint URL.
3. Select **OAuth** as the authentication method.
4. Check **I trust this application** and click **Create**.
5. Complete the OAuth login flow — no token needed.

Or embed a token directly in the URL: `https://${WP_SITE_URL}/wp-json/wordpress-code-ai/v1/mcp/${WP_API_TOKEN}`

> Requires Pro, Plus, Business, Enterprise, or Education plan.

### Gemini CLI

Add to `~/.gemini/settings.json`:

```json
{
  "mcpServers": {
    "wordpress": {
      "httpUrl": "https://${WP_SITE_URL}/wp-json/wordpress-code-ai/v1/mcp",
      "headers": {
        "Authorization": "Bearer ${WP_API_TOKEN}"
      }
    }
  }
}
```

Use `httpUrl`, not `url`: Gemini CLI treats `url` as the older SSE transport.

Or from the terminal:
```bash
gemini mcp add wordpress https://${WP_SITE_URL}/wp-json/wordpress-code-ai/v1/mcp \
  --transport http --scope user \
  -H "Authorization: Bearer ${WP_API_TOKEN}"
```

### n8n

1. Add an **MCP Client** node to your workflow.
2. Set **Transport** to `Streamable HTTP`.
3. Set **URL** to `https://${WP_SITE_URL}/wp-json/wordpress-code-ai/v1/mcp`.
4. Add a **Header Auth** credential: name `Authorization`, value `Bearer ${WP_API_TOKEN}`.

### Manus

Go to **Connectors → Custom MCP** and fill in the form:

| Field | Value |
|---|---|
| Server Name | WordPress |
| Transport Type | HTTP |
| Server URL | `https://${WP_SITE_URL}/wp-json/wordpress-code-ai/v1/mcp` |
| Header name | `Authorization` |
| Header value | `Bearer ${WP_API_TOKEN}` |

---

## Tools

The plugin has more than 280 tools. About 130 of them work on any WordPress site; the rest turn on when WooCommerce, an SEO plugin or another supported integration is active. The tables below list the main ones. The full list for your site is under **wordpress-code-ai → Tools**.

### Posts (10)
| Tool | Description |
|---|---|
| `wp_list_posts` | List posts with filters for status, author, category, date range |
| `wp_get_post` | Get a post by ID |
| `wp_get_post_full` | Get a post with all meta and terms in one call |
| `wp_create_post` | Create a new post (supports scheduling, categories, tags, featured image) |
| `wp_update_post` | Update any post field |
| `wp_delete_post` | Trash or permanently delete a post |
| `wp_search_posts` | Full-text search across post content and title |
| `wp_count_posts` | Count posts grouped by status |
| `wp_replace_in_post` | Find and replace text inside post content |
| `wp_add_post_terms` | Add or replace terms on a post in any taxonomy |

### Pages (5)
| Tool | Description |
|---|---|
| `wp_list_pages` | List pages |
| `wp_get_page` | Get a page by ID |
| `wp_create_page` | Create a new page |
| `wp_update_page` | Update a page |
| `wp_delete_page` | Trash or delete a page |

### Media (7)
| Tool | Description |
|---|---|
| `wp_list_media` | List media library items |
| `wp_get_media` | Get a media item by ID |
| `wp_upload_media` | Upload a media file (base64) |
| `wp_upload_media_from_url` | Upload media directly from a remote URL |
| `wp_update_media` | Update media metadata (alt text, caption, title) |
| `wp_delete_media` | Delete a media item |
| `wp_count_media` | Count media items by type |

### Users (8)
| Tool | Description |
|---|---|
| `wp_list_users` | List users with role filtering |
| `wp_get_user` | Get a user by ID |
| `wp_create_user` | Create a new WordPress user |
| `wp_update_user` | Update user profile and role |
| `wp_delete_user` | Delete a user |
| `wp_get_user_meta` | Get user meta value |
| `wp_update_user_meta` | Update user meta value |
| `wp_delete_user_meta` | Delete user meta key |

### Comments (5)
| Tool | Description |
|---|---|
| `wp_list_comments` | List comments with status filtering |
| `wp_get_comment` | Get a comment by ID |
| `wp_create_comment` | Create a new comment |
| `wp_update_comment` | Update comment content or status (approve, hold, spam) |
| `wp_delete_comment` | Delete a comment |

### Taxonomy (18)
| Tool | Description |
|---|---|
| `wp_list_categories` | List categories |
| `wp_get_category` | Get a category by ID |
| `wp_create_category` | Create a category |
| `wp_update_category` | Update a category |
| `wp_delete_category` | Delete a category |
| `wp_list_tags` | List tags |
| `wp_get_tag` | Get a tag by ID |
| `wp_create_tag` | Create a tag |
| `wp_update_tag` | Update a tag |
| `wp_delete_tag` | Delete a tag |
| `wp_get_term` | Get a term in any taxonomy |
| `wp_create_term` | Create a term in any taxonomy |
| `wp_update_term` | Update a term |
| `wp_delete_term` | Delete a term |
| `wp_count_terms` | Count terms in a taxonomy |
| `wp_get_term_meta` | Get term meta |
| `wp_update_term_meta` | Set term meta |
| `wp_delete_term_meta` | Delete term meta |

### Menus (9)
| Tool | Description |
|---|---|
| `wp_list_menus` | List all navigation menus |
| `wp_get_menu` | Get a menu with its items |
| `wp_create_menu` | Create a navigation menu |
| `wp_update_menu` | Update a menu |
| `wp_delete_menu` | Delete a menu |
| `wp_list_menu_items` | List items in a menu |
| `wp_create_menu_item` | Add an item to a menu |
| `wp_update_menu_item` | Update a menu item |
| `wp_delete_menu_item` | Remove an item from a menu |

### Post Meta (3)
| Tool | Description |
|---|---|
| `wp_get_post_meta` | Get a post meta value |
| `wp_update_post_meta` | Set a post meta value |
| `wp_delete_post_meta` | Delete a post meta key |

### Site Settings (5)
| Tool | Description |
|---|---|
| `wp_get_site_settings` | Get site name, URL, tagline, timezone, date/time format |
| `wp_update_site_settings` | Update site settings |
| `wp_get_post_types` | List all registered post types |
| `wp_get_taxonomies` | List all registered taxonomies |
| `wp_get_post_statuses` | List all available post statuses |

### Blocks — Gutenberg (5)
| Tool | Description |
|---|---|
| `wp_list_blocks` | List reusable blocks |
| `wp_get_block` | Get a reusable block by ID |
| `wp_create_block` | Create a reusable block |
| `wp_update_block` | Update a reusable block |
| `wp_delete_block` | Delete a reusable block |

### Global Styles — FSE (2)
| Tool | Description |
|---|---|
| `wp_get_global_styles` | Get active theme's global styles (FSE) |
| `wp_update_global_styles` | Update global styles |

### Templates — FSE (3)
| Tool | Description |
|---|---|
| `wp_list_templates` | List block templates |
| `wp_get_template` | Get a template by ID |
| `wp_update_template` | Update a template |

### Themes (2)
| Tool | Description |
|---|---|
| `wp_list_themes` | List installed themes |
| `wp_get_active_theme` | Get the currently active theme |

### Plugins (1)
| Tool | Description |
|---|---|
| `wp_list_plugins` | List installed plugins with status and version |

### Revisions (4)
| Tool | Description |
|---|---|
| `wp_list_revisions` | List post revisions |
| `wp_get_revision` | Get a revision by ID |
| `wp_restore_revision` | Restore a post to a previous revision |
| `wp_delete_revision` | Delete a revision |

### Search (1)
| Tool | Description |
|---|---|
| `wp_search` | Global site search across all content types |

### Custom Post Types (5)
| Tool | Description |
|---|---|
| `wp_list_cpt_items` | List items of any custom post type |
| `wp_get_cpt_item` | Get a CPT item by ID |
| `wp_create_cpt_item` | Create a CPT item |
| `wp_update_cpt_item` | Update a CPT item |
| `wp_delete_cpt_item` | Delete a CPT item |

### Change History (3)
| Tool | Description |
|---|---|
| `wp_history_list` | List MCP-originated write history with filters |
| `wp_history_get` | Get a single history entry with before/after payloads |
| `wp_history_diff` | Diff two history entries or an entry against current live state |

### WooCommerce (46)
| Tool | Description |
|---|---|
| `wp_wc_list_products` | List products |
| `wp_wc_get_product` | Get a product by ID |
| `wp_wc_create_product` | Create a product |
| `wp_wc_update_product` | Update a product |
| `wp_wc_delete_product` | Delete a product |
| `wp_wc_batch_update_products` | Batch create/update/delete products |
| `wp_wc_list_product_variations` | List product variations |
| `wp_wc_get_product_variation` | Get a variation |
| `wp_wc_create_product_variation` | Create a variation |
| `wp_wc_update_product_variation` | Update a variation |
| `wp_wc_delete_product_variation` | Delete a variation |
| `wp_wc_batch_update_variations` | Batch update variations |
| `wp_wc_list_product_attributes` | List product attributes |
| `wp_wc_create_product_attribute` | Create a product attribute |
| `wp_wc_set_product_attributes` | Set attributes on a product |
| `wp_wc_list_product_categories` | List product categories |
| `wp_wc_list_orders` | List orders |
| `wp_wc_get_order` | Get an order by ID |
| `wp_wc_create_order` | Create an order |
| `wp_wc_update_order` | Update an order |
| `wp_wc_batch_update_orders` | Batch update orders |
| `wp_wc_list_order_notes` | List order notes |
| `wp_wc_create_order_note` | Add a note to an order |
| `wp_wc_list_order_refunds` | List order refunds |
| `wp_wc_list_customers` | List customers |
| `wp_wc_get_customer` | Get a customer by ID |
| `wp_wc_create_customer` | Create a customer |
| `wp_wc_update_customer` | Update a customer |
| `wp_wc_delete_customer` | Delete a customer |
| `wp_wc_list_coupons` | List coupons |
| `wp_wc_create_coupon` | Create a coupon |
| `wp_wc_update_coupon` | Update a coupon |
| `wp_wc_delete_coupon` | Delete a coupon |
| `wp_wc_list_webhooks` | List webhooks |
| `wp_wc_create_webhook` | Create a webhook |
| `wp_wc_update_webhook` | Update a webhook |
| `wp_wc_delete_webhook` | Delete a webhook |
| `wp_wc_list_shipping_zones` | List shipping zones |
| `wp_wc_list_shipping_methods` | List shipping methods |
| `wp_wc_list_tax_rates` | List tax rates |
| `wp_wc_list_payment_gateways` | List payment gateways |
| `wp_wc_report_sales` | Sales summary report |
| `wp_wc_report_top_sellers` | Top-selling products report |
| `wp_wc_report_orders` | Orders report |
| `wp_wc_report_products` | Products report |
| `wp_wc_report_customers` | Customers report |

### Advanced Custom Fields — ACF (6)
| Tool | Description |
|---|---|
| `wp_acf_list_field_groups` | List ACF field groups |
| `wp_acf_get_fields` | Get ACF field values on a post |
| `wp_acf_update_fields` | Update ACF field values on a post |
| `wp_acf_get_user_fields` | Get ACF field values on a user |
| `wp_acf_update_user_fields` | Update ACF field values on a user |
| `wp_acf_get_term_fields` | Get ACF field values on a taxonomy term |

### The Events Calendar (10)
| Tool | Description |
|---|---|
| `wp_tec_list_events` | List events |
| `wp_tec_get_event` | Get an event by ID |
| `wp_tec_create_event` | Create an event |
| `wp_tec_update_event` | Update an event |
| `wp_tec_delete_event` | Delete an event |
| `wp_tec_list_venues` | List venues |
| `wp_tec_get_venue` | Get a venue |
| `wp_tec_create_venue` | Create a venue |
| `wp_tec_list_organizers` | List organizers |
| `wp_tec_create_organizer` | Create an organizer |

### BuddyPress (10)
| Tool | Description |
|---|---|
| `wp_bp_list_members` | List BuddyPress members |
| `wp_bp_get_member` | Get a member by ID |
| `wp_bp_list_groups` | List groups |
| `wp_bp_get_group` | Get a group by ID |
| `wp_bp_list_group_members` | List members of a group |
| `wp_bp_list_activity` | List activity stream posts |
| `wp_bp_create_activity` | Create an activity post |
| `wp_bp_delete_activity` | Delete an activity post |
| `wp_bp_list_message_threads` | List private message threads |
| `wp_bp_get_message_thread` | Get a message thread |

### SEO Plugins (8)
| Tool | Description |
|---|---|
| `wp_yoast_get_post_seo` | Get Yoast SEO meta for a post |
| `wp_yoast_update_post_seo` | Update Yoast SEO title, description, OG, focus keyword, cornerstone flag |
| `wp_yoast_get_head` | Get rendered Yoast `<head>` tags for a URL |
| `wp_rm_get_post_seo` | Get Rank Math SEO meta for a post |
| `wp_rm_update_post_seo` | Update Rank Math SEO title, description, canonical, focus keyword |
| `wp_rm_get_head` | Get rendered Rank Math `<head>` tags for a URL |
| `wp_aioseo_get_post_seo` | Get All in One SEO meta for a post |
| `wp_aioseo_update_post_seo` | Update AIOSEO title, description, OG, no-index |

### Google Analytics 4 (11)
| Tool | Description |
|---|---|
| `wp_ga_list_account_summaries` | List GA4 accounts and properties |
| `wp_ga_get_property` | Get a GA4 property |
| `wp_ga_get_metadata` | Get property metadata (dimensions, metrics) |
| `wp_ga_list_data_streams` | List data streams |
| `wp_ga_list_custom_dimensions` | List custom dimensions |
| `wp_ga_list_custom_metrics` | List custom metrics |
| `wp_ga_list_conversion_events` | List conversion events |
| `wp_ga_run_report` | Run a GA4 report (traffic, pages, conversions, etc.) |
| `wp_ga_run_realtime_report` | Run a real-time GA4 report (active users, pages) |
| `wp_ga_run_pivot_report` | Run a pivot report |
| `wp_ga_check_compatibility` | Check dimension/metric compatibility |

### Google Search Console (6)
| Tool | Description |
|---|---|
| `wp_gsc_list_sites` | List verified Search Console properties |
| `wp_gsc_get_site` | Get a site property |
| `wp_gsc_query_performance` | Query search performance (clicks, impressions, CTR, position) |
| `wp_gsc_list_sitemaps` | List submitted sitemaps |
| `wp_gsc_get_sitemap` | Get sitemap details |
| `wp_gsc_inspect_url` | Inspect URL indexing status |

### Semrush (13)
| Tool | Description |
|---|---|
| `wp_semrush_domain_overview` | Domain authority, organic traffic estimate, keyword count |
| `wp_semrush_domain_organic_keywords` | Keywords a domain ranks for |
| `wp_semrush_url_organic_keywords` | Keywords a specific page ranks for |
| `wp_semrush_competitors_organic` | Organic competitor domains |
| `wp_semrush_keyword_overview` | Search volume, CPC, competition for a keyword |
| `wp_semrush_keyword_difficulty` | Keyword difficulty score (0–100) |
| `wp_semrush_related_keywords` | Semantically related keywords |
| `wp_semrush_phrase_questions` | Question-style keyword variations |
| `wp_semrush_backlinks_overview` | Aggregate backlink summary |
| `wp_semrush_backlinks` | Individual backlinks list |
| `wp_semrush_referring_domains` | Referring domains list |
| `wp_semrush_anchors` | Anchor text distribution |
| `wp_semrush_api_units_balance` | Remaining Semrush API unit balance |

### DataforSEO (8)
| Tool | Description |
|---|---|
| `wp_dfs_serp_google_organic_live` | Live Google organic SERP results |
| `wp_dfs_keywords_search_volume_live` | Keyword search volumes |
| `wp_dfs_labs_keywords_for_site_live` | Keywords a site ranks for |
| `wp_dfs_labs_ranked_keywords_live` | Ranked keyword data for a domain |
| `wp_dfs_backlinks_summary_live` | Backlink summary for a target |
| `wp_dfs_backlinks_referring_domains_live` | Referring domains list |
| `wp_dfs_on_page_instant_pages` | On-page SEO audit for any URL |
| `wp_dfs_account_balance` | Remaining DataforSEO account balance |

> **WordPress 6.9+ Abilities:** The plugin also auto-discovers any plugin that registers WordPress Abilities API capabilities and exposes them as additional `wp_ability_*` tools at runtime — no configuration needed.

---

## Resources

On a standard site the server exposes 16 read-only MCP resources to any connected AI client:

| URI | Name | Description |
|---|---|---|
| `wp://site/info` | Site Info | Site name, URL, description, version, timezone, language |
| `wp://site/stats` | Site Stats | Counts of posts, pages, comments, users, categories, tags |
| `wp://site/post-types` | Post Types | All public post types with labels, REST bases, supported features |
| `wp://site/reading-settings` | Reading Settings | Homepage display type, front/posts page IDs, posts per page |
| `wp://site/discussion-settings` | Discussion Settings | Comment status, moderation rules, registration requirements |
| `wp://site/active-plugins` | Active Plugins | All active plugins with name, version, description |
| `wp://site/theme-templates` | Theme Templates | Available block templates and template parts from the active theme |
| `wp://posts/recent` | Recent Posts | 10 most recently published posts with title, date, excerpt, link |
| `wp://posts/drafts` | Draft Posts | Up to 50 most recently modified draft posts |
| `wp://posts/scheduled` | Scheduled Posts | All posts scheduled for future publication |
| `wp://media/recent` | Recent Media | 20 most recently uploaded media items with URLs, MIME types, alt text |
| `wp://users/authors` | Authors | All users who can create posts, with display names, logins, roles |
| `wp://menus/all` | Menus | All registered navigation menus with assigned theme locations |
| `wp://taxonomies/all` | Taxonomies | All categories and tags with IDs, names, slugs, parent relationships, post counts |
| `skill://wordpress/site-health-review/SKILL.md` | Site health review | Step-by-step instructions for a common task; one resource per installed skill |
| `ui://wordpress-code-ai/approval` | Approval card | Approve or deny a paused destructive tool call in the chat |

---

## Skills

The `skills/wordpress-site-manager.md` file contains a ready-to-use Claude skill for full WordPress site management. Drop it in your Claude project to give the AI persistent context about your site — it knows which tools to use for common workflows like publishing content, managing WooCommerce orders, bulk-editing SEO metadata, and querying analytics without needing step-by-step instructions each time.

---

## Security

- **Hashed tokens:** The raw token value is never persisted — only its SHA-256 hash is stored.
- **WordPress capability gate:** Each tool declares a minimum WordPress capability; the plugin verifies it against the user bound to the token before executing anything.
- **Scoped access:** Tokens can be locked to a subset of tools via glob patterns (e.g. `wp_list_*`, `wp_get_*`), so a read-only AI client can never trigger writes.
- **Rate limiting:** Configurable per-token call rate (default 60/minute).
- **Audit log:** A timestamped record of every tool call — arguments, result, token used, and client IP — is kept under **wordpress-code-ai → Activity**.
- **Change snapshots:** Write operations store before/after JSON payloads, queryable later via the `wp_history_*` tools.
- **OAuth 2.1:** Full PKCE S256 flow with rotating refresh tokens, per-scope consent, and RFC 7009 token revocation.
- **Force Draft:** A global switch that pins all AI-created content to `draft` status, regardless of what the AI requests.

---

## Updating from Easy MCP AI

wordpress-code-ai does not receive Easy MCP AI's updates, including security fixes, on its own. To bring in a new Easy MCP AI release:

1. Download the new Easy MCP AI ZIP and unzip it.
2. Delete everything inside `wordpress-code-ai/`, then copy in the contents of the unzipped `easy-mcp-ai/` folder.
3. Run `python3 tools/rename-upstream.py wordpress-code-ai` (on Windows, `py tools\rename-upstream.py wordpress-code-ai`). It renames the plugin, removes the Themeisle SDK and adds the credit. If a new release changed a line the script edits, it stops without writing anything and names that line.
4. Run `python3 tools/update-skills-mirror.py --from-plugin wordpress-code-ai` to copy the skills release bundled with the new version into `skills-feed/` (see below).
5. Commit, then rebuild the ZIP: `git archive --format=zip -o wordpress-code-ai.zip HEAD wordpress-code-ai`, and commit the ZIP.

### Skill updates

Once a day the plugin downloads its skills (step-by-step instructions for common tasks) from [`skills-feed/`](skills-feed/) in this repository, not from the original vendor's server. These files are byte-for-byte copies of Easy MCP AI's signed skill releases: the plugin checks the original signature and rejects any file that was edited.

To publish a newer skills release from Easy MCP AI, run `python3 tools/update-skills-mirror.py` (it downloads the newest release from skills.easymcpai.com and checks it), then commit `skills-feed/`. Sites pick it up within a day.

The download address contains this repository's exact name (`charcsllc/Wordpress-Code-AI`) because the plugin follows no redirects. If you rename the repository, update `SKILLS_FEED` in `tools/rename-upstream.py` and run the steps above again.

---

## Credits

wordpress-code-ai is a modified version of [Easy MCP AI](https://easymcpai.com) 2.3.0 by EasyMCPAI, used under the GNU General Public License v2 or later. Changes: the plugin is renamed, the bundled Themeisle SDK and the feedback reports sent to Themeisle are removed, and support reports go to info@charcs.net.

---

## License

GPL-2.0-or-later — see `LICENSE`.
