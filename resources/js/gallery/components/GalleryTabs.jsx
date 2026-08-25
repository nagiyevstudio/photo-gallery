import React from 'react';

export default function GalleryTabs({ 
    galleries, 
    activeSlug, 
    onTabChange
}) {
    if (!galleries || galleries.length === 0) return null;

    return (
        <nav className="tabs-bar" id="tabs-navigation-bar" aria-label="Gallery collections">
            <div className="tabs-list">
                {galleries.map((gallery) => (
                    <button 
                        key={gallery.slug}
                        className={`tab-item ${activeSlug === gallery.slug ? 'active' : ''}`}
                        onClick={() => onTabChange(gallery.slug)}
                        style={{ background: 'none', border: 'none' }}
                        type="button"
                    >
                        {gallery.title}
                    </button>
                ))}
            </div>
        </nav>
    );
}
