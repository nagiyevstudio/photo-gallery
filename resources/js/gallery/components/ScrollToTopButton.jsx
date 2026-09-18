import React, { useState, useEffect } from 'react';
import { ArrowUp } from 'lucide-react';

export default function ScrollToTopButton() {
    const [isVisible, setIsVisible] = useState(false);

    useEffect(() => {
        const toggleVisibility = () => {
            // Show button after scrolling past 400px
            if (window.scrollY > 400) {
                setIsVisible(true);
            } else {
                setIsVisible(false);
            }
        };

        window.addEventListener('scroll', toggleVisibility, { passive: true });
        toggleVisibility();

        return () => window.removeEventListener('scroll', toggleVisibility);
    }, []);

    const scrollToTop = () => {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    };

    return (
        <button
            type="button"
            className={`scroll-to-top-btn ${isVisible ? 'visible' : ''}`}
            onClick={scrollToTop}
            aria-label="Scroll back to top"
            tabIndex={isVisible ? 0 : -1}
        >
            <ArrowUp size={20} strokeWidth={2.2} />
        </button>
    );
}
