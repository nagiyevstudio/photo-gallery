import Alpine from 'alpinejs';
import Sortable from 'sortablejs';

window.Alpine = Alpine;
Alpine.start();

document.addEventListener('DOMContentLoaded', () => {
    // 1. Tab Navigation logic
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');

    // Restore tab from URL if present
    const urlParams = new URLSearchParams(window.location.search);
    const activeTab = urlParams.get('tab') || 'gallery';

    function switchTab(tabId) {
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

    // 4. Folder/File Upload logic
    const dropzone = document.getElementById('upload-dropzone');
    const folderInput = document.getElementById('folder-upload-input');
    const fileInput = document.getElementById('file-upload-input');
    const progressList = document.getElementById('progress-list');

    if (dropzone) {
        const projectId = dropzone.dataset.projectId;
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        dropzone.addEventListener('click', () => {
            // Trigger selection dialog
            if (confirm("Do you want to upload entire folders? Press OK to upload folders, Cancel to upload individual files.")) {
                folderInput.click();
            } else {
                fileInput.click();
            }
        });

        dropzone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropzone.classList.add('dragover');
        });

        dropzone.addEventListener('dragleave', () => {
            dropzone.classList.remove('dragover');
        });

        dropzone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropzone.classList.remove('dragover');
            
            // Note: Dropzone folder tree parsing is complex in pure JS without specific packages,
            // so we prompt the user to use the file picker buttons.
            alert("Please use the file picker (by clicking this zone) to upload folders or files correctly.");
        });

        folderInput.addEventListener('change', () => {
            uploadFiles(folderInput.files);
        });

        fileInput.addEventListener('change', () => {
            uploadFiles(fileInput.files);
        });

        function uploadFiles(files) {
            if (files.length === 0) return;
            
            const MAX_CONCURRENT = 2; // Upload max 2 files at a time
            const MAX_RETRIES = 2;    // Retry failed uploads up to 2 times

            // Make progress container visible
            const progressContainer = document.querySelector('.upload-progress-container');
            progressContainer.style.display = 'block';

            // Build the upload queue
            const queue = [];
            Array.from(files).forEach((file, index) => {
                // Determine gallery name from webkitRelativePath
                let galleryName = 'Unsorted';
                if (file.webkitRelativePath) {
                    const parts = file.webkitRelativePath.split('/');
                    if (parts.length > 1) {
                        galleryName = parts[0]; // Top-level folder
                    }
                }

                // Add progress bar UI
                const progressId = `progress-${Date.now()}-${index}`;
                const fileHtml = `
                    <div class="upload-file-progress" id="${progressId}">
                        <div class="upload-file-info">
                            <span>${file.name} <small style="opacity:0.6">(${galleryName})</small></span>
                            <span class="progress-percent">Queued</span>
                        </div>
                        <div class="progress-bar-bg">
                            <div class="progress-bar-fill" style="width: 0%"></div>
                        </div>
                    </div>
                `;
                progressList.insertAdjacentHTML('beforeend', fileHtml);

                queue.push({ file, galleryName, progressId, retries: 0 });
            });

            // Overall progress counter
            const totalFiles = queue.length;
            let completedFiles = 0;
            let failedFiles = 0;

            // Add overall progress header
            const overallId = `overall-progress-${Date.now()}`;
            progressList.insertAdjacentHTML('afterbegin', `
                <div class="upload-file-progress" id="${overallId}" style="border-bottom: 1px solid var(--border); padding-bottom: 10px; margin-bottom: 10px;">
                    <div class="upload-file-info">
                        <span><strong>Overall Progress</strong></span>
                        <span class="progress-percent" style="font-weight: 600;">0 / ${totalFiles}</span>
                    </div>
                    <div class="progress-bar-bg">
                        <div class="progress-bar-fill" style="width: 0%; transition: width 0.3s ease;"></div>
                    </div>
                </div>
            `);

            const overallEl = document.getElementById(overallId);
            const overallPercent = overallEl.querySelector('.progress-percent');
            const overallBar = overallEl.querySelector('.progress-bar-fill');

            function updateOverall() {
                const done = completedFiles + failedFiles;
                const pct = Math.round((done / totalFiles) * 100);
                overallPercent.textContent = `${completedFiles} / ${totalFiles}` + (failedFiles > 0 ? ` (${failedFiles} failed)` : '');
                overallBar.style.width = `${pct}%`;

                if (done === totalFiles) {
                    if (failedFiles === 0) {
                        overallPercent.textContent = `All ${totalFiles} files uploaded ✓`;
                        overallPercent.style.color = 'var(--success)';
                        overallBar.style.backgroundColor = 'var(--success)';
                    } else {
                        overallPercent.textContent = `Done: ${completedFiles} succeeded, ${failedFiles} failed`;
                        overallPercent.style.color = 'var(--warning, #f0ad4e)';
                        overallBar.style.backgroundColor = 'var(--warning, #f0ad4e)';
                    }
                }
            }

            // Sequential upload engine with concurrency limit
            let currentIndex = 0;
            let activeUploads = 0;

            function uploadNext() {
                while (activeUploads < MAX_CONCURRENT && currentIndex < queue.length) {
                    const item = queue[currentIndex];
                    currentIndex++;
                    activeUploads++;
                    uploadSingleFile(item);
                }
            }

            function uploadSingleFile(item) {
                const progressEl = document.getElementById(item.progressId);
                const percentText = progressEl.querySelector('.progress-percent');
                const barFill = progressEl.querySelector('.progress-bar-fill');

                percentText.textContent = 'Uploading...';
                barFill.style.width = '0%';
                barFill.style.backgroundColor = ''; // Reset color for retries

                const formData = new FormData();
                formData.append('file', item.file);
                formData.append('gallery_name', item.galleryName);

                const xhr = new XMLHttpRequest();
                xhr.open('POST', `/admin/projects/${projectId}/upload`, true);
                xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);

                xhr.upload.onprogress = (event) => {
                    if (event.lengthComputable) {
                        const percent = Math.round((event.loaded / event.total) * 100);
                        percentText.textContent = `${percent}%`;
                        barFill.style.width = `${percent}%`;
                    }
                };

                xhr.onload = () => {
                    activeUploads--;
                    if (xhr.status === 200) {
                        completedFiles++;
                        percentText.textContent = '✓ Done';
                        percentText.style.color = 'var(--success)';
                        barFill.style.width = '100%';
                        barFill.style.backgroundColor = 'var(--success)';
                    } else {
                        // Retry logic
                        if (item.retries < MAX_RETRIES) {
                            item.retries++;
                            percentText.textContent = `Retry ${item.retries}/${MAX_RETRIES}...`;
                            percentText.style.color = 'var(--warning, #f0ad4e)';
                            barFill.style.width = '0%';
                            activeUploads++;
                            setTimeout(() => uploadSingleFile(item), 1000); // Wait 1s before retry
                            return;
                        }
                        failedFiles++;
                        percentText.textContent = `✗ Failed (${xhr.status})`;
                        percentText.style.color = 'var(--danger)';
                        barFill.style.backgroundColor = 'var(--danger)';
                    }
                    updateOverall();
                    uploadNext(); // Start next file in queue
                };

                xhr.onerror = () => {
                    activeUploads--;
                    // Retry logic
                    if (item.retries < MAX_RETRIES) {
                        item.retries++;
                        percentText.textContent = `Retry ${item.retries}/${MAX_RETRIES}...`;
                        percentText.style.color = 'var(--warning, #f0ad4e)';
                        barFill.style.width = '0%';
                        activeUploads++;
                        setTimeout(() => uploadSingleFile(item), 1000);
                        return;
                    }
                    failedFiles++;
                    percentText.textContent = '✗ Network error';
                    percentText.style.color = 'var(--danger)';
                    barFill.style.backgroundColor = 'var(--danger)';
                    updateOverall();
                    uploadNext();
                };

                xhr.send(formData);
            }

            // Kick off the queue
            uploadNext();
        }
    }
});
