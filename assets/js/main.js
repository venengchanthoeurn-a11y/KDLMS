// ================================================================
// assets/js/main.js — Public Site JavaScript
// KDLMS - Khmer Digital Library Management System
// Author: អេង ចាន់ធឿន (Eng Chanthoeun - Vthe)
// ================================================================

'use strict';

// ================================================================
// NAVBAR SCROLL EFFECT
// ================================================================
(function initNavbarScroll() {
    const navbar = document.getElementById('kdlmsNavbar');
    if (!navbar) return;

    function handleScroll() {
        if (window.scrollY > 50) {
            navbar.classList.remove('transparent');
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
            navbar.classList.add('transparent');
        }
    }

    window.addEventListener('scroll', handleScroll, { passive: true });
    handleScroll(); // initial call
})();

// ================================================================
// MOBILE NAV TOGGLE
// ================================================================
function toggleMobileNav() {
    const mobileNav = document.getElementById('mobileNav');
    if (!mobileNav) return;
    const isOpen = mobileNav.style.display !== 'none';
    mobileNav.style.display = isOpen ? 'none' : 'block';
}

// ================================================================
// TOAST NOTIFICATION SYSTEM
// ================================================================
const TOAST_ICONS = {
    success: '<i class="bi bi-check-circle-fill" style="color:#22C55E;"></i>',
    error:   '<i class="bi bi-x-circle-fill" style="color:#DC2626;"></i>',
    info:    '<i class="bi bi-info-circle-fill" style="color:#1A3A5C;"></i>',
    warning: '<i class="bi bi-exclamation-triangle-fill" style="color:#E8A020;"></i>',
};

const TOAST_LABELS = {
    success: 'បានជោគជ័យ (Success)',
    error:   'មានបញ្ហា (Error)',
    info:    'ព័ត៌មាន (Info)',
    warning: 'ការព្រមាន (Warning)',
};

function showToast(type, message, duration = 4000) {
    const container = document.getElementById('toastContainer');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = `kdlms-toast ${type}`;
    toast.innerHTML = `
        <span class="toast-icon">${TOAST_ICONS[type] || TOAST_ICONS.info}</span>
        <div class="toast-body">
            <div class="toast-title">${TOAST_LABELS[type] || 'Info'}</div>
            <div class="toast-msg">${message}</div>
        </div>
        <button onclick="removeToast(this.parentElement)" style="background:none;border:none;cursor:pointer;padding:2px 6px;color:#94A3B8;font-size:1rem;margin-left:4px;">×</button>
    `;

    container.appendChild(toast);

    setTimeout(() => removeToast(toast), duration);
}

function removeToast(toast) {
    if (!toast || !toast.parentElement) return;
    toast.classList.add('removing');
    setTimeout(() => toast.remove(), 350);
}

// ================================================================
// ANIMATED COUNTER (Stats section)
// ================================================================
function animateCounter(el, target, duration = 1500) {
    const start = 0;
    const step  = (timestamp) => {
        if (!el._startTime) el._startTime = timestamp;
        const progress = Math.min((timestamp - el._startTime) / duration, 1);
        const eased    = 1 - Math.pow(1 - progress, 3); // ease-out cubic
        el.textContent = Math.floor(eased * target).toLocaleString();
        if (progress < 1) requestAnimationFrame(step);
        else el.textContent = target.toLocaleString();
    };
    requestAnimationFrame(step);
}

// Trigger counters when they enter viewport
(function initCounters() {
    const counters = document.querySelectorAll('[data-counter]');
    if (!counters.length) return;

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const el     = entry.target;
                const target = parseInt(el.dataset.counter, 10);
                animateCounter(el, target);
                observer.unobserve(el);
            }
        });
    }, { threshold: 0.5 });

    counters.forEach(el => observer.observe(el));
})();

// ================================================================
// LAZY IMAGE LOADING
// ================================================================
(function initLazyImages() {
    if ('IntersectionObserver' in window) {
        const imgObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    if (img.dataset.src) {
                        img.src = img.dataset.src;
                        img.removeAttribute('data-src');
                    }
                    imgObserver.unobserve(img);
                }
            });
        }, { rootMargin: '200px' });

        document.querySelectorAll('img[data-src]').forEach(img => imgObserver.observe(img));
    } else {
        // Fallback: load all immediately
        document.querySelectorAll('img[data-src]').forEach(img => {
            img.src = img.dataset.src;
        });
    }
})();

