import React from 'react';
import ExpirationBadge from './ExpirationBadge';
import DownloadAllButton from './DownloadAllButton';

export default function HeroSection({ 
    imageUrl, 
    title, 
    expiresAt, 
    allowDownload, 
    projectSlug 
}) {
    const handleScrollDown = () => {
        const tabsBar = document.getElementById('tabs-navigation-bar');
        if (tabsBar) {
            tabsBar.scrollIntoView({ behavior: 'smooth' });
        }
    };

    return (
        <section className="hero-container">
            <div 
                className="hero-bg" 
                style={{ backgroundImage: `url(${imageUrl})` }}
            ></div>
            <div className="hero-overlay">
                <div className="hero-copy">
                    <h1 className="hero-title">{title}</h1>

                    {(allowDownload || expiresAt) && (
                        <div className="hero-actions">
                            {allowDownload && (
                                <DownloadAllButton projectSlug={projectSlug} />
                            )}
                            <ExpirationBadge date={expiresAt} />
                        </div>
                    )}

                    <p className="hero-byline">photo.nagiyev.com <span aria-hidden="true">|</span> Photographer Faik Nagiyev</p>
                </div>
                <div className="scroll-indicator" onClick={handleScrollDown}></div>
            </div>
        </section>
    );
}
