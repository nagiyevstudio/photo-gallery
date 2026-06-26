import React from 'react';

export default function ExpirationBadge({ date }) {
    if (!date) return null;
    
    return (
        <div className="expiration-badge">
            Available until: {date}
        </div>
    );
}