// ================================================================
// BROWSE PAGE — AJAX SEARCH & FILTER
// ================================================================
let browseXHR = null;
let browseDebounceTimer = null;

function triggerBrowseFilter() {
    clearTimeout(browseDebounceTimer);
    browseDebounceTimer = setTimeout(executeBrowseFilter, 350);
}

function executeBrowseFilter(page = 1) {
    const resultsContainer = document.getElementById('booksGrid');
    const paginationWrap   = document.getElementById('paginationWrap');
    const resultsCount     = document.getElementById('resultsCount');
    if (!resultsContainer) return;

    const search   = document.getElementById('searchInput')?.value     || '';
    const catId    = document.getElementById('categoryFilter')?.value  || '';
    const lang     = document.getElementById('langFilter')?.value      || '';
    const ext      = document.getElementById('extFilter')?.value       || '';
    const sort     = document.getElementById('sortFilter')?.value      || 'newest';

    // Show loading skeleton
    resultsContainer.innerHTML = Array(4).fill(0).map(() => `
        <div class="book-card" style="animation:none;">
            <div style="background:#E2E8F0;aspect-ratio:3/4;border-radius:8px 8px 0 0;animation:pulse 1.5s ease infinite;"></div>
            <div class="book-body gap-2">
                <div style="height:12px;background:#E2E8F0;border-radius:4px;animation:pulse 1.5s ease infinite;"></div>
                <div style="height:10px;background:#F1F5F9;border-radius:4px;width:70%;animation:pulse 1.5s ease infinite;"></div>
                <div style="height:30px;background:#E2E8F0;border-radius:8px;margin-top:auto;animation:pulse 1.5s ease infinite;"></div>
            </div>
        </div>
    `).join('');

    // Cancel existing request
    if (browseXHR) browseXHR.abort();

    browseXHR = new XMLHttpRequest();
    const params = new URLSearchParams({ search, category_id: catId, language: lang, extension: ext, sort, page, ajax: 1 });
    browseXHR.open('GET', `browse.php?${params.toString()}`);
    browseXHR.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

    browseXHR.onload = function() {
        if (browseXHR.status === 200) {
            try {
                const data = JSON.parse(browseXHR.responseText);
                renderBookCards(data.books, resultsContainer);
                renderPagination(data, paginationWrap);
                if (resultsCount) {
                    resultsCount.textContent = `${data.total.toLocaleString()} ឯកសារ`;
                }
            } catch(e) {
                resultsContainer.innerHTML = '<div class="empty-state"><div class="empty-state-icon"><i class="bi bi-exclamation-circle"></i></div><div class="empty-state-title">មានបញ្ហាបច្ចេកទេស</div></div>';
            }
        }
    };

    browseXHR.send();
}

function renderBookCards(books, container) {
    if (!books || books.length === 0) {
        container.innerHTML = `
            <div class="empty-state" style="grid-column:1/-1;">
                <div class="empty-state-icon"><i class="bi bi-search"></i></div>
                <div class="empty-state-title">រកមិនឃើញឯកសារ</div>
                <div style="font-size:.82rem;color:#9CA3AF;font-family:'Kantumruy Pro',sans-serif;">
                    No documents found matching your search.
                </div>
            </div>`;
        return;
    }

    container.innerHTML = books.map(b => buildBookCard(b)).join('');

    // Re-init lazy images for new cards
    if ('IntersectionObserver' in window) {
        const imgObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    if (img.dataset.src) { img.src = img.dataset.src; img.removeAttribute('data-src'); }
                    imgObserver.unobserve(img);
                }
            });
        }, { rootMargin: '200px' });
        container.querySelectorAll('img[data-src]').forEach(img => imgObserver.observe(img));
    }
}

