import Alpine from 'alpinejs';
import Sortable from 'sortablejs';
import {
    createIcons,
    LayoutDashboard,
    Images,
    LogOut,
    Plus,
    Trash2,
    RefreshCw,
    FolderPlus,
    Folder,
    UploadCloud,
    ArrowLeft,
    ArrowRight,
    ExternalLink,
    Check,
    CheckCircle,
    AlertTriangle,
    X,
    Star,
    Edit3,
    Clock,
    Eye,
    Download,
    Share2,
    Info,
    Calendar,
    Lock,
    Save,
    Archive,
    Settings,
    BarChart3,
    ImagePlus,
    ChevronDown,
    Loader2
} from 'lucide';

const lucideIcons = {
    LayoutDashboard,
    Images,
    LogOut,
    Plus,
    Trash2,
    RefreshCw,
    FolderPlus,
    Folder,
    UploadCloud,
    ArrowLeft,
    ArrowRight,
    ExternalLink,
    Check,
    CheckCircle,
    AlertTriangle,
    X,
    Star,
    Edit3,
    Clock,
    Eye,
    Download,
    Share2,
    Info,
    Calendar,
    Lock,
    Save,
    Archive,
    Settings,
    BarChart3,
    ImagePlus,
    ChevronDown,
    Loader2
};

export function initLucideIcons() {
    createIcons({ icons: lucideIcons });
}

window.Alpine = Alpine;
Alpine.start();

