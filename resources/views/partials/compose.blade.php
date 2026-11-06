{{-- Gmail-style Compose window (bottom-right on desktop, full screen on phones). --}}
<button class="compose-fab" id="composeFab" type="button" aria-label="Compose">
    <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a1 1 0 0 0 0-1.41l-2.34-2.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
    <span>Compose</span>
</button>

<div class="compose" id="compose" hidden>
    <div class="compose-head" id="composeHead">
        <span class="compose-title" id="composeTitle">New Message</span>
        <button type="button" data-act="min" title="Minimise">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M5 19h14v2H5z"/></svg>
        </button>
        <button type="button" data-act="max" title="Full screen" class="compose-maxbtn">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M7 14H5v5h5v-2H7v-3zm-2-4h2V7h3V5H5v5zm12 7h-3v2h5v-5h-2v3zM14 5v2h3v3h2V5h-5z"/></svg>
        </button>
        <button type="button" data-act="close" title="Save & close">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M19 6.41 17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
        </button>
    </div>

    @if($composeMailboxes->isEmpty())
        <div class="compose-empty">
            No brand Gmail is connected yet.
            @if(auth()->user()?->isAdmin())
                Connect one under <a href="{{ route('mailboxes') }}">Mailboxes</a> first.
            @else
                Ask the admin to connect one.
            @endif
        </div>
    @else
        <form class="compose-body" id="composeForm" novalidate>
            <div class="compose-row">
                <label for="cFrom">From</label>
                <select id="cFrom" name="mailbox_id">
                    @foreach($composeMailboxes as $cmb)
                        <option value="{{ $cmb->id }}" {{ (int) request('mailbox') === $cmb->id ? 'selected' : '' }}>{{ $cmb->brand_name }} &lt;{{ $cmb->email }}&gt;</option>
                    @endforeach
                </select>
            </div>
            <div class="compose-row">
                <label for="cTo">To</label>
                <input id="cTo" name="to" type="text" autocomplete="off" inputmode="email" placeholder="customer@example.com">
                <span class="compose-ccbcc">
                    <a href="#" data-show="cCcRow">Cc</a>
                    <a href="#" data-show="cBccRow">Bcc</a>
                </span>
            </div>
            <div class="compose-row" id="cCcRow" hidden>
                <label for="cCc">Cc</label>
                <input id="cCc" name="cc" type="text" autocomplete="off" inputmode="email">
            </div>
            <div class="compose-row" id="cBccRow" hidden>
                <label for="cBcc">Bcc</label>
                <input id="cBcc" name="bcc" type="text" autocomplete="off" inputmode="email">
            </div>
            <div class="compose-row">
                <input id="cSubject" name="subject" type="text" maxlength="300" placeholder="Subject">
            </div>
            <textarea id="cBody" name="body" placeholder="Write your message…"></textarea>
            <div class="compose-sig">The brand’s signature is added automatically.</div>
            <div class="compose-files" id="cFiles"></div>
            <div class="compose-error" id="cError" hidden></div>
            <div class="compose-foot">
                <button class="btn-send" id="cSend" type="submit">Send</button>
                <label class="compose-icon" title="Attach files">
                    <input type="file" id="cAttach" multiple hidden>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M16.5 6v11.5a4 4 0 0 1-8 0V5a2.5 2.5 0 0 1 5 0v10.5a1 1 0 0 1-2 0V6H10v9.5a2.5 2.5 0 0 0 5 0V5a4 4 0 0 0-8 0v12.5a5.5 5.5 0 0 0 11 0V6h-1.5z"/></svg>
                </label>
                <span class="compose-saved" id="cSaved"></span>
                <button class="compose-icon compose-discard" type="button" id="cDiscard" title="Discard draft">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M15 4V3H9v1H4v2h1v13a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V6h1V4h-5zm2 15H7V6h10v13zM9 8h2v9H9zm4 0h2v9h-2z"/></svg>
                </button>
            </div>
        </form>
    @endif
</div>

