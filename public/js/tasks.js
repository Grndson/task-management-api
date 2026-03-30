const API = '/api';
let activeFilter = '';

// Set today as minimum date for the due date input
const todayStr = new Date().toISOString().split('T')[0];
document.getElementById('f-date').min   = todayStr;
document.getElementById('f-date').value = todayStr;
document.getElementById('r-date').value = todayStr;

// Status helpers
const statusNext    = { pending: 'in_progress', in_progress: 'done', done: null };
const statusNextLbl = { pending: 'Start',        in_progress: 'Complete',  done: null };
const statusLbl     = { pending: 'pending',      in_progress: 'in progress', done: 'done' };

// Utilities

function esc(s) {
    return String(s)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
}

function fmtDate(s) {
    return new Date(s).toLocaleDateString('en-GB', {
        day: 'numeric', month: 'short', year: 'numeric'
    });
}

function notify(msg, type = '') {
    const el = document.createElement('div');
    el.className = 'toast' + (type ? ' ' + type : '');
    el.textContent = msg;
    document.getElementById('toast').appendChild(el);
    setTimeout(() => el.remove(), 2800);
}

// Stats

function updateStats(tasks) {
    document.getElementById('s-total').textContent   = tasks.length;
    document.getElementById('s-pending').textContent = tasks.filter(t => t.status === 'pending').length;
    document.getElementById('s-inprog').textContent  = tasks.filter(t => t.status === 'in_progress').length;
    document.getElementById('s-done').textContent    = tasks.filter(t => t.status === 'done').length;
}

// Render task list

function render(tasks) {
    const el = document.getElementById('task-list');

    if (!tasks.length) {
        el.innerHTML = `<div class="state-msg">No tasks${activeFilter ? ' with status "' + activeFilter.replace('_', ' ') + '"' : ''}.</div>`;
        return;
    }

    el.innerHTML = tasks.map((t, i) => {
        const next   = statusNext[t.status];
        const canDel = t.status === 'done';

        return `
        <div class="task-item" style="animation-delay:${i * 0.04}s">
            <div class="task-left">
                <div class="task-title${t.status === 'done' ? ' is-done' : ''}">${esc(t.title)}</div>
                <div class="task-tags">
                    <span class="tag ${t.priority}">${t.priority}</span>
                    <span class="tag ${t.status}">${statusLbl[t.status]}</span>
                    <span class="task-date">${fmtDate(t.due_date)}</span>
                </div>
            </div>
            <div class="task-right">
                ${next ? `<button class="btn-next" onclick="advance(${t.id},'${next}')">${statusNextLbl[t.status]}</button>` : ''}
                <button class="btn-del"
                    onclick="remove(${t.id},'${t.status}')"
                    ${!canDel ? 'disabled title="Mark as done first"' : 'title="Delete task"'}>×</button>
            </div>
        </div>`;
    }).join('');
}

// Load tasks from API

async function load() {
    const url = activeFilter ? `${API}/tasks?status=${activeFilter}` : `${API}/tasks`;

    try {
        const r = await fetch(url, { headers: { Accept: 'application/json' } });
        const d = await r.json();
        const tasks = d.tasks || [];
        render(tasks);
        updateStats(tasks);
    } catch {
        document.getElementById('task-list').innerHTML =
            `<div class="state-msg">Could not reach API. Is the server running?</div>`;
    }
}

// Filter

function setFilter(btn, f) {
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    const label = document.querySelector('.list-header .section-label');
    label.textContent = f ? f.replace('_', ' ') + ' tasks' : 'All tasks';

    activeFilter = f;
    document.getElementById('task-list').innerHTML = `<div class="state-msg">loading...</div>`;
    load();
}

// Field errors

function clearErrs() {
    ['title', 'date', 'priority'].forEach(f => {
        const el = document.getElementById('e-' + f);
        el.textContent = '';
        el.classList.remove('visible');
    });
}

function showErr(id, msg) {
    const el = document.getElementById('e-' + id);
    el.textContent = msg;
    el.classList.add('visible');
}

// Create task

