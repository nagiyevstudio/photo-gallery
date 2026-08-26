import React, { useEffect, useRef } from 'react';
import PhotoSwipe from 'photoswipe';
import 'photoswipe/style.css';

export default function Lightbox({ 
    photos, 
    activeIndex, 
    isOpen, 
    onClose, 
    allowDownload 
}) {
    const pswpRef = useRef(null);

    useEffect(() => {
        if (!isOpen || photos.length === 0) return;

        const dataSource = photos.map(photo => ({
            id: photo.id,
            src: photo.web_url,
            w: photo.width,
            h: photo.height,
            msrc: photo.thumbnail_url,
            downloadUrl: photo.download_url,
        }));

        const pswp = new PhotoSwipe({
            dataSource: dataSource,
            index: activeIndex,
            bgOpacity: 1, // fully opaque backdrop — gallery must not bleed through
            padding: { top: 24, bottom: 24, left: 0, right: 0 }, // breathing room from screen edges
            showHideAnimationType: 'zoom',
            // Feather-style stroke icons so the whole toolbar comes from one icon family
            zoomSVG: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line><line class="pswp__zoom-icn-bar-h" x1="11" y1="8" x2="11" y2="14"></line><line class="pswp__zoom-icn-bar-v" x1="8" y1="11" x2="14" y2="11"></line></svg>',
            closeSVG: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>',
        });

        // 1. Add download button to toolbar if downloads are allowed
        if (allowDownload) {
            pswp.on('uiRegister', () => {
                pswp.ui.registerElement({
                    name: 'download-single',
                    ariaLabel: 'Download',
                    order: 9,
                    isButton: true,
                    html: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-download"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>',
                    onClick: (event, el, pswpInstance) => {
                        const slideData = pswpInstance.currSlide.data;
                        if (slideData.downloadUrl) {
                            window.location.href = slideData.downloadUrl;
                        }
                    }
                });
            });
        }

        // 2. Add share link button to toolbar
        pswp.on('uiRegister', () => {
            pswp.ui.registerElement({
                name: 'share-photo',
                ariaLabel: 'Share link',
                order: 8,
                isButton: true,
                html: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-share-2"><circle cx="18" cy="5" r="3"></circle><circle cx="6" cy="12" r="3"></circle><circle cx="18" cy="19" r="3"></circle><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line></svg>',
                onClick: (event, el, pswpInstance) => {
                    const slideData = pswpInstance.currSlide.data;
                    
                    // Generate deep link
                    const deepLink = `${window.location.origin}${window.location.pathname}?photo=${slideData.id}`;
                    
                    navigator.clipboard.writeText(deepLink)
                        .then(() => {
                            // Temporary visual feedback
                            el.innerHTML = '<span style="font-size:10px; color:var(--accent);">COPIED</span>';
                            setTimeout(() => {
                                el.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-share-2"><circle cx="18" cy="5" r="3"></circle><circle cx="6" cy="12" r="3"></circle><circle cx="18" cy="19" r="3"></circle><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line></svg>';
                            }, 2000);
                        })
                        .catch(err => console.error('Share link failed:', err));
                }
            });
        });

        // 3. Update URL parameter (?photo=id) on slide transition
        pswp.on('change', () => {
            const slideData = pswp.currSlide.data;
            const url = new URL(window.location.href);
            url.searchParams.set('photo', slideData.id);
            window.history.replaceState({}, '', url);
        });

        // 4. Remove URL parameter on close
        pswp.on('close', () => {
            const url = new URL(window.location.href);
            url.searchParams.delete('photo');
            window.history.replaceState({}, '', url);
            onClose();
        });

        pswp.init();
        pswpRef.current = pswp;

        return () => {
            if (pswpRef.current) {
                pswpRef.current.close();
            }
        };
    }, [isOpen, photos, activeIndex]);

    return null; // PhotoSwipe injects elements dynamically into the body
}
