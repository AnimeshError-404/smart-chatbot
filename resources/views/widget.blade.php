@if(config('smart-chatbot.enabled'))
    <div id="rb-smart-chatbot" style="position:fixed;right:24px;bottom:24px;z-index:99999;font-family:Arial,sans-serif;">
        <div id="rb-chat-window" style="display:none;width:360px;height:520px;background:#fff;border-radius:18px;box-shadow:0 20px 60px rgba(0,0,0,.22);overflow:hidden;border:1px solid #e5e7eb;">
            <div style="background:#111827;color:#fff;padding:16px 18px;font-weight:700;">
                {{ config('smart-chatbot.bot_name', 'Website Assistant') }}
                <button id="rb-chat-close" type="button" style="float:right;background:transparent;border:0;color:#fff;font-size:20px;cursor:pointer;">×</button>
            </div>

            <div id="rb-chat-messages" style="height:380px;overflow-y:auto;padding:16px;background:#f9fafb;font-size:14px;line-height:1.6;">
                <div style="background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:10px 12px;margin-bottom:10px;">
                    <strong>Assistant:</strong> Hello! How can I help you today?
                </div>
            </div>

            <form id="rb-chat-form" style="display:flex;border-top:1px solid #e5e7eb;">
                <input id="rb-chat-input" type="text" placeholder="Type your question..." style="flex:1;border:0;padding:14px;outline:none;font-size:14px;">
                <button id="rb-chat-send" type="submit" style="border:0;background:#111827;color:#fff;padding:0 18px;font-weight:700;cursor:pointer;">Send</button>
            </form>
        </div>

        <button id="rb-chat-toggle" type="button" style="margin-top:12px;background:#111827;color:#fff;border:0;border-radius:999px;padding:12px 18px;font-weight:700;cursor:pointer;box-shadow:0 12px 30px rgba(0,0,0,.25);">
            Chat
        </button>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const windowBox = document.getElementById('rb-chat-window');
            const toggleBtn = document.getElementById('rb-chat-toggle');
            const closeBtn = document.getElementById('rb-chat-close');
            const form = document.getElementById('rb-chat-form');
            const input = document.getElementById('rb-chat-input');
            const messages = document.getElementById('rb-chat-messages');
            const sendBtn = document.getElementById('rb-chat-send');

            toggleBtn.addEventListener('click', function () {
                windowBox.style.display = windowBox.style.display === 'none' ? 'block' : 'none';
            });

            closeBtn.addEventListener('click', function () {
                windowBox.style.display = 'none';
            });

            function escapeHtml(text) {
                const div = document.createElement('div');
                div.innerText = text;
                return div.innerHTML;
            }

            function addMessage(label, text, isUser = false) {
                const div = document.createElement('div');
                div.style.background = isUser ? '#111827' : '#ffffff';
                div.style.color = isUser ? '#ffffff' : '#111827';
                div.style.border = isUser ? '0' : '1px solid #e5e7eb';
                div.style.borderRadius = '14px';
                div.style.padding = '10px 12px';
                div.style.marginBottom = '10px';
                div.style.maxWidth = '88%';
                div.style.marginLeft = isUser ? 'auto' : '0';
                div.innerHTML = '<strong>' + label + ':</strong> ' + escapeHtml(text);
                messages.appendChild(div);
                messages.scrollTop = messages.scrollHeight;
            }

            form.addEventListener('submit', async function (e) {
                e.preventDefault();

                const question = input.value.trim();

                if (!question) {
                    return;
                }

                addMessage('You', question, true);
                input.value = '';
                sendBtn.disabled = true;

                try {
                    const response = await fetch("{{ route('smart-chatbot.ask') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': "{{ csrf_token() }}"
                        },
                        body: JSON.stringify({ question })
                    });

                    const data = await response.json();

                    addMessage('Assistant', data.answer || 'Sorry, no answer found.');
                } catch (error) {
                    addMessage('Assistant', 'Something went wrong. Please try again.');
                } finally {
                    sendBtn.disabled = false;
                }
            });
        });
    </script>
@endif