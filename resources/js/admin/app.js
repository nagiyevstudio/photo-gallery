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
            
            // Make progress container visible
            document.querySelector('.upload-progress-container').style.display = 'block';

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
                            <span>${file.name} (${galleryName})</span>
                            <span class="progress-percent">0%</span>
                        </div>
                        <div class="progress-bar-bg">
                            <div class="progress-bar-fill" style="width: 0%"></div>
                        </div>
                    </div>
                `;
                progressList.insertAdjacentHTML('beforeend', fileHtml);

                const progressEl = document.getElementById(progressId);
                const percentText = progressEl.querySelector('.progress-percent');
                const barFill = progressEl.querySelector('.progress-bar-fill');

                // Perform AJAX upload
                const formData = new FormData();
                formData.append('file', file);
                formData.append('gallery_name', galleryName);

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
                    if (xhr.status === 200) {
                        percentText.textContent = 'Completed';
                        percentText.style.color = 'var(--success)';
                        barFill.style.backgroundColor = 'var(--success)';
                    } else {
                        percentText.textContent = 'Failed';
                        percentText.style.color = 'var(--danger)';
                        barFill.style.backgroundColor = 'var(--danger)';
                    }
                };

                xhr.onerror = () => {
                    percentText.textContent = 'Error';
                    percentText.style.color = 'var(--danger)';
                    barFill.style.backgroundColor = 'var(--danger)';
                };

                xhr.send(formData);
            });
        }
    }
});