document.addEventListener('DOMContentLoaded', () => {
    initLucideIcons();

    // 1. Tab Navigation logic
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');

    // Restore tab from URL if present
    const urlParams = new URLSearchParams(window.location.search);
    const activeTab = urlParams.get('tab') || 'gallery';

    let hasUploadedNewFiles = false;

    function switchTab(tabId) {
        // If files were uploaded, redirect to gallery tab to get fresh server HTML
        if (tabId === 'gallery' && hasUploadedNewFiles) {
            window.location.href = window.location.pathname + '?tab=gallery';
            return;
        }

        tabButtons.forEach(btn => {
            if (btn.dataset.tab === tabId) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        });

        tabContents.forEach(content => {
            if (content.id === `${tabId}-tab`) {
                content.classList.add('active');
            } else {
                content.classList.remove('active');
            }
        });
        
        // Update URL state silently
        const newUrl = new URL(window.location.href);
        newUrl.searchParams.set('tab', tabId);
        window.history.replaceState({}, '', newUrl);

        initLucideIcons();
    }

    tabButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            switchTab(btn.dataset.tab);
        });
    });

    if (activeTab) {
        switchTab(activeTab);
    }

    // 2. Drag and Drop Sorting for Galleries Sidebar
    const galleriesList = document.getElementById('galleries-list');
    if (galleriesList) {
        const projectSlug = galleriesList.dataset.projectSlug;
        const projectId = galleriesList.dataset.projectId;
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        Sortable.create(galleriesList, {
            animation: 150,
            delay: 200,
            delayOnTouchOnly: true,
            touchStartThreshold: 5,
            onEnd: function () {
                const order = Array.from(galleriesList.querySelectorAll('.gallery-tab-item')).map(item => item.dataset.id);
                
                fetch(`/admin/projects/${projectId}/galleries/sort`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ order })
                })
                .then(res => res.json())
                .then(data => {
                    if (!data.success) {
                        alert('Failed to save gallery order.');
                    }
                })
                .catch(() => alert('Network error while saving gallery order.'));
            }
        });
    }

    // 3. Drag and Drop Sorting for Photo Grids
    const photoGrid = document.getElementById('photo-grid');
    if (photoGrid) {
        const projectId = photoGrid.dataset.projectId;
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        Sortable.create(photoGrid, {
            animation: 150,
            delay: 200,
            delayOnTouchOnly: true,
            touchStartThreshold: 5,
            onEnd: function () {
                const order = Array.from(photoGrid.querySelectorAll('.photo-item')).map(item => item.dataset.id);
                
                fetch(`/admin/projects/${projectId}/photos/sort`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ order })
                })
                .then(res => res.json())
                .then(data => {
                    if (!data.success) {
                        alert('Failed to save photo order.');
                    }
                })
                .catch(() => alert('Network error while saving photo order.'));
            }
        });
    }

    // 4. Folder/File Upload logic & Dashboard
    const dropzone = document.getElementById('upload-dropzone');
    const folderInput = document.getElementById('folder-upload-input');
    const fileInput = document.getElementById('file-upload-input');
    const btnBrowseFolders = document.getElementById('btn-browse-folders');
    const btnBrowseFiles = document.getElementById('btn-browse-files');

    const dashboard = document.getElementById('upload-dashboard');
    const completeBanner = document.getElementById('upload-complete-banner');
    const completeTitle = document.getElementById('complete-title');
    const completeSubtitle = document.getElementById('complete-subtitle');
    const btnGotoGallery = document.getElementById('btn-goto-gallery');

    const summaryStatusText = document.getElementById('summary-status-text');
    const summaryCountsText = document.getElementById('summary-counts-text');
    const summaryPercentText = document.getElementById('summary-percent-text');
    const summaryProgressBar = document.getElementById('summary-progress-bar');

    const activeCard = document.getElementById('upload-active-card');
    const activeFilename = document.getElementById('active-filename');
    const activeFolderBadge = document.getElementById('active-folder-badge');
    const activeFileProgressBar = document.getElementById('active-file-progress-bar');
    const activeFilePercent = document.getElementById('active-file-percent');
    const activeSpinner = document.getElementById('active-spinner');

    const foldersList = document.getElementById('folders-progress-list');
    const toggleFileDetails = document.getElementById('toggle-file-details');
    const detailsToggleText = document.getElementById('details-toggle-text');
    const fileDetailsBody = document.getElementById('file-details-body');
    const progressList = document.getElementById('progress-list');

    const activeGalleryUploadInput = document.getElementById('active-gallery-upload-input');
    const btnActiveGalleryUpload = document.getElementById('btn-active-gallery-upload');
    const targetGallerySelect = document.getElementById('target-gallery-select');

    if (dropzone) {
        const projectId = dropzone.dataset.projectId;
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const MAX_RETRIES = 2;

        const uploadState = {
            queue: [],
            currentIndex: 0,
            isUploading: false,
            folderStats: new Map(), // galleryName -> { total: 0, completed: 0, failed: 0 }
            completedFiles: 0,
            failedFiles: 0,
        };

        const isImage = (filename) => {
            const ext = filename.split('.').pop().toLowerCase();
            return ['jpg', 'jpeg', 'png', 'webp'].includes(ext);
        };

        function slugify(text) {
            return encodeURIComponent(text).replace(/%/g, '_');
        }

        function getTargetGallery() {
            if (targetGallerySelect && targetGallerySelect.value) {
                const galleryId = targetGallerySelect.value;
                const opt = targetGallerySelect.options[targetGallerySelect.selectedIndex];
                const galleryName = opt.dataset.galleryTitle || opt.text.replace(/\s*\(\d+.*?\)$/, '').trim();
                return { galleryId, galleryName };
            }
            return { galleryId: null, galleryName: 'Unsorted' };
        }

        // Active gallery upload button in Gallery Tabs
        if (btnActiveGalleryUpload && activeGalleryUploadInput) {
            btnActiveGalleryUpload.addEventListener('click', () => {
                activeGalleryUploadInput.click();
            });
        }

        if (activeGalleryUploadInput) {
            activeGalleryUploadInput.addEventListener('change', () => {
                const files = Array.from(activeGalleryUploadInput.files);
                const galleryId = activeGalleryUploadInput.dataset.galleryId;
                const galleryTitle = activeGalleryUploadInput.dataset.galleryTitle || 'Gallery';
                const items = [];

                files.forEach(file => {
                    if (isImage(file.name)) {
                        items.push({
                            file,
                            galleryName: galleryTitle,
                            galleryId: galleryId
                        });
                    }
                });

                activeGalleryUploadInput.value = '';

                if (items.length > 0) {
                    if (targetGallerySelect) {
                        targetGallerySelect.value = galleryId;
                    }
                    switchTab('upload');
                    enqueueItems(items);
                }
            });
        }

        // Toggle detailed file list accordion
        if (toggleFileDetails && fileDetailsBody) {
            toggleFileDetails.addEventListener('click', () => {
                const isHidden = fileDetailsBody.style.display === 'none';
                fileDetailsBody.style.display = isHidden ? 'block' : 'none';
                toggleFileDetails.classList.toggle('expanded', isHidden);
            });
        }

        // Action button: Go to Gallery Tabs
        if (btnGotoGallery) {
            btnGotoGallery.addEventListener('click', () => {
                window.location.href = window.location.pathname + '?tab=gallery';
            });
        }

        // Browse buttons
        if (btnBrowseFolders) {
            btnBrowseFolders.addEventListener('click', (e) => {
                e.stopPropagation();
                folderInput.click();
            });
        }

        if (btnBrowseFiles) {
            btnBrowseFiles.addEventListener('click', (e) => {
                e.stopPropagation();
                fileInput.click();
            });
        }

        // Dropzone click default: open folder picker
        dropzone.addEventListener('click', () => {
            folderInput.click();
        });

        // Drag & Drop handlers
        dropzone.addEventListener('dragover', (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropzone.classList.add('dragover');
        });

        dropzone.addEventListener('dragleave', (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropzone.classList.remove('dragover');
        });

        dropzone.addEventListener('drop', async (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropzone.classList.remove('dragover');

            const items = await scanDroppedItems(e.dataTransfer);
            if (items.length > 0) {
                enqueueItems(items);
            }
        });

        // Input change handlers
        folderInput.addEventListener('change', () => {
            const files = Array.from(folderInput.files);
            const items = [];
            files.forEach(file => {
                if (isImage(file.name)) {
                    let galleryName = 'Unsorted';
                    if (file.webkitRelativePath) {
                        const parts = file.webkitRelativePath.split('/');
                        if (parts.length > 1) {
                            galleryName = parts[0];
                        }
                    }
                    items.push({ file, galleryName, galleryId: null });
                }
            });
            folderInput.value = '';
            if (items.length > 0) {
                enqueueItems(items);
            }
        });

        fileInput.addEventListener('change', () => {
            const files = Array.from(fileInput.files);
            const items = [];
            const target = getTargetGallery();
            files.forEach(file => {
                if (isImage(file.name)) {
                    items.push({
                        file,
                        galleryName: target.galleryName,
                        galleryId: target.galleryId
                    });
                }
            });
            fileInput.value = '';
            if (items.length > 0) {
                enqueueItems(items);
            }
        });

        // Recursive directory scanner using HTML5 FileSystem API
        async function scanDroppedItems(dataTransfer) {
            const fileItems = [];
            const items = dataTransfer.items;

            const readAllEntries = (dirReader) => {
                return new Promise((resolve) => {
                    const entries = [];
                    const readBatch = () => {
                        dirReader.readEntries((batch) => {
                            if (!batch || batch.length === 0) {
                                resolve(entries);
                            } else {
                                entries.push(...batch);
                                readBatch();
                            }
                        }, (err) => {
                            console.warn('readEntries error:', err);
                            resolve(entries);
                        });
                    };
                    readBatch();
                });
            };

            const getFile = (fileEntry) => {
                return new Promise((resolve) => {
                    fileEntry.file(
                        (file) => resolve(file),
                        (err) => {
                            console.warn('getFile error:', err);
                            resolve(null);
                        }
                    );
                });
            };

            const traverseEntry = async (entry, topFolderName) => {
                if (!entry) return;

                if (entry.isFile) {
                    if (isImage(entry.name)) {
                        const file = await getFile(entry);
                        if (file) {
                            let galleryName = topFolderName;
                            let galleryId = null;
                            if (!galleryName) {
                                const target = getTargetGallery();
                                galleryName = target.galleryName;
                                galleryId = target.galleryId;
                            }
                            fileItems.push({
                                file,
                                galleryName,
                                galleryId
                            });
                        }
                    }
                } else if (entry.isDirectory) {
                    const folderName = topFolderName || entry.name;
                    const reader = entry.createReader();
                    const entries = await readAllEntries(reader);
                    for (const child of entries) {
                        await traverseEntry(child, folderName);
                    }
                }
            };

            if (items && items.length > 0 && items[0].webkitGetAsEntry) {
                const promises = [];
                for (let i = 0; i < items.length; i++) {
                    const item = items[i];
                    if (item.kind === 'file') {
                        const entry = item.webkitGetAsEntry();
                        if (entry) {
                            promises.push(traverseEntry(entry, null));
                        }
                    }
                }
                await Promise.all(promises);
            } else if (dataTransfer.files && dataTransfer.files.length > 0) {
                const target = getTargetGallery();
                Array.from(dataTransfer.files).forEach(file => {
                    if (isImage(file.name)) {
                        let galleryName = target.galleryName;
                        let galleryId = target.galleryId;
                        if (file.webkitRelativePath) {
                            const parts = file.webkitRelativePath.split('/');
                            if (parts.length > 1) {
                                galleryName = parts[0];
                                galleryId = null;
                            }
                        }
                        fileItems.push({ file, galleryName, galleryId });
                    }
                });
            }

            return fileItems;
        }

        // Add items to upload queue
        function enqueueItems(items) {
            if (!items || items.length === 0) return;

            // Make dashboard visible and hide complete banner if re-adding
            dashboard.style.display = 'flex';
            completeBanner.style.display = 'none';

            items.forEach((it, idx) => {
                const id = `item-${Date.now()}-${uploadState.queue.length + idx}`;
                const galleryName = (it.galleryName || 'Unsorted').trim();

                const queueItem = {
                    id,
                    file: it.file,
                    galleryName,
                    galleryId: it.galleryId || null,
                    status: 'queued',
                    retries: 0
                };
                uploadState.queue.push(queueItem);

                // Update folder statistics
                if (!uploadState.folderStats.has(galleryName)) {
                    uploadState.folderStats.set(galleryName, { total: 0, completed: 0, failed: 0 });
                }
                uploadState.folderStats.get(galleryName).total++;

                // Append item to detailed files log
                const fileHtml = `
                    <div class="upload-file-progress" id="file-${id}">
                        <div class="upload-file-info">
                            <span class="file-status-icon queued" id="icon-${id}"><i data-lucide="clock" style="width:12px; height:12px;"></i></span>
                            <span style="font-weight: 500;" title="${it.file.name}">${it.file.name}</span>
                            <small style="opacity:0.6">(${galleryName})</small>
                        </div>
                        <span class="progress-status-text" id="status-${id}" style="color:var(--text-muted); font-size:12px;">Queued</span>
                    </div>
                `;
                progressList.insertAdjacentHTML('beforeend', fileHtml);
            });

            initLucideIcons();

            // Update folder cards in UI
            renderFolderCards();

            // Update summary
            updateOverallSummary();

            // Start queue if idle
            if (!uploadState.isUploading) {
                processNext();
            }
        }

        function renderFolderCards() {
            let hasNewCards = false;
            uploadState.folderStats.forEach((stats, galleryName) => {
                const slug = slugify(galleryName);
                let card = document.getElementById(`folder-card-${slug}`);

                const done = stats.completed + stats.failed;
                const pct = stats.total > 0 ? Math.round((stats.completed / stats.total) * 100) : 0;
                const isAllDone = done === stats.total;

                let badgeText = 'Queued';
                let badgeClass = 'badge-queued';
                let cardClass = '';

                if (isAllDone) {
                    badgeText = stats.failed === 0 ? 'Completed' : `${stats.failed} Failed`;
                    badgeClass = stats.failed === 0 ? 'badge-done' : 'badge-queued';
                    cardClass = stats.failed === 0 ? 'done' : '';
                } else if (done > 0 || (uploadState.isUploading && uploadState.queue[uploadState.currentIndex]?.galleryName === galleryName)) {
                    badgeText = 'Uploading...';
                    badgeClass = 'badge-uploading';
                    cardClass = 'active';
                }

                if (!card) {
                    hasNewCards = true;
                    const cardHtml = `
                        <div class="folder-progress-card ${cardClass}" id="folder-card-${slug}">
                            <div class="folder-card-top">
                                <span class="folder-card-name" title="${galleryName}"><i data-lucide="folder" style="width:14px; height:14px; margin-right:5px;"></i>${galleryName}</span>
                                <span class="folder-card-badge ${badgeClass}" id="folder-badge-${slug}">${badgeText}</span>
                            </div>
                            <div class="progress-bar-bg">
                                <div class="progress-bar-fill ${isAllDone ? 'success' : ''}" id="folder-bar-${slug}" style="width: ${pct}%"></div>
                            </div>
                            <div class="folder-card-counts">
                                <span id="folder-count-${slug}">${stats.completed} / ${stats.total} photos</span>
                                <span id="folder-pct-${slug}">${pct}%</span>
                            </div>
                        </div>
                    `;
                    foldersList.insertAdjacentHTML('beforeend', cardHtml);
                } else {
                    card.className = `folder-progress-card ${cardClass}`;
                    const badge = document.getElementById(`folder-badge-${slug}`);
                    const bar = document.getElementById(`folder-bar-${slug}`);
                    const count = document.getElementById(`folder-count-${slug}`);
                    const pctEl = document.getElementById(`folder-pct-${slug}`);

                    if (badge) {
                        badge.textContent = badgeText;
                        badge.className = `folder-card-badge ${badgeClass}`;
                    }
                    if (bar) {
                        bar.style.width = `${pct}%`;
                        bar.className = `progress-bar-fill ${isAllDone ? 'success' : ''}`;
                    }
                    if (count) count.textContent = `${stats.completed} / ${stats.total} photos`;
                    if (pctEl) pctEl.textContent = `${pct}%`;
                }
            });

            if (hasNewCards) {
                initLucideIcons();
            }
        }

        function updateOverallSummary() {
            const total = uploadState.queue.length;
            const completed = uploadState.completedFiles;
            const failed = uploadState.failedFiles;
            const done = completed + failed;
            const pct = total > 0 ? Math.round((done / total) * 100) : 0;

            summaryProgressBar.style.width = `${pct}%`;
            summaryPercentText.textContent = `${pct}%`;

            if (detailsToggleText) {
                detailsToggleText.textContent = `Show detailed file list (${total} files)`;
            }

            if (done === total && total > 0) {
                if (failed === 0) {
                    summaryStatusText.textContent = 'All files uploaded';
                    summaryCountsText.textContent = `${completed} of ${total} photos`;
                    summaryProgressBar.className = 'progress-bar-fill success';
                } else {
                    summaryStatusText.textContent = 'Upload completed with errors';
                    summaryCountsText.textContent = `${completed} succeeded, ${failed} failed`;
                    summaryProgressBar.className = 'progress-bar-fill warning';
                }
            } else {
                summaryStatusText.textContent = 'Uploading files...';
                summaryCountsText.textContent = `${done} / ${total} photos ${failed > 0 ? `(${failed} failed)` : ''}`;
            }
        }

        function processNext() {
            if (uploadState.currentIndex >= uploadState.queue.length) {
                // Upload queue is completed!
                uploadState.isUploading = false;
                hasUploadedNewFiles = true;

                updateOverallSummary();
                renderFolderCards();

                // Hide active card and show complete banner
                activeCard.style.display = 'none';
                completeBanner.style.display = 'flex';

                const total = uploadState.queue.length;
                const completed = uploadState.completedFiles;
                const foldersCount = uploadState.folderStats.size;

                if (uploadState.failedFiles === 0) {
                    completeTitle.textContent = 'Upload Completed!';
                    completeSubtitle.textContent = `Successfully uploaded ${completed} photo${completed === 1 ? '' : 's'} across ${foldersCount} ${foldersCount === 1 ? 'gallery' : 'galleries'}.`;
                } else {
                    completeTitle.textContent = 'Upload Completed with warnings';
                    completeSubtitle.textContent = `${completed} of ${total} photos uploaded across ${foldersCount} ${foldersCount === 1 ? 'gallery' : 'galleries'} (${uploadState.failedFiles} failed).`;
                }
                return;
            }

            uploadState.isUploading = true;
            const currentItem = uploadState.queue[uploadState.currentIndex];
            uploadState.currentIndex++;

            uploadSingleFile(currentItem);
        }

        function uploadSingleFile(item) {
            activeCard.style.display = 'block';
            activeFilename.textContent = item.file.name;
            activeFilename.title = item.file.name;
            activeFolderBadge.textContent = item.galleryName;
            activeFileProgressBar.style.width = '0%';
            activeFileProgressBar.className = 'progress-bar-fill';
            activeFilePercent.textContent = '0%';
            if (activeSpinner) activeSpinner.style.display = 'inline-block';

            const iconEl = document.getElementById(`icon-${item.id}`);
            const statusEl = document.getElementById(`status-${item.id}`);
            if (iconEl) {
                iconEl.innerHTML = '<i data-lucide="loader-2" class="lucide-spin" style="width:12px; height:12px;"></i>';
                iconEl.className = 'file-status-icon uploading';
                initLucideIcons();
            }
            if (statusEl) {
                statusEl.textContent = 'Uploading...';
                statusEl.style.color = 'var(--accent)';
            }

            renderFolderCards();

            const formData = new FormData();
            formData.append('file', item.file);
            if (item.galleryId) {
                formData.append('gallery_id', item.galleryId);
            }
            formData.append('gallery_name', item.galleryName);

            const xhr = new XMLHttpRequest();
            xhr.open('POST', `/admin/projects/${projectId}/upload`, true);
            xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);

            xhr.upload.onprogress = (event) => {
                if (event.lengthComputable) {
                    const percent = Math.round((event.loaded / event.total) * 100);
                    activeFileProgressBar.style.width = `${percent}%`;
                    activeFilePercent.textContent = `${percent}%`;
                }
            };

            xhr.onload = () => {
                if (xhr.status === 200) {
                    uploadState.completedFiles++;
                    hasUploadedNewFiles = true;
                    item.status = 'done';

                    if (uploadState.folderStats.has(item.galleryName)) {
                        uploadState.folderStats.get(item.galleryName).completed++;
                    }

                    if (iconEl) {
                        iconEl.innerHTML = '<i data-lucide="check" style="width:12px; height:12px;"></i>';
                        iconEl.className = 'file-status-icon done';
                        initLucideIcons();
                    }
                    if (statusEl) {
                        statusEl.textContent = 'Done';
                        statusEl.style.color = 'var(--success)';
                    }

                    updateOverallSummary();
                    renderFolderCards();
                    processNext();
                } else {
                    handleUploadFailure(item, xhr.status);
                }
            };

            xhr.onerror = () => {
                handleUploadFailure(item, 'Network');
            };

            function handleUploadFailure(failedItem, errorCode) {
                if (failedItem.retries < MAX_RETRIES) {
                    failedItem.retries++;
                    if (statusEl) {
                        statusEl.textContent = `Retry ${failedItem.retries}/${MAX_RETRIES}...`;
                        statusEl.style.color = 'var(--warning)';
                    }
                    setTimeout(() => uploadSingleFile(failedItem), 1500);
                } else {
                    uploadState.failedFiles++;
                    failedItem.status = 'failed';

                    if (uploadState.folderStats.has(failedItem.galleryName)) {
                        uploadState.folderStats.get(failedItem.galleryName).failed++;
                    }

                    if (iconEl) {
                        iconEl.innerHTML = '<i data-lucide="x" style="width:12px; height:12px;"></i>';
                        iconEl.className = 'file-status-icon failed';
                        initLucideIcons();
                    }
                    if (statusEl) {
                        statusEl.textContent = `Failed (${errorCode})`;
                        statusEl.style.color = 'var(--danger)';
                    }

                    updateOverallSummary();
                    renderFolderCards();
                    processNext();
                }
            }

            xhr.send(formData);
        }
    }
});
