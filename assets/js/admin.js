// ================================================================
// assets/js/admin.js — Admin Panel JavaScript
// KDLMS - Khmer Digital Library Management System
// Author: អេង ចាន់ធឿន (Eng Chanthoeun - Vthe)
// ================================================================

'use strict';

// ================================================================
// ADMIN SIDEBAR TOGGLE (Mobile)
// ================================================================
function toggleAdminSidebar() {
    const sidebar = document.getElementById('adminSidebar');
    if (sidebar) sidebar.classList.toggle('open');
}

// Close sidebar when clicking outside on mobile
document.addEventListener('click', function(e) {
    const sidebar = document.getElementById('adminSidebar');
    const toggle  = document.querySelector('[onclick="toggleAdminSidebar()"]');
    if (sidebar && !sidebar.contains(e.target) && toggle && !toggle.contains(e.target)) {
        if (window.innerWidth < 992) sidebar.classList.remove('open');
    }
});

// ================================================================
// TOAST (admin reuse)
// ================================================================
function showAdminToast(type, message, duration = 4000) {
    const icons  = { success:'bi-check-circle-fill', error:'bi-x-circle-fill', info:'bi-info-circle-fill', warning:'bi-exclamation-triangle-fill' };
    const colors = { success:'#22C55E', error:'#DC2626', info:'#1A3A5C', warning:'#E8A020' };
    const labels = { success:'បានជោគជ័យ', error:'មានបញ្ហា', info:'ព័ត៌មាន', warning:'ការព្រមាន' };

    let container = document.getElementById('adminToastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'adminToastContainer';
        container.style.cssText = 'position:fixed;top:70px;right:16px;z-index:9999;display:flex;flex-direction:column;gap:8px;';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.style.cssText = `background:white;border-radius:12px;padding:12px 16px;min-width:280px;max-width:360px;
        box-shadow:0 8px 30px rgba(0,0,0,.15);display:flex;align-items:flex-start;gap:10px;
        border-left:4px solid ${colors[type]||colors.info};animation:slideInToast .35s ease forwards;font-family:'Inter',sans-serif;`;
    toast.innerHTML = `
        <i class="bi ${icons[type]||icons.info}" style="color:${colors[type]||colors.info};font-size:1.1rem;flex-shrink:0;margin-top:1px;"></i>
        <div style="flex:1;">
            <div style="font-weight:700;font-size:.82rem;color:#1E293B;">${labels[type]||'Info'}</div>
            <div style="font-size:.78rem;color:#64748B;margin-top:2px;">${message}</div>
        </div>
        <button onclick="this.parentElement.remove()" style="background:none;border:none;cursor:pointer;padding:0 4px;color:#94A3B8;font-size:1.1rem;line-height:1;">×</button>
    `;

    container.appendChild(toast);
    setTimeout(() => toast.remove(), duration);
}

// ================================================================
// CHART.JS INITIALIZATION — Dashboard
// ================================================================
function initDashboardCharts(downloadsData, categoryData) {
    // Bar chart: downloads last 7 days
    const barCtx = document.getElementById('downloadsBarChart');
    if (barCtx && downloadsData) {
        new Chart(barCtx, {
            type: 'bar',
            data: {
                labels: downloadsData.labels,
                datasets: [{
                    label: 'ការទាញយក (Downloads)',
                    data:  downloadsData.values,
                    backgroundColor: 'rgba(139,0,0,0.7)',
                    borderColor:     '#8B0000',
                    borderWidth: 2,
                    borderRadius: 6,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: ctx => ` ${ctx.parsed.y} ដង`
                        }
                    }
                },
                scales: {
                    x: { grid: { display: false }, ticks: { font: { family:'Inter', size:11 } } },
                    y: { beginAtZero: true, grid: { color:'rgba(0,0,0,.05)' }, ticks: { font: { family:'Inter', size:11 } } }
                }
            }
        });
    }

    // Doughnut chart: downloads by category
    const doughCtx = document.getElementById('categoryDoughnutChart');
    if (doughCtx && categoryData) {
        const COLORS = ['#8B0000','#C8960C','#1A3A5C','#1B4332','#4A1B5C','#5C3A1A','#2D6A4F','#E63946','#457B9D','#2A9D8F'];
        new Chart(doughCtx, {
            type: 'doughnut',
            data: {
                labels:   categoryData.labels,
                datasets: [{
                    data:             categoryData.values,
                    backgroundColor:  COLORS.slice(0, categoryData.values.length),
                    borderWidth:      2,
                    borderColor:      '#FFFFFF',
                    hoverBorderWidth: 3,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { font: { family:'Inter', size:10 }, boxWidth:12, padding:10 }
                    },
                    tooltip: {
                        callbacks: {
                            label: ctx => ` ${ctx.label}: ${ctx.parsed} ដង`
                        }
                    }
                }
            }
        });
    }
}

