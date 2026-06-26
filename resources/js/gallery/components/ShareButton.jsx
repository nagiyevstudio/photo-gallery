import React, { useState } from 'react';

export default function ShareButton({ url, label = 'Share' }) {
    const [copied, setCopied] = useState(false);

    const handleShare = (e) => {
        e.stopPropagation();
        
        navigator.clipboard.writeText(url || window.location.href)
            .then(() => {
                setCopied(true);
                setTimeout(() => setCopied(false), 2000);
            })
            .catch((err) => console.error('Failed to copy link: ', err));
    };

    return (
        <button className="btn-outline" onClick={handleShare}>
            {copied ? 'Link Copied!' : label}
        </button>
    );
}
