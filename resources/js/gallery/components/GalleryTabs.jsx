import React, { useState, useEffect, useLayoutEffect, useRef, useCallback } from 'react';
import { ChevronDown, ChevronRight, ChevronLeft } from 'lucide-react';

export default function GalleryTabs({ 
    galleries, 
    activeSlug, 
    onTabChange
}) {
    if (!galleries || galleries.length === 0) return null;

    const navRef = useRef(null);
    const tabsListRef = useRef(null);
    const measureRef = useRef(null);
    const dropdownRef = useRef(null);

    const [isMobile, setIsMobile] = useState(() => {
        if (typeof window === 'undefined') return false;
        return window.innerWidth <= 768;
    });

    const [visibleCount, setVisibleCount] = useState(galleries.length);
    const [dropdownOpen, setDropdownOpen] = useState(false);
    const [canScrollLeft, setCanScrollLeft] = useState(false);
    const [canScrollRight, setCanScrollRight] = useState(false);

    // Track mobile breakpoint
    useEffect(() => {
        const checkMobile = () => {
            const mobile = window.innerWidth <= 768;
            setIsMobile(mobile);
            if (mobile) {
                setDropdownOpen(false);
            }
        };

        window.addEventListener('resize', checkMobile);
        return () => window.removeEventListener('resize', checkMobile);
    }, []);

    // Desktop: calculate how many tabs fit in the bar
    const updateVisibleTabs = useCallback(() => {
        if (window.innerWidth <= 768) {
            setVisibleCount(galleries.length);
            return;
        }

        const bar = navRef.current;
        const ruler = measureRef.current;
        if (!bar || !ruler || !galleries || galleries.length === 0) return;

        // Account for nav-bar padding (32px on each side = 64px) + safety buffer
        const availableWidth = bar.clientWidth - 88;
        const children = ruler.children;
        if (!children || children.length < galleries.length + 1) return;

        const gap = 32;
        let totalTabsWidth = 0;
        const widths = [];

        for (let i = 0; i < galleries.length; i++) {
            const w = children[i].getBoundingClientRect().width;
            widths.push(w);
            totalTabsWidth += w + (i > 0 ? gap : 0);
        }

        const moreBtnWidth = children[galleries.length].getBoundingClientRect().width;

        // If everything fits without More button
        if (totalTabsWidth <= availableWidth) {
            setVisibleCount(galleries.length);
            return;
        }

        // Space available for visible tabs when More button is present
        const maxTabSpace = availableWidth - moreBtnWidth - gap;
        let accumulated = 0;
        let count = 0;

        for (let i = 0; i < galleries.length; i++) {
            const needed = (count > 0 ? gap : 0) + widths[i];
            if (accumulated + needed <= maxTabSpace) {
                accumulated += needed;
                count++;
            } else {
                break;
            }
        }

        setVisibleCount(Math.max(1, count));
    }, [galleries]);

    useLayoutEffect(() => {
        updateVisibleTabs();

        if (!navRef.current || typeof ResizeObserver === 'undefined') return;

        const observer = new ResizeObserver(() => {
            updateVisibleTabs();
        });

        observer.observe(navRef.current);
        return () => observer.disconnect();
    }, [updateVisibleTabs]);

    // Recalculate once fonts load
    useEffect(() => {
        if (document.fonts && document.fonts.ready) {
            document.fonts.ready.then(() => {
                updateVisibleTabs();
            });
        }
    }, [updateVisibleTabs]);

    // Close dropdown on outside click or Escape key
    useEffect(() => {
        if (!dropdownOpen) return;

        const handleClickOutside = (event) => {
            if (dropdownRef.current && !dropdownRef.current.contains(event.target)) {
                setDropdownOpen(false);
            }
        };

        const handleKeyDown = (event) => {
            if (event.key === 'Escape') {
                setDropdownOpen(false);
            }
        };

        document.addEventListener('pointerdown', handleClickOutside);
        document.addEventListener('keydown', handleKeyDown);

        return () => {
            document.removeEventListener('pointerdown', handleClickOutside);
            document.removeEventListener('keydown', handleKeyDown);
        };
    }, [dropdownOpen]);

    // Mobile: check scroll position to toggle chevron indicators
    const checkMobileScroll = useCallback(() => {
        const el = tabsListRef.current;
        if (!el) return;
        const { scrollLeft, scrollWidth, clientWidth } = el;
        setCanScrollLeft(scrollLeft > 6);
        setCanScrollRight(scrollWidth - clientWidth - scrollLeft > 6);
    }, []);

    useEffect(() => {
        if (isMobile) {
            checkMobileScroll();
            const timer = setTimeout(checkMobileScroll, 100);
            return () => clearTimeout(timer);
        }
    }, [isMobile, galleries, checkMobileScroll]);

    // Mobile: scroll active tab into view
    useEffect(() => {
        if (!isMobile || !tabsListRef.current) return;
        const activeEl = tabsListRef.current.querySelector('.tab-item.active');
        if (activeEl) {
            activeEl.scrollIntoView({
                behavior: 'smooth',
                inline: 'center',
                block: 'nearest'
            });
        }
    }, [activeSlug, isMobile]);

    const handleScroll = (direction) => {
        if (!tabsListRef.current) return;
        const scrollAmount = 180;
        tabsListRef.current.scrollBy({
            left: direction === 'right' ? scrollAmount : -scrollAmount,
            behavior: 'smooth'
        });
    };

    // Determine visible vs overflow tabs for desktop
    const hasOverflow = !isMobile && visibleCount < galleries.length;
    const visibleGalleries = hasOverflow ? galleries.slice(0, visibleCount) : galleries;
    const overflowGalleries = hasOverflow ? galleries.slice(visibleCount) : [];
    const isMoreActive = overflowGalleries.some(g => g.slug === activeSlug);

    return (
        <nav 
            className="tabs-bar" 
            id="tabs-navigation-bar" 
            ref={navRef}
            aria-label="Gallery collections"
        >
            {/* Mobile Left Chevron Indicator with transparent gradient */}
            <div 
                className={`tabs-scroll-indicator left ${canScrollLeft ? 'visible' : ''}`}
                aria-hidden={!canScrollLeft}
            >
                <button
                    type="button"
                    className="tabs-scroll-btn"
                    onClick={() => handleScroll('left')}
                    aria-label="Scroll left"
                    tabIndex={canScrollLeft ? 0 : -1}
                >
                    <ChevronLeft size={16} strokeWidth={2.4} />
                </button>
            </div>

            {/* Main Tabs List */}
            <div 
                className="tabs-list" 
                ref={tabsListRef}
                onScroll={isMobile ? checkMobileScroll : undefined}
            >
                {visibleGalleries.map((gallery) => (
                    <button 
                        key={gallery.slug}
                        className={`tab-item ${activeSlug === gallery.slug ? 'active' : ''}`}
                        onClick={() => onTabChange(gallery.slug)}
                        style={{ background: 'none', border: 'none' }}
                        type="button"
                    >
                        {gallery.title}
                    </button>
                ))}

                {/* Desktop "More" dropdown trigger & menu */}
                {hasOverflow && (
                    <div className="tabs-dropdown-wrapper" ref={dropdownRef}>
                        <button
                            type="button"
                            className={`tab-item tab-dropdown-trigger ${isMoreActive ? 'active' : ''} ${dropdownOpen ? 'open' : ''}`}
                            onClick={() => setDropdownOpen(prev => !prev)}
                            aria-haspopup="true"
                            aria-expanded={dropdownOpen}
                            style={{ background: 'none', border: 'none' }}
                        >
                            <span>More</span>
                            <ChevronDown 
                                size={14} 
                                className={`tab-chevron-icon ${dropdownOpen ? 'rotated' : ''}`} 
                            />
                        </button>

                        {dropdownOpen && (
                            <div className="tab-dropdown-menu" role="menu">
                                {overflowGalleries.map((gallery) => {
                                    const isActive = activeSlug === gallery.slug;
                                    return (
                                        <button
                                            key={gallery.slug}
                                            role="menuitem"
                                            type="button"
                                            className={`tab-dropdown-item ${isActive ? 'active' : ''}`}
                                            onClick={() => {
                                                onTabChange(gallery.slug);
                                                setDropdownOpen(false);
                                            }}
                                        >
                                            <span className="tab-dropdown-item-title">{gallery.title}</span>
                                            {isActive && <span className="tab-dropdown-item-dot" />}
                                        </button>
                                    );
                                })}
                            </div>
                        )}
                    </div>
                )}
            </div>

            {/* Mobile Right Chevron Indicator with transparent gradient */}
            <div 
                className={`tabs-scroll-indicator right ${canScrollRight ? 'visible' : ''}`}
                aria-hidden={!canScrollRight}
            >
                <button
                    type="button"
                    className="tabs-scroll-btn"
                    onClick={() => handleScroll('right')}
                    aria-label="Scroll right"
                    tabIndex={canScrollRight ? 0 : -1}
                >
                    <ChevronRight size={16} strokeWidth={2.4} />
                </button>
            </div>

            {/* Hidden measurement ruler for accurate tab widths calculation */}
            <div
                ref={measureRef}
                className="tabs-measure-ruler"
                aria-hidden="true"
            >
                {galleries.map((gallery) => (
                    <span 
                        key={gallery.slug}
                        className="tab-item"
                    >
                        {gallery.title}
                    </span>
                ))}
                <span className="tab-item tab-dropdown-trigger">
                    <span>More</span>
                    <ChevronDown size={14} />
                </span>
            </div>
        </nav>
    );
}