// ================================================================
// DATATABLE INIT (lightweight client-side table sort/search)
// ================================================================
function initSimpleDataTable(tableId, searchInputId) {
    const table = document.getElementById(tableId);
    const searchInput = document.getElementById(searchInputId);
    if (!table) return;

    const tbody = table.querySelector('tbody');
    const rows  = Array.from(tbody?.querySelectorAll('tr') || []);

    // Sort by column
    table.querySelectorAll('th[data-sort]').forEach(th => {
        th.style.cursor = 'pointer';
        th.addEventListener('click', function() {
            const col     = parseInt(this.dataset.sort, 10);
            const asc     = this.dataset.dir !== 'asc';
            this.dataset.dir = asc ? 'asc' : 'desc';
            table.querySelectorAll('th[data-sort]').forEach(h => h.classList.remove('sort-asc','sort-desc'));
            this.classList.add(asc ? 'sort-asc' : 'sort-desc');

            rows.sort((a, b) => {
                const av = a.cells[col]?.textContent?.trim() || '';
                const bv = b.cells[col]?.textContent?.trim() || '';
                const an = parseFloat(av.replace(/,/g, ''));
                const bn = parseFloat(bv.replace(/,/g, ''));
                if (!isNaN(an) && !isNaN(bn)) return asc ? an - bn : bn - an;
                return asc ? av.localeCompare(bv) : bv.localeCompare(av);
            });

            rows.forEach(r => tbody.appendChild(r));
        });
    });

    // Search filter
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const q = this.value.toLowerCase();
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(q) ? '' : 'none';
            });
        });
    }
}

// ================================================================
// UPLOAD DROPZONE
// ================================================================
function initUploadDropzone(dropzoneId, fileInputId, progressId) {
    const zone      = document.getElementById(dropzoneId);
    const fileInput = document.getElementById(fileInputId);
    if (!zone || !fileInput) return;

    ['dragenter','dragover'].forEach(evt => {
        zone.addEventListener(evt, e => { e.preventDefault(); zone.classList.add('drag-over'); });
    });

    ['dragleave','drop'].forEach(evt => {
        zone.addEventListener(evt, e => { e.preventDefault(); zone.classList.remove('drag-over'); });
    });

    zone.addEventListener('drop', e => {
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            fileInput.files = files;
            updateFileInfo(files[0], zone);
        }
    });

    zone.addEventListener('click', () => fileInput.click());

    fileInput.addEventListener('change', function() {
        if (this.files.length > 0) updateFileInfo(this.files[0], zone);
    });

    function updateFileInfo(file, zone) {
        const icon = zone.querySelector('.upload-dropzone-icon');
        const title = zone.querySelector('.upload-dropzone-title');
        const sub   = zone.querySelector('.upload-dropzone-sub');
        if (icon)  icon.innerHTML  = '<i class="bi bi-file-earmark-check" style="color:#1B4332;"></i>';
        if (title) title.textContent = file.name;
        if (sub)   sub.textContent  = `${(file.size / 1048576).toFixed(2)} MB — ${file.type}`;
        zone.style.borderColor = '#1B4332';
        zone.style.background  = '#F0FDF4';
    }
}

// AJAX file upload with progress bar
function uploadFileWithProgress(formId, progressWrapId, fillId, pctId) {
    const form       = document.getElementById(formId);
    const progressWrap = document.getElementById(progressWrapId);
    const fill       = document.getElementById(fillId);
    const pct        = document.getElementById(pctId);
    if (!form) return;

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(form);
        const xhr      = new XMLHttpRequest();

        if (progressWrap) progressWrap.style.display = 'block';

        xhr.upload.addEventListener('progress', function(event) {
            if (event.lengthComputable) {
                const percent = Math.round((event.loaded / event.total) * 100);
                if (fill) fill.style.width = percent + '%';
                if (pct)  pct.textContent  = percent + '%';
            }
        });

        xhr.addEventListener('load', function() {
            if (xhr.status === 200) {
                try {
                    const res = JSON.parse(xhr.responseText);
                    if (res.success) {
                        showAdminToast('success', res.message || 'ការ Upload ជោគជ័យ!');
                        setTimeout(() => { window.location.href = 'books.php'; }, 1500);
                    } else {
                        showAdminToast('error', res.message || 'Upload failed.');
                        if (progressWrap) progressWrap.style.display = 'none';
                    }
                } catch(err) {
                    showAdminToast('error', 'Server error. Check PHP logs.');
                }
            } else {
                showAdminToast('error', 'Upload failed (HTTP ' + xhr.status + ')');
            }
        });

        xhr.addEventListener('error', () => showAdminToast('error', 'Network error during upload.'));

        xhr.open('POST', form.action || window.location.href);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.send(formData);
    });
}

