(function () {
    function csrfToken(root) {
        var token = root ? root.getAttribute('data-csrf-token') : '';
        if (token) return token;

        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function messageEndpoint(root) {
        return root.getAttribute('data-message-url') || root.getAttribute('data-ai-message-url') || '/ai-assistant/message';
    }

    function historyEndpoint(root) {
        return root.getAttribute('data-history-url') || '/ai-assistant/messages';
    }

    function closeEndpoint(root) {
        return root.getAttribute('data-close-url') || '/ai-assistant/conversation';
    }

    function appendMessage(container, role, text) {
        if (!container || !text) return null;

        var message = document.createElement('div');
        message.className = 'ai-assistant-message ai-assistant-message-' + role;
        message.textContent = text;
        container.appendChild(message);
        container.scrollTop = container.scrollHeight;

        return message;
    }

    function renderMessages(container, items) {
        if (!container) return;

        container.innerHTML = '';
        (items || []).forEach(function (item) {
            appendMessage(container, item.role || 'assistant', item.content || '');
        });
    }

    function parseJson(response) {
        return response.json().catch(function () {
            return {
                ok: false,
                message: response.status === 419
                    ? 'Session expired. Refresh the page and try again.'
                    : 'Request failed. Please try again.'
            };
        });
    }

    function requestJson(root, url, options) {
        options = options || {};
        options.credentials = 'same-origin';
        options.headers = Object.assign({
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken(root)
        }, options.headers || {});

        return fetch(url, options).then(function (response) {
            return parseJson(response).then(function (data) {
                if (!response.ok && (!data || !data.message)) {
                    data = {
                        ok: false,
                        message: response.status === 419
                            ? 'Session expired. Refresh the page and try again.'
                            : 'Request failed with HTTP ' + response.status + '.'
                    };
                }

                return data;
            });
        });
    }

    function loadHistory(root, messages) {
        if (!messages) return;

        requestJson(root, historyEndpoint(root), { method: 'GET' })
            .then(function (data) {
                if (data && data.ok) {
                    renderMessages(messages, data.messages || []);
                }
            })
            .catch(function () {
                // History loading is non-blocking; sending a new message can still create the conversation.
            });
    }

    function closeConversation(root, messages, afterClose) {
        requestJson(root, closeEndpoint(root), { method: 'DELETE' })
            .then(function () {
                renderMessages(messages, []);
            })
            .catch(function () {
                renderMessages(messages, []);
            })
            .finally(function () {
                if (typeof afterClose === 'function') afterClose();
            });
    }

    function bindChat(root) {
        if (!root || root.getAttribute('data-ai-assistant-bound') === '1') return;
        root.setAttribute('data-ai-assistant-bound', '1');

        var form = root.querySelector('[data-ai-assistant-form]');
        var input = form ? form.querySelector('input[name="message"]') : null;
        var messages = root.querySelector('[data-ai-assistant-messages]');
        var button = form ? form.querySelector('button[type="submit"]') : null;

        if (!form || !input || !messages) return;

        loadHistory(root, messages);

        form.addEventListener('submit', function (event) {
            event.preventDefault();

            var text = input.value.trim();
            if (!text) return;

            appendMessage(messages, 'user', text);
            input.value = '';

            if (button) button.disabled = true;
            var loading = appendMessage(messages, 'assistant', 'Thinking...');

            requestJson(root, messageEndpoint(root), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ message: text })
            })
                .then(function (data) {
                    if (loading) loading.remove();
                    appendMessage(messages, data && data.ok ? 'assistant' : 'error', (data && data.message) || 'AI Assistant is currently unavailable.');
                })
                .catch(function () {
                    if (loading) loading.remove();
                    appendMessage(messages, 'error', 'AI Assistant is currently unavailable.');
                })
                .finally(function () {
                    if (button) button.disabled = false;
                    input.focus();
                });
        });
    }

    function bindWidget(widget) {
        if (!widget || widget.getAttribute('data-ai-widget-bound') === '1') return;
        widget.setAttribute('data-ai-widget-bound', '1');

        var launcher = widget.querySelector('.ai-assistant-launcher');
        var panel = widget.querySelector('.ai-assistant-panel');
        var close = widget.querySelector('[data-ai-assistant-close]');
        var minimize = widget.querySelector('[data-ai-assistant-minimize]');
        var messages = widget.querySelector('[data-ai-assistant-messages]');

        function openPanel() {
            if (!panel || !launcher) return;

            panel.hidden = false;
            launcher.setAttribute('aria-expanded', 'true');

            var input = panel.querySelector('input[name="message"]');
            if (input) setTimeout(function () { input.focus(); }, 50);
        }

        function hidePanel() {
            if (!panel || !launcher) return;

            panel.hidden = true;
            launcher.setAttribute('aria-expanded', 'false');
        }

        if (launcher) {
            launcher.addEventListener('click', function () {
                panel && panel.hidden ? openPanel() : hidePanel();
            });
        }

        if (close) {
            close.addEventListener('click', function () {
                closeConversation(widget, messages, hidePanel);
            });
        }

        if (minimize) {
            minimize.addEventListener('click', hidePanel);
        }

        bindChat(widget);
    }

    function boot() {
        document.querySelectorAll('[data-ai-assistant-widget]').forEach(bindWidget);
        document.querySelectorAll('[data-ai-assistant-full]').forEach(bindChat);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
