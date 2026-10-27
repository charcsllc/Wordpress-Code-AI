# wordpress-code-ai documentation

wordpress-code-ai turns your WordPress site into an MCP server, so AI clients such as Claude, ChatGPT and Cursor can work on the site with the permissions you give them. The server runs inside WordPress: requests go from your AI client to your site, with no service in between.

- [Install and update](#install-and-update)
- [Connect an AI client](#connect-an-ai-client)
- [Where things are](#where-things-are)
- [Google Search Console and Analytics](external-data.md)
- [Troubleshooting](troubleshooting.md): test from your browser, well-known addresses, trusted proxies
- [Get help](#get-help)

## Install and update

1. Download `wordpress-code-ai.zip` from the [latest release](https://github.com/charcsllc/Wordpress-Code-AI/releases/latest), under **Assets**. The **Source code** files that GitHub adds to every release hold the whole repository, not the plugin, and WordPress refuses them.
2. In WordPress, go to **Plugins → Add New Plugin → Upload Plugin**, choose the ZIP, click **Install Now**, then **Activate**.

New versions appear on the **Plugins** screen like any other plugin's: click **update now** under wordpress-code-ai. WordPress checks for them about twice a day; **Dashboard → Updates → Check again** checks at once. **Enable auto-updates** on the Plugins screen lets WordPress install them on its own, unless your host manages updates itself (Hostinger's Smart Auto Updates, for example, hides that link for every plugin).

Version 2.3.0.1 was the first to get updates this way. A site on 2.3.0 needs one manual update: upload the ZIP as above and click **Replace current with uploaded**. Settings, tokens and connections are kept.

## Connect an AI client

1. Open **wordpress-code-ai → Dashboard** and copy the MCP URL, or use one of the connect buttons.
2. Add the URL to your AI client.
   - Claude, ChatGPT, Cursor, VS Code and Antigravity sign in with **OAuth**: your WordPress login screen opens, you choose what the client may read or change, and click **Approve**.
   - Other clients use an **API token**: create one under **wordpress-code-ai → Connections → API tokens**, choose the user and the tools it may use, and paste the URL and the token into the client. The token is shown only once.

The [README](../README.md#connecting-clients) has the exact steps and configuration for each client.

## Where things are

| Screen | What is there |
|---|---|
| **Dashboard** | The MCP URL, the connect buttons, recent activity, and the **Diagnostics** card, with **Re-run**, **Copy system info** and **Test from your browser** |
| **Connections** | The AI clients connected with OAuth, and the API tokens |
| **Tools** | The tools AI clients can use: **Core** (WordPress), **Abilities** (tools that other plugins provide) and **External data** (Search Console, Analytics, Semrush and other services) |
| **Activity** | The audit log of every tool call, and the change history, which shows what each change replaced |
| **Settings** | **Access & safety** (rate limit, Force draft on create), **Logging & history** (how long the audit log and the change history are kept) and **Advanced** (switched-off tools, IP allow list) |

## Get help

- Report a problem or ask for a feature in [GitHub issues](https://github.com/charcsllc/Wordpress-Code-AI/issues), or write to info@charcs.net.
- Include the text from **Dashboard → Diagnostics → Copy system info**, and the result of **Test from your browser** if the problem is connecting.
