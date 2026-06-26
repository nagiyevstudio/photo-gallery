import React from 'react';
import ExpirationBadge from './ExpirationBadge';
import DownloadAllButton from './DownloadAllButton';

export default function GalleryTabs({ 
    galleries, 
    activeSlug, 
    onTabChange, 
    expiresAt, 
    allowDownload, 
    projectSlug 
}) {
    return (
        <nav className="tabs-bar" id="tabs-navigation-bar">
            <div className="tabs-list">
                {galleries.map((gallery) => (
                    <button 
                        key={gallery.slug}
                        className={`tab-item ${activeSlug === gallery.slug ? 'active' : ''}`}
                        onClick={() => onTabChange(gallery.slug)}
                        style={{ background: 'none', border: 'none' }}
                    >
                        {gallery.title}
                    </button>
                ))}
            </div>

            <div className="tabs-actions">
                <ExpirationBadge date={expiresAt} />
                
                {allowDownload && (
                    <DownloadAllButton projectSlug={projectSlug} />
                )}
            </div>
        </nav>
    );
}
