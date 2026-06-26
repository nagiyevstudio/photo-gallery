import React, { useState, useEffect } from 'react';
import { client } from '../api/client';

export default function DownloadAllButton({ projectSlug }) {
    const [state, setState] = useState('idle'); // idle, generating, ready, error
    const [token, setToken] = useState(null);
    const [zipSize, setZipSize] = useState('');

    useEffect(() => {
        let intervalId = null;

        if (state === 'generating' && token) {
            intervalId = setInterval(async () => {
                try {
                    const res = await client.get(`/downloads/${token}/status`);
                    if (res.status === 'ready') {
                        setZipSize(formatBytes(res.size));
                        setState('ready');
                        clearInterval(intervalId);
                    } else if (res.status === 'error') {
                        setState('error');
                        clearInterval(intervalId);
                    }
                } catch (err) {
                    console.error('ZIP status check failed:', err);
                    setState('error');
                    clearInterval(intervalId);
                }
            }, 3000);
        }

        return () => {
            if (intervalId) clearInterval(intervalId);
        };
    }, [state, token]);

    const handleStartDownload = async () => {
        setState('generating');
        try {
            const res = await client.post(`/projects/${projectSlug}/download-all`);
            setToken(res.token);
        } catch (err) {
            console.error('Failed to request ZIP:', err);
            setState('error');
        }
    };

    const handleExecuteDownload = () => {
        if (!token) return;
        window.location.href = `/api/downloads/${token}/file`;
        // Optionally return to idle after some delay
        setTimeout(() => {
            setState('idle');
            setToken(null);
            setZipSize('');
        }, 5000);
    };

    function formatBytes(bytes, decimals = 1) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const dm = decimals < 0 ? 0 : decimals;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
    }

    if (state === 'idle') {
        return (
            <button className="btn-gold" onClick={handleStartDownload}>
                <span>Download All (ZIP)</span>
            </button>
        );
    }

    if (state === 'generating') {
        return (
            <div className="zip-progress-wrapper" style={{ padding: '0 8px' }}>
                <span style={{ fontSize: '11px', color: 'var(--text-secondary)', textTransform: 'uppercase', letterSpacing: '0.5px' }}>
                    Compiling Archive...
                </span>
                <div className="zip-bar-bg">
                    <div className="zip-bar-fill"></div>
                </div>
            </div>
        );
    }

    if (state === 'ready') {
        return (
            <button className="btn-gold" onClick={handleExecuteDownload}>
                <span>Save ZIP ({zipSize})</span>
            </button>
        );
    }

    return (
        <button className="btn-outline" onClick={() => setState('idle')} style={{ borderColor: 'var(--danger)', color: 'var(--danger)' }}>
            <span>Error. Try Again</span>
        </button>
    );
}
