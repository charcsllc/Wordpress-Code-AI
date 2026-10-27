# Google Search Console and Analytics

With Search Console and Google Analytics 4 connected, your AI client can answer questions such as which searches bring visitors to each page, which pages are losing positions, or how many sessions a page had last month. The tools are named `wp_gsc_*` and `wp_ga_*`.

The plugin reads both with a Google Cloud **service account**: a Google identity for the site, with its own email address, that you give read access to. It takes about 15 minutes, once.

## 1. Create the service account

1. Open https://console.cloud.google.com with the Google account that manages your Search Console and Analytics, and create a project, for example `my-site-wordpress`.
2. Under **APIs & Services → Library**, find and **Enable** these three APIs:
   - Google Search Console API
   - Google Analytics Data API
   - Google Analytics Admin API
3. Under **IAM & Admin → Service accounts**, click **Create service account**. Any name will do, such as `wordpress-code-ai`. Give it no roles: click **Create**, then **Done**.
4. Open the service account and go to **Keys → Add key → Create new key → JSON**. A `.json` file downloads. Keep it like a password: anyone with it can read what the account can read.
5. Copy the service account's email address. It ends in `iam.gserviceaccount.com`.

If Google says that an organization policy stops you from creating keys, the Google account belongs to an organization whose administrator has to allow it.

## 2. Give it read access

- **Search Console:** go to **Settings → Users and permissions → Add user**, paste the email address and choose **Restricted**.
- **Google Analytics:** go to **Admin → Property access management → + → Add users**, paste the email address and choose **Viewer**. Choose **Editor** only if you want the AI to change key events: the two key-event tools need it.

## 3. Connect it in WordPress

1. Go to **wordpress-code-ai → Tools → External data**.
2. Under **Google Search Console**, paste the contents of the JSON file. As the site, enter your address with `https://` and the final slash, such as `https://example.com/`, or `sc-domain:example.com` if your Search Console property is a domain property.
3. Under **Google Analytics**, paste the same JSON and the **property ID**. It is the number under **Admin → Property details**, such as `123456789`, not the measurement ID that starts with `G-`.
4. Save and use the test button. If your AI client was already connected, reconnect it so it sees the new tools.

## Other services

Semrush, SE Ranking, DataForSEO and Ahrefs connect on the same screen with an API key from your account with each service. Their charges come from those services; the plugin adds none. A service is contacted only when you save or test its key, when the External data tab loads its account details, or when an AI client uses one of its tools.
