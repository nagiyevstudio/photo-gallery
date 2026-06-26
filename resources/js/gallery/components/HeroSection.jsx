import React from 'react';

export default function HeroSection({ imageUrl, title }) {
    const handleScrollDown = () => {
        const tabsBar = document.getElementById('tabs-navigation-bar');
        if (tabsBar) {
            tabsBar.scrollIntoView({ behavior: 'smooth' });
        }
    };

    return (
        <section className="hero-container">
            <div 
                className="hero-bg" 
                style={{ backgroundImage: `url(${imageUrl})` }}
            ></div>
            <div className="hero-overlay">
                <h1 className="hero-title">{title}</h1>
                <div className="scroll-indicator" onClick={handleScrollDown}></div>
            </div>
        </section>
    );
}