// ================================================================
// BULK ACTION CHECKBOXES
// ================================================================
function initBulkActions(selectAllId, rowCheckboxClass) {
    const selectAll   = document.getElementById(selectAllId);
    const rowCheckboxes = document.querySelectorAll(`.${rowCheckboxClass}`);

    if (selectAll) {
        selectAll.addEventListener('change', function() {
            rowCheckboxes.forEach(cb => cb.checked = this.checked);
            updateBulkActionBar();
        });
    }

    rowCheckboxes.forEach(cb => {
        cb.addEventListener('change', updateBulkActionBar);
    });
}

function updateBulkActionBar() {
    const checked = document.querySelectorAll('.row-check:checked').length;
    const bar     = document.getElementById('bulkActionBar');
    const countEl = document.getElementById('bulkSelectedCount');
    if (bar)     bar.style.display    = checked > 0 ? 'flex' : 'none';
    if (countEl) countEl.textContent   = checked;
}

function getCheckedIds() {
    return Array.from(document.querySelectorAll('.row-check:checked')).map(cb => cb.value);
}

// ================================================================
// CSV EXPORT (client-side from table)
// ================================================================
function exportTableToCSV(tableId, filename) {
    const table = document.getElementById(tableId);
    if (!table) return;

    const rows = Array.from(table.querySelectorAll('tr'));
    const csv  = rows.map(row => {
        const cols = Array.from(row.querySelectorAll('th, td'));
        return cols.map(col => {
            const text = col.textContent.trim().replace(/"/g, '""');
            return `"${text}"`;
        }).join(',');
    }).join('\n');

    const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' }); // BOM for Excel Khmer
    const url  = URL.createObjectURL(blob);
    const a    = document.createElement('a');
    a.href     = url;
    a.download = filename || 'export.csv';
    document.body.appendChild(a);
    a.click();
    a.remove();
    URL.revokeObjectURL(url);
    showAdminToast('success', 'CSV exported successfully!');
}

// ================================================================
// MODAL HELPERS
// ================================================================
function openModal(modalId) {
    const overlay = document.getElementById(modalId);
    if (overlay) {
        overlay.classList.add('open');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const overlay = document.getElementById(modalId);
    if (overlay) {
        overlay.classList.remove('open');
        document.body.style.overflow = '';
    }
}

// Close modal when clicking overlay backdrop
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('admin-modal-overlay')) {
        e.target.classList.remove('open');
        document.body.style.overflow = '';
    }
});

// Escape key closes modals
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.admin-modal-overlay.open').forEach(m => {
            m.classList.remove('open');
        });
        document.body.style.overflow = '';
    }
});

// ================================================================
// DELETE CONFIRMATION
// ================================================================
function confirmDelete(action, id, name) {
    const confirmed = window.confirm(`⚠️ តើអ្នកប្រាកដចង់លុប "${name}" មែនទេ?\nAre you sure you want to delete "${name}"?`);
    if (confirmed) {
        window.location.href = `${action}?id=${id}&action=delete&csrf=${document.getElementById('csrf_token_js')?.value || ''}`;
    }
}

// ================================================================
// COLOR PICKER PREVIEW
// ================================================================
document.addEventListener('input', function(e) {
    if (e.target.type === 'color' && e.target.dataset.preview) {
        const preview = document.getElementById(e.target.dataset.preview);
        if (preview) preview.style.background = e.target.value;
    }
});

// Add slideInToast keyframe
(function addAdminKeyframes() {
    if (document.getElementById('adminKeyframes')) return;
    const style = document.createElement('style');
    style.id = 'adminKeyframes';
    style.textContent = `
        @keyframes slideInToast {
            from { transform:translateX(110%); opacity:0; }
            to   { transform:translateX(0);   opacity:1; }
        }
        th.sort-asc::after  { content:' ↑'; color:#8B0000; }
        th.sort-desc::after { content:' ↓'; color:#8B0000; }
    `;
    document.head.appendChild(style);
})();
