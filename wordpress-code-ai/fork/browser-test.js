(function () {
    'use strict';

    var config = window.wordpressCodeAiBrowserTest;
    var rows = document.getElementById('wcai-browser-test-rows');
    var runButton = document.getElementById('wcai-browser-test-run');
    var copyButton = document.getElementById('wcai-browser-test-copy');
    var status = document.getElementById('wcai-browser-test-status');
    if (!config || !rows || !runButton || !copyButton || !status || !window.fetch) {
        return;
    }
    var s = config.strings;
    var results = [];

    // Fills in %s, or %1$s, %2$s…, in a translated string.
    function format(text) {
        var values = Array.prototype.slice.call(arguments, 1);
        var next = 0;
        return text.replace(/%(?:(\d+)\$)?s/g, function (match, position) {
            var value = position ? values[position - 1] : values[next++];
            return value === undefined ? match : String(value);
        });
    }

    function trimSlash(url) {
        return String(url || '').replace(/\/+$/, '');
    }

    function crossOrigin(url) {
        try {
            return new URL(url, window.location.href).origin !== window.location.origin;
        } catch (e) {
            return true;
        }
    }

    // Asks the site the way an AI client does: no cookies, nothing cached.
    function request(url, options) {
        var init = { method: 'GET', credentials: 'omit', cache: 'no-store', redirect: 'follow', headers: { Accept: 'application/json' } };
        Object.keys(options || {}).forEach(function (key) {
            init[key] = options[key];
        });
        return fetch(url, init).then(function (response) {
            return response.text().then(function (text) {
                var json = null;
                try {
                    json = JSON.parse(text);
                } catch (e) {
                    json = null;
                }
                return {
                    status: response.status,
                    type: (response.headers.get('content-type') || '').split(';')[0].trim().toLowerCase(),
                    auth: response.headers.get('www-authenticate'),
                    redirected: response.redirected,
                    finalUrl: response.url,
                    json: json && typeof json === 'object' ? json : null
                };
            });
        }, function (error) {
            return { error: String(error && error.message ? error.message : error) };
        });
    }

    function seen(response) {
        if (response.error) {
            return response.error;
        }
        var parts = ['HTTP ' + response.status];
        if (response.type) {
            parts.push(response.type);
        }
        if (response.redirected) {
            parts.push(format(s.redirected, response.finalUrl));
        }
        if (response.auth) {
            parts.push('WWW-Authenticate: ' + response.auth);
        }
        return parts.join(' · ');
    }

    // What most likely answered instead of the plugin.
    function problem(response, url) {
        if (response.error) {
            return format(s.unreachable, response.error);
        }
        var refused = response.type === 'text/html' || response.status === 403 || response.status === 404;
        if (refused && url.indexOf('/.well-known/') !== -1) {
            return s.wellKnown;
        }
        if (response.type === 'text/html') {
            return s.htmlPage;
        }
        if (response.status === 401 || response.status === 403) {
            return format(s.refused, response.status);
        }
        if (response.status === 404) {
            return s.notFound;
        }
        if (response.status === 503) {
            return s.unavailable;
        }
        if (response.status >= 500) {
            return format(s.serverError, response.status);
        }
        return format(s.unexpected, response.status);
    }

    function passed(response, note) {
        return response.redirected ? { status: 'warn', note: s.redirectNote } : { status: 'pass', note: note };
    }

    function checkEndpoint() {
        var url = config.endpoint;
        var body = {
            jsonrpc: '2.0',
            id: 1,
            method: 'initialize',
            params: { protocolVersion: '2025-06-18', capabilities: {}, clientInfo: { name: 'wordpress-code-ai-browser-test', version: config.version } }
        };
        return request(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json, text/event-stream' },
            body: JSON.stringify(body)
        }).then(function (response) {
            var result = { label: s.endpointLabel, url: url, seen: seen(response) };
            var metadata = response.auth ? /resource_metadata="([^"]+)"/.exec(response.auth) : null;
            var json = response.type === 'application/json';
            if (response.status === 401 && json && metadata) {
                result.metadata = metadata[1];
                return Object.assign(result, passed(response, s.endpointPass));
            }
            if (response.status === 401 && json && !response.auth && crossOrigin(url)) {
                return Object.assign(result, { status: 'unknown', note: s.headerHidden });
            }
            if (response.status === 401 && json) {
                return Object.assign(result, { status: 'fail', note: s.noChallenge });
            }
            if (response.status >= 200 && response.status < 300 && json) {
                return Object.assign(result, { status: 'fail', note: s.noSignIn });
            }
            return Object.assign(result, { status: 'fail', note: problem(response, url) });
        });
    }

    function checkResource(url) {
        return request(url).then(function (response) {
            var result = { label: s.resourceLabel, url: url, seen: seen(response) };
            var doc = response.json;
            if (response.status !== 200 || !doc) {
                return Object.assign(result, { status: 'fail', note: problem(response, url) });
            }
            if (trimSlash(doc.resource) !== trimSlash(config.endpoint)) {
                return Object.assign(result, { status: 'fail', note: format(s.otherResource, doc.resource) });
            }
            var servers = doc.authorization_servers;
            if (!Array.isArray(servers) || typeof servers[0] !== 'string') {
                return Object.assign(result, { status: 'fail', note: s.noServer });
            }
            result.issuer = servers[0];
            return Object.assign(result, passed(response, s.resourcePass));
        });
    }

    // The addresses AI clients try for the sign-in server's document, in order.
    function serverDocuments(issuer) {
        var parsed;
        try {
            parsed = new URL(issuer);
        } catch (e) {
            return [];
        }
        var path = parsed.pathname.replace(/\/+$/, '');
        if (!path) {
            return [parsed.origin + '/.well-known/oauth-authorization-server', parsed.origin + '/.well-known/openid-configuration'];
        }
        return [
            parsed.origin + '/.well-known/oauth-authorization-server' + path,
            parsed.origin + '/.well-known/openid-configuration' + path,
            parsed.origin + path + '/.well-known/openid-configuration'
        ];
    }

    function checkServer(issuer, guessed) {
        var urls = serverDocuments(issuer);
        var tried = [];
        var blocked = false;
        function next(index) {
            if (index >= urls.length) {
                var note = blocked ? s.serverMissing + ' ' + s.wellKnown : s.serverMissing;
                return { label: s.serverLabel, url: urls.length ? urls[0] : issuer, status: 'fail', seen: tried.join('\n'), note: note };
            }
            return request(urls[index]).then(function (response) {
                var doc = response.json;
                if (response.status === 200 && doc && trimSlash(doc.issuer) === trimSlash(issuer)
                    && typeof doc.authorization_endpoint === 'string' && typeof doc.token_endpoint === 'string') {
                    var result = { label: s.serverLabel, url: urls[index], seen: tried.concat(seen(response)).join('\n') };
                    Object.assign(result, passed(response, s.serverPass));
                    if (guessed) {
                        result.status = 'warn';
                        result.note = s.guessedServer;
                    }
                    return result;
                }
                tried.push(urls[index] + ': ' + seen(response));
                blocked = problem(response, urls[index]) === s.wellKnown;
                return next(index + 1);
            });
        }
        return next(0);
    }

    // Only needed when the connection address points AI clients somewhere else.
    function checkRoot(tested) {
        var missing = config.rootDocuments.filter(function (url) {
            return tested.indexOf(url) === -1;
        });
        if (!missing.length) {
            return Promise.resolve(null);
        }
        return Promise.all(missing.map(function (url) {
            return request(url).then(function (response) {
                return { url: url, response: response };
            });
        })).then(function (answers) {
            var failing = answers.filter(function (answer) {
                return answer.response.status !== 200 || !answer.response.json;
            });
            return {
                label: s.rootLabel,
                url: missing.join('\n'),
                status: failing.length ? 'warn' : 'pass',
                seen: answers.map(function (answer) {
                    return answer.url + ': ' + seen(answer.response);
                }).join('\n'),
                note: failing.length ? s.rootWarn : s.rootPass
            };
        });
    }

    function cell(row, text, className) {
        var td = document.createElement('td');
        if (className) {
            td.className = className;
        }
        if (text) {
            td.textContent = text;
        }
        row.appendChild(td);
        return td;
    }

    function show(result) {
        results.push(result);
        var row = document.createElement('tr');
        var check = cell(row);
        var label = document.createElement('strong');
        label.textContent = result.label;
        var url = document.createElement('div');
        url.className = 'wcai-browser-test-code';
        url.textContent = result.url;
        check.appendChild(label);
        check.appendChild(url);
        var outcome = cell(row);
        var badge = document.createElement('span');
        badge.className = 'wcai-browser-test-badge wcai-browser-test-badge--' + result.status;
        badge.textContent = s[result.status];
        outcome.appendChild(badge);
        var answer = cell(row);
        var what = document.createElement('div');
        what.className = 'wcai-browser-test-code';
        what.textContent = result.seen;
        var note = document.createElement('p');
        note.textContent = result.note;
        answer.appendChild(what);
        answer.appendChild(note);
        rows.appendChild(row);
        return result;
    }

    function finish() {
        var count = function (state) {
            return results.filter(function (result) {
                return result.status === state;
            }).length;
        };
        if (count('fail')) {
            status.textContent = s.failed;
        } else if (count('warn') || count('unknown')) {
            status.textContent = s.warnings;
        } else {
            status.textContent = s.allPassed;
        }
        runButton.disabled = false;
        copyButton.disabled = false;
    }

    function run() {
        results = [];
        rows.textContent = '';
        runButton.disabled = true;
        copyButton.disabled = true;
        status.textContent = s.running;
        var tested = [];
        var endpoint;
        checkEndpoint().then(show).then(function (result) {
            endpoint = result;
            var url = result.metadata || config.rootDocuments[0];
            tested.push(url);
            return checkResource(url).then(show);
        }).then(function (resource) {
            var issuer = resource.issuer;
            var guessed = !issuer;
            if (guessed) {
                issuer = new URL(config.home).origin;
            }
            tested = tested.concat(serverDocuments(issuer));
            return checkServer(issuer, guessed).then(show);
        }).then(function () {
            return endpoint.metadata ? checkRoot(tested) : null;
        }).then(function (root) {
            if (root) {
                show(root);
            }
        }).catch(function (error) {
            show({ label: s.reportTitle, url: '', status: 'fail', seen: String(error), note: '' });
        }).then(finish);
    }

    function report() {
        var lines = [
            s.reportTitle,
            s.reportDate + ': ' + new Date().toISOString(),
            s.reportSite + ': ' + config.home,
            s.reportEndpoint + ': ' + config.endpoint,
            s.reportVersion + ': ' + config.version,
            s.reportBrowser + ': ' + navigator.userAgent,
            ''
        ];
        results.forEach(function (result) {
            lines.push('[' + s[result.status] + '] ' + result.label);
            if (result.url) {
                lines.push('  ' + result.url.split('\n').join('\n  '));
            }
            lines.push('  ' + s.reportSeen + ': ' + result.seen.split('\n').join('\n    '));
            if (result.note) {
                lines.push('  ' + result.note);
            }
            lines.push('');
        });
        return lines.join('\n');
    }

    function copyByHand(text) {
        var area = document.createElement('textarea');
        area.value = text;
        area.setAttribute('readonly', '');
        area.style.position = 'fixed';
        area.style.opacity = '0';
        document.body.appendChild(area);
        area.select();
        var copied = false;
        try {
            copied = document.execCommand('copy');
        } catch (e) {
            copied = false;
        }
        document.body.removeChild(area);
        return copied;
    }

    copyButton.addEventListener('click', function () {
        var text = report();
        var done = function () {
            status.textContent = s.copied;
        };
        var fallback = function () {
            status.textContent = copyByHand(text) ? s.copied : s.copyFailed;
        };
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(done, fallback);
        } else {
            fallback();
        }
    });
    runButton.addEventListener('click', run);
    run();
}());
