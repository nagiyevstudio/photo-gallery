import React from 'react';
import { Calendar } from 'lucide-react';

function formatShortDate(dateInput) {
    if (!dateInput) return '';

    // If it's already in DD.MM.YYYY format
    if (/^\d{2}\.\d{2}\.\d{4}$/.test(dateInput)) {
        return dateInput;
    }

    const d = new Date(dateInput);
    if (isNaN(d.getTime())) {
        return dateInput;
    }

    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const year = d.getFullYear();
    return `${day}.${month}.${year}`;
}

export default function ExpirationBadge({ date }) {
    if (!date) return null;

    const formattedDate = formatShortDate(date);
    if (!formattedDate) return null;

    return (
        <div className="expiration-badge" title={`Expire: ${formattedDate}`}>
            <Calendar size={13} style={{ opacity: 0.7 }} />
            <span className="expiration-label">Expire:</span>
            <span className="expiration-date">{formattedDate}</span>
        </div>
    );
}
