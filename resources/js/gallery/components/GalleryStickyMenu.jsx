import React from 'react';
import ExpirationBadge from './ExpirationBadge';
import DownloadAllButton from './DownloadAllButton';

export default function GalleryStickyMenu({ expiresAt, allowDownload, projectSlug }) {
    if (!expiresAt && !allowDownload) return null;

    return (
        <aside className="gallery-sticky-menu" id="gallery-sticky-menu" aria-label="Gallery actions">
            <ExpirationBadge date={expiresAt} />
            {allowDownload && <DownloadAllButton projectSlug={projectSlug} />}
        </aside>
    );
}
