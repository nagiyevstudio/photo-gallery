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
                <div className="hero-copy">
                    <h1 className="hero-title">{title}</h1>
                    <p className="hero-byline">photo.nagiyev.com <span aria-hidden="true">|</span> Photographer Faik Nagiyev</p>
                </div>
                <div className="scroll-indicator" onClick={handleScrollDown}></div>
            </div>
        </section>
    );
}