async function createTask() {
    clearErrs();

    const title    = document.getElementById('f-title').value.trim();
    const due_date = document.getElementById('f-date').value;
    const priority = document.getElementById('f-priority').value;

    // Client-side checks before hitting the API
    let valid = true;
    if (!title)    { showErr('title',    'Title is required');      valid = false; }
    if (!due_date) { showErr('date',     'Due date is required');   valid = false; }
    if (!priority) { showErr('priority', 'Select a priority');      valid = false; }
    if (!valid) return;

    const btn = document.getElementById('btn-submit');
    btn.disabled    = true;
    btn.textContent = 'Adding...';

    try {
        const r = await fetch(`${API}/tasks`, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
            body:    JSON.stringify({ title, due_date, priority })
        });
        const d = await r.json();

        if (r.ok) {
            notify('Task added', 'ok');
            document.getElementById('f-title').value    = '';
            document.getElementById('f-priority').value = '';
            document.getElementById('f-date').value     = todayStr;
            load();
        } else {
            // Show Laravel validation errors inline
            if (d.errors) {
                if (d.errors.title)    showErr('title',    d.errors.title[0]);
                if (d.errors.due_date) showErr('date',     d.errors.due_date[0]);
                if (d.errors.priority) showErr('priority', d.errors.priority[0]);
            } else {
                notify(d.message || 'Something went wrong', 'error');
            }
        }
    } catch {
        notify('Network error', 'error');
    }

    btn.disabled    = false;
    btn.textContent = 'Add task';
}

// Advance status

async function advance(id, next) {
    try {
        const r = await fetch(`${API}/tasks/${id}/status`, {
            method:  'PATCH',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
            body:    JSON.stringify({ status: next })
        });
        const d = await r.json();

        if (r.ok) { notify('Status updated', 'ok'); load(); }
        else       { notify(d.message || 'Could not update status', 'error'); }
    } catch {
        notify('Network error', 'error');
    }
}

// Delete task

async function remove(id, status) {
    if (status !== 'done') {
        notify('Only done tasks can be deleted', 'error');
        return;
    }

    if (!confirm('Delete this task? This cannot be undone.')) return;

    try {
        const r = await fetch(`${API}/tasks/${id}`, {
            method:  'DELETE',
            headers: { Accept: 'application/json' }
        });
        const d = await r.json();

        if (r.ok) { notify('Task deleted', 'ok'); load(); }
        else       { notify(d.message || 'Could not delete task', 'error'); }
    } catch {
        notify('Network error', 'error');
    }
}

// Daily report 

async function loadReport() {
    const date = document.getElementById('r-date').value;
    if (!date) { notify('Pick a date first', 'error'); return; }

    const btn = document.getElementById('btn-report');
    btn.disabled    = true;
    btn.textContent = 'Loading...';

    const output = document.getElementById('report-output');
    output.innerHTML = '';

    try {
        const r = await fetch(`${API}/tasks/report?date=${date}`, {
            headers: { Accept: 'application/json' }
        });
        const d = await r.json();

        if (!r.ok) {
            output.innerHTML = `<div class="report-empty">${d.message || 'Could not load report.'}</div>`;
            return;
        }

        const s = d.summary;
        const priorities = ['high', 'medium', 'low'];
        const statuses   = ['pending', 'in_progress', 'done'];

        // Total per status across all priorities
        const totals = {};
        statuses.forEach(st => {
            totals[st] = priorities.reduce((sum, p) => sum + (s[p][st] || 0), 0);
        });
        const grandTotal = Object.values(totals).reduce((a, b) => a + b, 0);

        function cell(n) {
            return `<td class="${n === 0 ? 'report-zero' : ''}">${n}</td>`;
        }

        output.innerHTML = `
            <div class="report-date">Report for ${date} &mdash; ${d.total} task${d.total !== 1 ? 's' : ''}</div>
            ${grandTotal === 0
                ? `<div class="report-empty">No tasks due on this date.</div>`
                : `<table class="report-table">
                    <thead>
                        <tr>
                            <th>Priority</th>
                            <th>Pending</th>
                            <th>In prog.</th>
                            <th>Done</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${priorities.map(p => `
                        <tr>
                            <td class="report-priority-${p}">${p}</td>
                            ${statuses.map(st => cell(s[p][st] || 0)).join('')}
                        </tr>`).join('')}
                        <tr class="report-total-row">
                            <td>Total</td>
                            ${statuses.map(st => `<td>${totals[st]}</td>`).join('')}
                        </tr>
                    </tbody>
                </table>`
            }`;
    } catch {
        output.innerHTML = `<div class="report-empty">Network error.</div>`;
    }

    btn.disabled    = false;
    btn.textContent = 'Generate report';
}

// Init
load();