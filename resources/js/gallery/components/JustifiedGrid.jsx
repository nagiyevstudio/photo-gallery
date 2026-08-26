import React, { useRef, useState, useEffect } from 'react';
import justifiedLayout from 'justified-layout';

export default function JustifiedGrid({ photos, onPhotoClick, allowDownload }) {
    const containerRef = useRef(null);
    const [copiedId, setCopiedId] = useState(null);
    const copyTimer = useRef(null);
    const [containerWidth, setContainerWidth] = useState(() => {
        if (typeof window !== 'undefined') {
            return window.innerWidth > 768 ? window.innerWidth - 16 : window.innerWidth - 12;
        }
        return 1200;
    });
    const [layout, setLayout] = useState({ containerHeight: 0, boxes: [] });

    // Measure inner content width of container (excluding padding)
    useEffect(() => {
        if (!containerRef.current) return;

        const updateWidth = () => {
            if (!containerRef.current) return;
            const width = containerRef.current.clientWidth;
            if (width > 0) {
                setContainerWidth(width);
            }
        };

        updateWidth();

        // Use ResizeObserver for accurate and reactive dimension tracking
        let resizeObserver = null;
        if (typeof ResizeObserver !== 'undefined') {
            resizeObserver = new ResizeObserver((entries) => {
                for (const entry of entries) {
                    const width = entry.contentRect ? entry.contentRect.width : entry.target.clientWidth;
                    if (width > 0) {
                        setContainerWidth(width);
                    }
                }
            });
            resizeObserver.observe(containerRef.current);
        }

        window.addEventListener('resize', updateWidth);

        return () => {
            if (resizeObserver) {
                resizeObserver.disconnect();
            }
            window.removeEventListener('resize', updateWidth);
        };
    }, []);

    // Recalculate justified geometry when photos list or container width changes
    useEffect(() => {
        if (!photos || photos.length === 0 || containerWidth <= 0) return;

        const ratios = photos.map(photo => {
            const ratio = photo.width / photo.height;
            return isNaN(ratio) || ratio <= 0 ? 1.5 : ratio;
        });

        // Determine target height based on device size
        const isMobile = containerWidth < 768;
        const targetHeight = isMobile ? 180 : 300;

        try {
            const geometry = justifiedLayout(ratios, {
                containerWidth: containerWidth,
                targetRowHeight: targetHeight,
                boxSpacing: isMobile ? 6 : 8,
                containerPadding: 0,
            });

            setLayout(geometry);
        } catch (err) {
            console.error('justified-layout calculation error:', err);
        }
    }, [photos, containerWidth]);

    if (photos.length === 0) {
        return (
            <div style={{ textAlign: 'center', padding: '100px 0', color: 'var(--text-secondary)' }}>
                <p>No photos in this section.</p>
            </div>
        );
    }

    return (
        <section className="grid-container" ref={containerRef}>
            <div 
                className="justified-grid" 
                style={{ height: `${layout.containerHeight}px`, position: 'relative' }}
            >
                {photos.map((photo, index) => {
                    const box = layout.boxes[index];
                    if (!box) return null;

                    return (
                        <div
                            key={photo.id}
                            className="photo-card loaded"
                            style={{
                                position: 'absolute',
                                top: `${box.top}px`,
                                left: `${box.left}px`,
                                width: `${box.width}px`,
                                height: `${box.height}px`,
                            }}
                            onClick={() => onPhotoClick(index)}
                        >
                            <img 
                                src={photo.thumbnail_url} 
                                alt="Gallery Thumbnail" 
                                loading="lazy"
                                style={{ pointerEvents: 'none' }}
                            />
                            <div className="photo-card-overlay">
                                <button
                                    className="photo-card-action"
                                    title="Share link"
                                    aria-label="Share link"
                                    onClick={(e) => {
                                        e.stopPropagation();
                                        const deepLink = `${window.location.origin}${window.location.pathname}?photo=${photo.id}`;
                                        navigator.clipboard.writeText(deepLink)
                                            .then(() => {
                                                setCopiedId(photo.id);
                                                clearTimeout(copyTimer.current);
                                                copyTimer.current = setTimeout(() => setCopiedId(null), 1500);
                                            })
                                            .catch(err => console.error('Share link failed:', err));
                                    }}
                                >
                                    {copiedId === photo.id ? (
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                    ) : (
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><circle cx="18" cy="5" r="3"></circle><circle cx="6" cy="12" r="3"></circle><circle cx="18" cy="19" r="3"></circle><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line></svg>
                                    )}
                                </button>
                                {allowDownload && (
                                    <button
                                        className="photo-card-action"
                                        title="Download"
                                        aria-label="Download"
                                        onClick={(e) => {
                                            e.stopPropagation();
                                            if (photo.download_url) {
                                                window.location.href = photo.download_url;
                                            }
                                        }}
                                    >
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                                    </button>
                                )}
                            </div>
                        </div>
                    );
                })}
            </div>
        </section>
    );
}
