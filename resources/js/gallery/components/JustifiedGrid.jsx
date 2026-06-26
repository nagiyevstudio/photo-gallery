import React, { useRef, useState, useEffect } from 'react';
import justifiedLayout from 'justified-layout';

export default function JustifiedGrid({ photos, onPhotoClick }) {
    const containerRef = useRef(null);
    const [containerWidth, setContainerWidth] = useState(1200);
    const [layout, setLayout] = useState({ containerHeight: 0, boxes: [] });

    // Update container width on resize
    useEffect(() => {
        if (!containerRef.current) return;

        const updateWidth = () => {
            const width = containerRef.current.offsetWidth;
            setContainerWidth(width > 0 ? width : 1200);
        };

        updateWidth();
        window.addEventListener('resize', updateWidth);

        // Keep checking width for a brief period to handle tab transition latency
        const intervalId = setInterval(updateWidth, 100);
        setTimeout(() => clearInterval(intervalId), 1000);

        return () => {
            window.removeEventListener('resize', updateWidth);
            clearInterval(intervalId);
        };
    }, [photos]);

    // Recalculate justified geometry when photos list or container width changes
    useEffect(() => {
        if (photos.length === 0) return;

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
                boxSpacing: isMobile ? 3 : 4,
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
                        </div>
                    );
                })}
            </div>
        </section>
    );
}
