import { useEffect } from 'react';

export function useAntiTheft(enabled) {
    useEffect(() => {
        if (!enabled) return;

        const handleContextMenu = (e) => {
            if (e.target.tagName === 'IMG' || e.target.closest('.pswp__img')) {
                e.preventDefault();
            }
        };

        const handleDragStart = (e) => {
            if (e.target.tagName === 'IMG' || e.target.closest('.pswp__img')) {
                e.preventDefault();
            }
        };

        // Attach event listeners
        document.addEventListener('contextmenu', handleContextMenu);
        document.addEventListener('dragstart', handleDragStart);

        // Add CSS rule dynamically to disable touch callout on mobile
        const style = document.createElement('style');
        style.id = 'anti-theft-styles';
        style.innerHTML = `
            img, .pswp__img {
                -webkit-touch-callout: none !important;
                -webkit-user-select: none !important;
                -khtml-user-select: none !important;
                -moz-user-select: none !important;
                -ms-user-select: none !important;
                user-select: none !important;
            }
        `;
        document.head.appendChild(style);

        return () => {
            document.removeEventListener('contextmenu', handleContextMenu);
            document.removeEventListener('dragstart', handleDragStart);
            const styleElement = document.getElementById('anti-theft-styles');
            if (styleElement) {
                styleElement.remove();
            }
        };
    }, [enabled]);
}
