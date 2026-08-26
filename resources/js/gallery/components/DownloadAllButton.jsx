import React, { useState } from 'react';
import { client } from '../api/client';

export default function DownloadAllButton({ projectSlug, zipSize }) {
    const [isDownloading, setIsDownloading] = useState(false);
    const [error, setError] = useState(false);

    const handleDownload = async () => {
        if (isDownloading) return;
        setIsDownloading(true);
        setError(false);

        try {
            const res = await client.post(`/projects/${projectSlug}/download-all`);
            if (res && res.token) {
                // Instantly trigger browser download of pre-compiled ZIP
                window.location.href = `/api/downloads/${res.token}/file`;
                setTimeout(() => {
                    setIsDownloading(false);
                }, 3000);
            } else {
                setError(true);
                setIsDownloading(false);
            }
        } catch (err) {
            console.error('Failed to download ZIP:', err);
            setError(true);
            setIsDownloading(false);
        }
    };

    const label = zipSize ? `Download All (${zipSize})` : 'Download All';

    if (error) {
        return (
            <button 
                className="btn-outline" 
                onClick={handleDownload} 
                style={{ borderColor: 'var(--danger)', color: 'var(--danger)' }}
                type="button"
            >
                <span>ZIP Not Ready</span>
            </button>
        );
    }

    return (
        <button 
            className="btn-gold" 
            onClick={handleDownload} 
            disabled={isDownloading}
            type="button"
        >
            <span>{isDownloading ? 'Starting Download...' : label}</span>
        </button>
    );
}