<script>
(function () {
    const box = document.getElementById('compose');
    const fab = document.getElementById('composeFab');
    const openers = [fab, document.getElementById('composeBtn')].filter(Boolean);
    const form = document.getElementById('composeForm');
    const DRAFT = 'md_draft_{{ auth()->id() }}';
    let files = [];

    function open() {
        box.hidden = false;
        box.classList.remove('min');
        document.body.classList.add('composing');
        if (form) (form.cTo.value ? form.cBody : form.cTo).focus();
    }
    function close() {
        box.hidden = true;
        box.classList.remove('min', 'max');
        document.body.classList.remove('composing');
    }
    openers.forEach(b => b.addEventListener('click', open));

    box.querySelector('.compose-head').addEventListener('click', e => {
        const act = e.target.closest('button')?.dataset.act;
        if (act === 'close') return close();
        if (act === 'max') { box.classList.toggle('max'); box.classList.remove('min'); return; }
        if (act === 'min' || box.classList.contains('min')) box.classList.toggle('min');
    });

    if (!form) return;
    // `form.cTo` style access by id
    ['cFrom', 'cTo', 'cCc', 'cBcc', 'cSubject', 'cBody'].forEach(id => form[id] = document.getElementById(id));

    // ---- Cc / Bcc ----
    form.querySelectorAll('[data-show]').forEach(a => a.addEventListener('click', e => {
        e.preventDefault();
        const row = document.getElementById(a.dataset.show);
        row.hidden = false; a.hidden = true; row.querySelector('input').focus();
    }));

    // ---- Draft autosave (text only) ----
    const fields = ['cFrom', 'cTo', 'cCc', 'cBcc', 'cSubject', 'cBody'];
    const saved = document.getElementById('cSaved');
    let saveTimer;
    function saveDraft() {
        clearTimeout(saveTimer);
        saveTimer = setTimeout(() => {
            const d = {};
            fields.forEach(f => d[f] = form[f].value);
            const empty = !d.cTo && !d.cCc && !d.cBcc && !d.cSubject && !d.cBody;
            try { empty ? localStorage.removeItem(DRAFT) : localStorage.setItem(DRAFT, JSON.stringify(d)); } catch (e) {}
            saved.textContent = empty ? '' : 'Draft saved';
            updateTitle();
        }, 400);
    }
    function updateTitle() {
        document.getElementById('composeTitle').textContent = form.cSubject.value.trim() || 'New Message';
    }
    try {
        const d = JSON.parse(localStorage.getItem(DRAFT) || 'null');
        if (d) {
            fields.forEach(f => { if (d[f] !== undefined && form[f]) form[f].value = d[f]; });
            if (d.cCc) document.getElementById('cCcRow').hidden = false;
            if (d.cBcc) document.getElementById('cBccRow').hidden = false;
            updateTitle();
        }
    } catch (e) {}
    form.addEventListener('input', saveDraft);
    form.addEventListener('change', saveDraft);

    // ---- Attachments ----
    const MAX = 20 * 1024 * 1024;
    const list = document.getElementById('cFiles');
    const err = document.getElementById('cError');
    const fmt = n => n > 1048576 ? (n / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(n / 1024)) + ' KB';
    function renderFiles() {
        list.innerHTML = '';
        files.forEach((f, i) => {
            const chip = document.createElement('div');
            chip.className = 'compose-file';
            chip.innerHTML = '<span class="cf-name"></span><span class="cf-size"></span><button type="button" title="Remove">&times;</button>';
            chip.querySelector('.cf-name').textContent = f.name;
            chip.querySelector('.cf-size').textContent = fmt(f.size);
            chip.querySelector('button').onclick = () => { files.splice(i, 1); renderFiles(); };
            list.appendChild(chip);
        });
    }
    document.getElementById('cAttach').addEventListener('change', e => {
        files = files.concat(Array.from(e.target.files));
        e.target.value = '';
        const total = files.reduce((s, f) => s + f.size, 0);
        if (total > MAX) showError('Attachments are too large together (20 MB max).');
        renderFiles();
    });

    function showError(msg) { err.textContent = msg; err.hidden = !msg; }

    // ---- Discard ----
    document.getElementById('cDiscard').addEventListener('click', () => {
        const hasText = fields.some(f => f !== 'cFrom' && form[f].value.trim());
        if ((hasText || files.length) && !confirm('Discard this draft?')) return;
        fields.forEach(f => { if (f !== 'cFrom') form[f].value = ''; });
        files = []; renderFiles(); showError('');
        try { localStorage.removeItem(DRAFT); } catch (e) {}
        saved.textContent = ''; updateTitle(); close();
    });

    // ---- Send ----
    const sendBtn = document.getElementById('cSend');
    async function send(e) {
        if (e) e.preventDefault();
        showError('');
        if (!form.cTo.value.trim()) return showError('Add at least one recipient in “To”.');
        if (!form.cBody.value.trim()) return showError('Write a message first.');
        if (!form.cSubject.value.trim() && !confirm('Send this message without a subject?')) return;
        if (files.reduce((s, f) => s + f.size, 0) > MAX) return showError('Attachments are too large together (20 MB max).');

        const fd = new FormData();
        fields.forEach(f => fd.append(form[f].name, form[f].value));
        files.forEach(f => fd.append('attachments[]', f, f.name));

        sendBtn.disabled = true;
        sendBtn.textContent = 'Sending…';
        try {
            const res = await fetch("{{ route('compose.send') }}", {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: fd,
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok) {
                const first = data.errors ? Object.values(data.errors)[0][0] : null;
                showError(first || data.message || (res.status === 413 ? 'Attachments are too large for the server.' : 'Sending failed — please try again.'));
            } else {
                try { localStorage.removeItem(DRAFT); } catch (e) {}
                location.href = data.redirect;
                return;
            }
        } catch (ex) {
            showError('No connection — your draft is saved, try again.');
        }
        sendBtn.disabled = false;
        sendBtn.textContent = 'Send';
    }
    form.addEventListener('submit', send);
    form.addEventListener('keydown', e => { if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) send(e); });
})();
</script>