function buildBookCard(b) {
    const extBadgeMap = { pdf:'danger', docx:'primary', doc:'primary', epub:'success', txt:'secondary', zip:'warning', mp4:'info', pptx:'warning', xlsx:'success' };
    const badgeClass  = extBadgeMap[b.file_extension?.toLowerCase()] || 'dark';
    const coverSrc    = b.cover_image ? `covers/${b.cover_image}` : null;
    const fileSizeFmt = formatFileSizeJS(parseInt(b.file_size, 10));

    const coverHTML = coverSrc
        ? `<img data-src="${coverSrc}" src="data:image/gif;base64,R0lGODlhAQABAAAAACH5BAALAAAAALAAAAABAAEAAAI=" alt="${escapeHtml(b.title_kh)}" loading="lazy">`
        : `<div class="book-cover-placeholder">
              <i class="bi bi-file-earmark-text"></i>
              <span class="placeholder-title">${escapeHtml(b.title_kh?.substring(0, 30) || '')}</span>
           </div>`;

    return `
    <div class="book-card">
        <div class="book-cover-wrap">
            ${coverHTML}
            <span class="badge bg-${badgeClass} book-ext-badge">${escapeHtml(b.file_extension?.toUpperCase() || '')}</span>
            ${b.is_featured == 1 ? '<span class="book-featured-badge"><i class="bi bi-star-fill me-1"></i>Featured</span>' : ''}
        </div>
        <div class="book-body">
            <div class="book-category-tag">${escapeHtml(b.cat_name_kh || '')}</div>
            <div class="book-title-kh" title="${escapeHtml(b.title_kh)}">${escapeHtml(b.title_kh)}</div>
            ${b.author ? `<div class="book-author"><i class="bi bi-person me-1"></i>${escapeHtml(b.author)}</div>` : ''}
            <div class="book-meta">
                <span class="book-size">${fileSizeFmt}</span>
                <span class="book-downloads"><i class="bi bi-download"></i>${parseInt(b.download_count, 10).toLocaleString()}</span>
            </div>
            <a href="download.php?id=${b.id}" class="btn-download mt-2" onclick="return confirmDownload(event, this.href)">
                <i class="bi bi-download"></i> ⬇ ទាញយក
            </a>
        </div>
    </div>`;
}

function renderPagination(data, container) {
    if (!container) return;
    if (data.total_pages <= 1) { container.innerHTML = ''; return; }

    let html = '<div class="kdlms-pagination">';

    // Prev button
    if (data.page > 1) {
        html += `<button class="page-btn" onclick="executeBrowseFilter(${data.page - 1})"><i class="bi bi-chevron-left"></i></button>`;
    }

    // Page buttons (show max 7 around current)
    const start = Math.max(1, data.page - 3);
    const end   = Math.min(data.total_pages, data.page + 3);

    if (start > 1) html += `<button class="page-btn" onclick="executeBrowseFilter(1)">1</button><span class="page-btn" style="border:none;cursor:default;">…</span>`;

    for (let p = start; p <= end; p++) {
        html += `<button class="page-btn${p === data.page ? ' active' : ''}" onclick="executeBrowseFilter(${p})">${p}</button>`;
    }

    if (end < data.total_pages) html += `<span class="page-btn" style="border:none;cursor:default;">…</span><button class="page-btn" onclick="executeBrowseFilter(${data.total_pages})">${data.total_pages}</button>`;

    // Next
    if (data.page < data.total_pages) {
        html += `<button class="page-btn" onclick="executeBrowseFilter(${data.page + 1})"><i class="bi bi-chevron-right"></i></button>`;
    }

    html += '</div>';
    container.innerHTML = html;
}

// ================================================================
// DOWNLOAD CONFIRM (if not logged in — handled server side, but UX)
// ================================================================
function confirmDownload(event, url) {
    // Just proceed — server will redirect to login if needed
    return true;
}

// ================================================================
// CATEGORY FILTER CLICK (sidebar)
// ================================================================
function filterByCategory(catId, el) {
    document.querySelectorAll('.sidebar-cat-item').forEach(item => item.classList.remove('active'));
    if (el) el.classList.add('active');

    const catFilter = document.getElementById('categoryFilter');
    if (catFilter) {
        catFilter.value = catId;
        executeBrowseFilter(1);
    }
}

// ================================================================
// UTILITIES
// ================================================================
function escapeHtml(str) {
    const div = document.createElement('div');
    div.appendChild(document.createTextNode(str || ''));
    return div.innerHTML;
}

function formatFileSizeJS(bytes) {
    if (bytes >= 1073741824) return (bytes / 1073741824).toFixed(2) + ' GB';
    if (bytes >= 1048576)    return (bytes / 1048576).toFixed(2)    + ' MB';
    if (bytes >= 1024)       return (bytes / 1024).toFixed(2)       + ' KB';
    return bytes + ' B';
}

// Add pulse keyframe via JS if not in CSS
(function addPulseKeyframe() {
    if (document.getElementById('kdlmsPulseStyle')) return;
    const style = document.createElement('style');
    style.id = 'kdlmsPulseStyle';
    style.textContent = `
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50%       { opacity: 0.5; }
        }
    `;
    document.head.appendChild(style);
})();
