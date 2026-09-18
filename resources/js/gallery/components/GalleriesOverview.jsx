import React from 'react';
import { ArrowRight, Images } from 'lucide-react';

export default function GalleriesOverview({ galleries, onSelectGallery }) {
    if (!galleries || galleries.length === 0) return null;

    return (
        <section className="galleries-overview" aria-label="Collections overview">
            <div className="overview-container">
                <div className="overview-header">
                    <h2 className="overview-title">Collections</h2>
                    <p className="overview-subtitle">
                        Select a collection to view all photographs
                    </p>
                </div>

                <div className="overview-grid">
                    {galleries.map((gallery) => {
                        const previews = gallery.preview_photos || [];
                        const count = gallery.photo_count || 0;
                        const hasPhotos = previews.length > 0;

                        // Preview photos mapping:
                        // Front card: previews[0]
                        // Mid card: previews[1] (if 3 photos)
                        // Back card: previews[2] (if 3 photos) or previews[1] (if 2 photos)
                        const frontPhoto = previews[0];
                        const midPhoto = previews.length >= 3 ? previews[1] : null;
                        const backPhoto = previews.length >= 3 ? previews[2] : (previews.length === 2 ? previews[1] : null);

                        return (
                            <button
                                key={gallery.slug}
                                type="button"
                                className="gallery-stack-card"
                                onClick={() => onSelectGallery(gallery.slug)}
                                aria-label={`Open ${gallery.title} collection, ${count} photos`}
                            >
                                <div className="photo-stack-wrapper">
                                    <div className={`photo-stack ${hasPhotos ? '' : 'is-empty'}`}>
                                        {/* Back Photo Card */}
                                        {backPhoto && (
                                            <div className="stack-card stack-card-back">
                                                <img 
                                                    src={backPhoto.thumbnail_url} 
                                                    alt="" 
                                                    loading="lazy" 
                                                    className="stack-img" 
                                                />
                                            </div>
                                        )}

                                        {/* Mid Photo Card */}
                                        {midPhoto && (
                                            <div className="stack-card stack-card-mid">
                                                <img 
                                                    src={midPhoto.thumbnail_url} 
                                                    alt="" 
                                                    loading="lazy" 
                                                    className="stack-img" 
                                                />
                                            </div>
                                        )}

                                        {/* Front Photo Card */}
                                        {frontPhoto ? (
                                            <div className="stack-card stack-card-front">
                                                <img 
                                                    src={frontPhoto.thumbnail_url} 
                                                    alt="" 
                                                    loading="lazy" 
                                                    className="stack-img" 
                                                />
                                                <div className="stack-count-badge">
                                                    {count} {count === 1 ? 'photo' : 'photos'}
                                                </div>
                                            </div>
                                        ) : (
                                            <div className="stack-card stack-card-front stack-card-placeholder">
                                                <Images size={36} strokeWidth={1.4} className="placeholder-icon" />
                                                <span className="placeholder-text">Empty Collection</span>
                                            </div>
                                        )}
                                    </div>
                                </div>

                                <div className="gallery-card-content">
                                    <div className="gallery-card-info">
                                        <h3 className="gallery-card-title">{gallery.title}</h3>
                                        <span className="gallery-card-count">
                                            {count} {count === 1 ? 'photo' : 'photos'}
                                        </span>
                                    </div>
                                    <div className="gallery-card-action" aria-hidden="true">
                                        <span className="action-text">Explore</span>
                                        <ArrowRight size={16} className="action-arrow" />
                                    </div>
                                </div>
                            </button>
                        );
                    })}
                </div>
            </div>
        </section>
    );
}
