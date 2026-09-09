import React, { useState } from 'react';
import { Share2, Check } from 'lucide-react';

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
            {copied ? (
                <>
                    <Check size={14} />
                    <span>Link Copied!</span>
                </>
            ) : (
                <>
                    <Share2 size={14} />
                    <span>{label}</span>
                </>
            )}
        </button>
    );
}
