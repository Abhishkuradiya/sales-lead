<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leads | Lead Sales</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.dataTables.min.css">
    <link rel="stylesheet" href="/assets/css/dashboard.css">
</head>
<body>
    <header class="topbar">
        <a class="brand" href="/index.php/dashboard"><span class="brand-mark">L</span><span>Lead Sales</span></a>
        <div class="user-area">
            <span class="avatar" aria-hidden="true"><?= esc(strtoupper(substr((string) ($user['name'] ?? 'U'), 0, 1))) ?></span>
            <span><?= esc($user['name'] ?? $user['email'] ?? 'Signed in') ?></span>
            <button class="logout" id="logout" type="button">Sign out</button>
        </div>
    </header>

    <main class="container">
        <header class="page-header">
            <div>
                <p class="breadcrumb">Sales / Leads</p>
                <h1>Leads</h1>
                <p class="subtitle">Add and manage your sales leads from one place.</p>
            </div>
            <div class="page-actions"><button class="button" id="open-drawer" type="button">+ Add lead</button></div>
        </header>

        <p class="summary" id="lead-summary">Loading leads...</p>

        <section class="lead-form-card" id="lead-form-panel" aria-labelledby="drawer-title">
            <div class="form-card-header"><div><h2 id="drawer-title">Add lead</h2><p>Capture a new business lead.</p></div></div>
            <form id="lead-form" method="post" action="">
                <div class="form-grid">
                    <div class="field"><label for="firm_name">Firm name</label><input id="firm_name" name="firm_name" required></div>
                    <div class="field"><label for="honour_name">Honour name</label><input id="honour_name" name="honour_name" required></div>
                    <div class="field"><label for="manufactured_items">Manufactured items</label><select id="manufactured_items" name="manufactured_items" required><option value="">Loading items...</option></select><div class="custom-option-row" id="manufactured-custom-row"><input class="custom-option-input" id="custom_manufactured_item" type="text" placeholder="Enter new manufactured item" aria-label="New manufactured item"><button class="custom-add-button" id="add-manufactured-item" type="button">Add</button></div></div>
                    <div class="field"><label for="raw_materials">Raw materials</label><select id="raw_materials" name="raw_materials" required><option value="">Loading materials...</option></select><div class="custom-option-row" id="raw-material-custom-row"><input class="custom-option-input" id="custom_raw_material" type="text" placeholder="Enter new raw material" aria-label="New raw material"><button class="custom-add-button" id="add-raw-material" type="button">Add</button></div></div>
                    <div class="field"><label for="website">Website</label><input id="website" name="website" type="url" placeholder="https://example.com"></div>
                    <div class="field"><label for="gst_no">GST no.</label><input id="gst_no" name="gst_no"></div>
                </div>
                <div class="form-actions"><button class="button ghost" type="reset" id="cancel-edit">Clear</button><button class="button" type="submit" id="save-lead">Add lead</button></div>
            </form>
        </section>

        <section class="table-card" aria-labelledby="directory-title">
            <div class="table-toolbar">
                <h2 id="directory-title" class="sr-only">Lead directory</h2>
                <label class="search-box" for="lead-search">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-4-4"></path></svg>
                    <input id="lead-search" type="search" placeholder="Search firm, owner, GST..." aria-label="Search leads">
                </label>
                <label class="rows-control">Rows <select id="rows-per-page" aria-label="Rows per page"><option value="10">10</option><option value="25">25</option><option value="50">50</option></select></label>
            </div>
            <div class="table-wrap">
                <table id="leads-table" class="display">
                    <thead><tr><th>S.No.</th><th>Firm name</th><th>Honour name</th><th>Manufactured items</th><th>Website</th><th>GST no.</th><th>Actions</th></tr></thead>
                    <tbody>
                        <tr class="loading-row"><td colspan="7"><span class="skeleton"></span></td></tr>
                        <tr class="loading-row"><td colspan="7"><span class="skeleton"></span></td></tr>
                        <tr class="loading-row"><td colspan="7"><span class="skeleton"></span></td></tr>
                    </tbody>
                </table>
            </div>
            <div class="table-footer"><span id="table-info">Showing 0 to 0 of 0 leads</span><div id="table-pagination" class="table-pagination" aria-label="Table pagination"></div></div>
        </section>

    </main>

    <script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js"></script>
    <script {csp-script-nonce}>
        (() => {
            const leads = [];
            let editingIndex = null;
            const fields = ['firm_name', 'honour_name', 'manufactured_items', 'raw_materials', 'website', 'gst_no'];
            const summary = document.getElementById('lead-summary');
            const formPanel = document.getElementById('lead-form-panel');

            const escapeHtml = value => String(value ?? '').replace(/[&<>'"]/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[char]));
            const emptyValue = value => value ? escapeHtml(value) : '<span class="muted-empty">—</span>';
            const domain = value => { try { return new URL(value).hostname.replace(/^www\./, ''); } catch { return value; } };
            const initials = value => (String(value || 'U').trim().split(/\s+/).slice(0, 2).map(part => part[0]).join('') || 'U').toUpperCase();

            const columns = [
                    { data: null, render: (data, type, row, meta) => meta.row + 1 },
                    { data: null, render: (data, type, row) => { const name = row.firm_name || ''; const website = row.website || ''; return `<div class="firm-cell"><span class="firm-avatar">${escapeHtml(initials(name))}</span><span class="firm-info"><span class="firm-name ${name ? '' : 'empty'}">${name ? escapeHtml(name) : 'Unnamed firm'}</span>${website ? `<a class="firm-domain" href="${escapeHtml(website)}" target="_blank" rel="noopener noreferrer">${escapeHtml(domain(website))}</a>` : ''}</span></div>`; } },
                    { data: 'honour_name', render: value => `<span class="cell-truncate" title="${escapeHtml(value || '')}">${emptyValue(value)}</span>` },
                    { data: 'manufactured_items', render: value => `<span class="cell-truncate" title="${escapeHtml(value || '')}">${emptyValue(value)}</span>` },
                    { data: 'website', render: value => value ? `<a class="website-link" href="${escapeHtml(value)}" target="_blank" rel="noopener noreferrer">${escapeHtml(domain(value))}</a>` : '<span class="muted-empty">—</span>' },
                    { data: 'gst_no', render: value => value ? `<span class="gst-chip">${escapeHtml(value)}</span>` : '<span class="muted-empty">—</span>' },
                    { data: null, orderable: false, searchable: false, render: (data, type, row, meta) => `<div class="row-actions"><button class="action view" data-index="${meta.row}" type="button">View</button><button class="action edit" data-index="${meta.row}" type="button">Edit</button><button class="action update" data-index="${meta.row}" type="button">Update</button><button class="action remark" data-index="${meta.row}" type="button">Remark</button></div>` }
            ];

            const fallbackTable = () => {
                let query = '';
                let pageLength = 10;
                let currentPage = 0;
                const listeners = { draw: [] };
                const render = () => {
                    const filtered = leads.filter(record => Object.values(record).join(' ').toLowerCase().includes(query.toLowerCase()));
                    const pages = Math.max(1, Math.ceil(filtered.length / pageLength));
                    currentPage = Math.min(currentPage, pages - 1);
                    const visible = filtered.slice(currentPage * pageLength, (currentPage + 1) * pageLength);
                    const tbody = document.querySelector('#leads-table tbody');
                    tbody.innerHTML = visible.length ? visible.map((row, index) => `<tr>${columns.map((column, columnIndex) => `<td>${column.render ? column.render(row[column.data], 'display', row, { row: currentPage * pageLength + index }) : emptyValue(row[column.data])}</td>`).join('')}</tr>`).join('') : '<tr><td colspan="7"><div class="empty-message">No leads match your search</div></td></tr>';
                    summary.textContent = filtered.length ? `Showing ${currentPage * pageLength + 1} to ${Math.min((currentPage + 1) * pageLength, filtered.length)} of ${filtered.length} leads` : '0 leads';
                    listeners.draw.forEach(listener => listener());
                };
                const page = target => ({ draw: () => { currentPage = Math.max(0, target); render(); } });
                page.len = value => ({ draw: () => { pageLength = value; currentPage = 0; render(); } });
                page.info = () => ({ start: currentPage * pageLength, end: Math.min((currentPage + 1) * pageLength, leads.length), recordsDisplay: leads.length, length: pageLength });
                return { search: value => ({ draw: () => { query = value; currentPage = 0; render(); } }), page, clear: () => ({ rows: { add: values => ({ draw: () => { leads.splice(0, leads.length, ...values); render(); } }) } }), on: (event, listener) => { if (listeners[event]) listeners[event].push(listener); }, draw: render };
            };

            const table = typeof DataTable === 'function' ? new DataTable('#leads-table', {
                data: leads,
                layout: { topStart: null, topEnd: null, bottomStart: null, bottomEnd: null },
                language: { emptyTable: 'No leads match your search', info: 'Showing _START_ to _END_ of _TOTAL_ leads', infoEmpty: 'Showing 0 to 0 of 0 leads', paginate: { first: '«', last: '»', previous: '‹', next: '›' } },
                columns
            }) : fallbackTable();
            if (typeof DataTable !== 'function') table.draw();

            const directoryRecords = payload => { if (Array.isArray(payload)) return payload; for (const key of ['data','parties','records','results','third_party_directory']) if (Array.isArray(payload?.[key])) return payload[key]; return []; };
            const mapRecord = record => ({ firm_name: record.firm_name ?? record.firmName ?? record.company_name ?? record.company ?? record.name ?? '', honour_name: record.honour_name ?? record.honourName ?? record.owner_name ?? record.owner ?? '', manufactured_items: record.manufactured_items ?? record.manufacturedItems ?? record.products ?? record.items ?? '', website: record.website ?? record.web_site ?? record.url ?? '', gst_no: record.gst_no ?? record.gstNo ?? record.gst_number ?? record.gst ?? '' });
            const optionsFrom = (payload, namesKey, objectsKey) => payload?.data?.[namesKey] || payload?.[namesKey] || payload?.data?.[objectsKey]?.map(item => item.name) || payload?.[objectsKey]?.map(item => item.name) || [];
            const createCustomOption = async (selectId, customId, endpoint) => { const select = document.getElementById(selectId); if (select.value !== '__custom__') return select.value; const input = document.getElementById(customId); const name = input.value.trim(); if (!name) throw new Error('Enter a name for the custom item.'); const response = await fetch('https://erp.supplyr.in' + endpoint, { method:'POST', headers:{'Content-Type':'application/json','Accept':'application/json','Authorization':'Bearer ' + <?= json_encode($apiToken ?? '') ?>}, body:JSON.stringify({ name }) }); const payload = await response.json(); if (!response.ok || payload.status === 'error') throw new Error(payload.message || JSON.stringify(payload)); const option = new Option(name, name, true, true); select.insertBefore(option, select.querySelector('option[value="__custom__"]')); select.value = name; input.value = ''; input.parentElement.classList.remove('visible'); return name; };
            const setOptions = (select, values, customId, endpoint) => { select.innerHTML = '<option value="">Select an option</option>' + values.map(value => `<option value="${escapeHtml(value)}">${escapeHtml(value)}</option>`).join('') + '<option value="__custom__">+ Add custom item</option>'; select.addEventListener('change', () => { const row = document.getElementById(customId).parentElement; row.classList.toggle('visible', select.value === '__custom__'); if (select.value !== '__custom__') document.getElementById(customId).value = ''; }); document.getElementById(customId).parentElement.querySelector('button').addEventListener('click', async () => { const button = document.getElementById(customId).parentElement.querySelector('button'); button.disabled = true; try { await createCustomOption(select.id, customId, endpoint); } catch (error) { alert(error.message); } finally { button.disabled = false; } }); };
            const loadManufacturingOptions = async () => { const response = await fetch('https://erp.supplyr.in/api/manufacturing-options', { headers: { 'Accept':'application/json', 'Authorization':'Bearer ' + <?= json_encode($apiToken ?? '') ?> } }); const payload = await response.json(); if (!response.ok || payload.status === 'error') throw new Error(payload.message || JSON.stringify(payload)); setOptions(document.getElementById('manufactured_items'), optionsFrom(payload, 'manufactured_item_names', 'manufactured_items'), 'custom_manufactured_item', '/api/manufactured-items'); setOptions(document.getElementById('raw_materials'), optionsFrom(payload, 'raw_material_names', 'raw_materials'), 'custom_raw_material', '/api/raw-materials'); };
            const updateSummary = () => { const info = table.page.info(); summary.textContent = info.recordsDisplay ? `Showing ${info.start + 1} to ${info.end} of ${info.recordsDisplay} leads` : '0 leads'; };
            const renderPagination = () => {
                const info = table.page.info();
                const totalPages = Math.max(1, Math.ceil(info.recordsDisplay / info.length));
                const currentPage = info.length ? Math.floor(info.start / info.length) : 0;
                document.getElementById('table-info').textContent = info.recordsDisplay ? `Showing ${info.start + 1} to ${info.end} of ${info.recordsDisplay} leads` : 'Showing 0 to 0 of 0 leads';
                const buttons = [`<button type="button" data-page="first" ${currentPage === 0 ? 'disabled' : ''} aria-label="First page">«</button>`, `<button type="button" data-page="previous" ${currentPage === 0 ? 'disabled' : ''} aria-label="Previous page">‹</button>`];
                const start = Math.max(0, Math.min(currentPage - 2, totalPages - 5));
                const end = Math.min(totalPages, start + 5);
                for (let page = start; page < end; page++) buttons.push(`<button type="button" data-page="${page}" class="${page === currentPage ? 'current' : ''}" aria-label="Page ${page + 1}">${page + 1}</button>`);
                buttons.push(`<button type="button" data-page="next" ${currentPage >= totalPages - 1 ? 'disabled' : ''} aria-label="Next page">›</button>`, `<button type="button" data-page="last" ${currentPage >= totalPages - 1 ? 'disabled' : ''} aria-label="Last page">»</button>`);
                document.getElementById('table-pagination').innerHTML = buttons.join('');
            };
            const loadDirectory = async () => { const controller = new AbortController(); const timeout = setTimeout(() => controller.abort(), 12000); try { const response = await fetch('https://erp.supplyr.in/api/parties/third-party-directory', { headers: { 'Accept':'application/json', 'Authorization':'Bearer ' + <?= json_encode($apiToken ?? '') ?> }, signal: controller.signal }); const payload = await response.json(); if (!response.ok || payload.status === 'error') throw new Error(payload.message || JSON.stringify(payload)); leads.splice(0, leads.length, ...directoryRecords(payload).map(mapRecord)); table.clear().rows.add(leads).draw(); updateSummary(); } catch (error) { table.clear().draw(); summary.textContent = error.name === 'AbortError' ? 'Directory request timed out.' : error.message; } finally { clearTimeout(timeout); } };
            const createLead = async lead => { const payloadBody = { firmname: document.getElementById('firm_name').value.trim(), howner_name: document.getElementById('honour_name').value.trim(), website: document.getElementById('website').value.trim(), gst_no: document.getElementById('gst_no').value.trim(), manufacture_items: [lead.manufactured_items], raw_materials_used: [lead.raw_materials], party_type: 'customer' }; const missing = ['firmname', 'howner_name', 'website', 'gst_no'].find(key => !payloadBody[key]); if (missing) throw new Error(`${missing} is required.`); const response = await fetch('https://erp.supplyr.in/api/parties/third-party-directory', { method:'POST', headers:{'Content-Type':'application/json','Accept':'application/json','Authorization':'Bearer ' + <?= json_encode($apiToken ?? '') ?>}, body:JSON.stringify(payloadBody) }); const payload = await response.json(); if (!response.ok || payload.status === 'error') throw new Error(payload.message || JSON.stringify(payload)); return payload; };
            const openDrawer = () => { formPanel.scrollIntoView({ behavior: 'smooth', block: 'start' }); document.getElementById('firm_name').focus(); };
            const closeDrawer = () => {};
            const clearForm = () => { editingIndex = null; document.getElementById('lead-form').reset(); document.getElementById('drawer-title').textContent = 'Add lead'; document.getElementById('save-lead').textContent = 'Add lead'; };
            const fillForm = lead => { fields.forEach(field => document.getElementById(field).value = lead[field] || ''); openDrawer(); };

            document.getElementById('lead-search').addEventListener('input', event => { table.search(event.target.value).draw(); updateSummary(); });
            document.getElementById('rows-per-page').addEventListener('change', event => table.page.len(Number(event.target.value)).draw());
            table.on('draw', () => { updateSummary(); renderPagination(); });
            document.getElementById('table-pagination').addEventListener('click', event => { const button = event.target.closest('button'); if (!button || button.disabled) return; const info = table.page.info(); const last = Math.max(0, Math.ceil(info.recordsDisplay / info.length) - 1); const current = info.length ? Math.floor(info.start / info.length) : 0; const target = button.dataset.page === 'first' ? 0 : button.dataset.page === 'previous' ? current - 1 : button.dataset.page === 'next' ? current + 1 : button.dataset.page === 'last' ? last : Number(button.dataset.page); table.page(target).draw('page'); });
            document.getElementById('open-drawer').addEventListener('click', () => { clearForm(); openDrawer(); });
            document.addEventListener('keydown', event => { if (event.key === 'Escape') { closeDrawer(); document.querySelectorAll('.more-menu.open').forEach(menu => menu.classList.remove('open')); } });
            document.getElementById('cancel-edit').addEventListener('click', clearForm);
            document.getElementById('lead-form').addEventListener('submit', async event => { event.preventDefault(); const saveButton = document.getElementById('save-lead'); saveButton.disabled = true; saveButton.textContent = 'Saving...'; try { const manufacturedItem = await createCustomOption('manufactured_items', 'custom_manufactured_item', '/api/manufactured-items'); const rawMaterial = await createCustomOption('raw_materials', 'custom_raw_material', '/api/raw-materials'); const lead = Object.fromEntries(fields.map(field => [field, document.getElementById(field).value.trim()])); lead.manufactured_items = manufacturedItem; lead.raw_materials = rawMaterial; await createLead(lead); clearForm(); await loadDirectory(); } catch (error) { alert(error.message); } finally { saveButton.disabled = false; saveButton.textContent = 'Add lead'; } });
            document.querySelector('#leads-table tbody').addEventListener('click', event => { const button = event.target.closest('button'); if (!button) return; const index = Number(button.dataset.index); const lead = leads[index]; if (button.dataset.menu) { const panel = document.querySelector(`[data-menu-panel="${button.dataset.menu}"]`); document.querySelectorAll('.more-menu.open').forEach(menu => { if (menu !== panel) menu.classList.remove('open'); }); panel.classList.toggle('open'); button.setAttribute('aria-expanded', panel.classList.contains('open')); return; } if (button.classList.contains('edit') || button.classList.contains('update')) { editingIndex = index; fillForm(lead); document.getElementById('drawer-title').textContent = 'Update lead'; document.getElementById('save-lead').textContent = 'Save changes'; } if (button.classList.contains('view')) alert(Object.entries(lead).map(([key, value]) => `${key.replaceAll('_',' ')}: ${value || '—'}`).join('\n')); if (button.classList.contains('remark')) prompt('Add a remark for this lead:', ''); });
            document.getElementById('logout').addEventListener('click', async () => { await fetch('/index.php/api/logout', { method:'POST' }); window.location.href = '/index.php/'; });
            loadManufacturingOptions().catch(error => { document.getElementById('manufactured_items').innerHTML = '<option value="">Unable to load items</option>'; document.getElementById('raw_materials').innerHTML = '<option value="">Unable to load materials</option>'; summary.textContent = error.message; });
            loadDirectory();
        })();
    </script>
</body>
</html>
