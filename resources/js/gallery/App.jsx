import React, { useState, useEffect } from 'react';
import { client } from './api/client';
import HeroSection from './components/HeroSection';
import GalleryTabs from './components/GalleryTabs';
import JustifiedGrid from './components/JustifiedGrid';
import Lightbox from './components/Lightbox';
import PasswordGate from './components/PasswordGate';
import Spinner from './components/Spinner';
import Footer from './components/Footer';
import { useAntiTheft } from './hooks/useAntiTheft';

export default function App() {
    const [projectSlug, setProjectSlug] = useState('');
    const [project, setProject] = useState(null);
    const [photos, setPhotos] = useState([]);
    
    const [activeGallerySlug, setActiveGallerySlug] = useState('');
    const [isPasswordRequired, setIsPasswordRequired] = useState(false);
    const [isLoadingProject, setIsLoadingProject] = useState(true);
    const [isLoadingPhotos, setIsLoadingPhotos] = useState(false);
    const [error, setError] = useState(null);

    // Lightbox states
    const [lightboxOpen, setLightboxOpen] = useState(false);
    const [lightboxIndex, setLightboxIndex] = useState(0);

    // Get project slug from pathname (e.g. /wedding-photoshoot -> wedding-photoshoot)
    useEffect(() => {
        const path = window.location.pathname.replace(/^\/|\/$/g, '');
        setProjectSlug(path);
    }, []);

    // Fetch project details
    const fetchProject = async () => {
        if (!projectSlug) return;
        
        setIsLoadingProject(true);
        setError(null);

        try {
            const data = await client.get(`/projects/${projectSlug}`);
            setProject(data.project);
            
            // Set first gallery active by default
            if (data.project.galleries?.length > 0) {
                setActiveGallerySlug(data.project.galleries[0].slug);
            }
            setIsPasswordRequired(false);
        } catch (err) {
            if (err.status === 401 && err.data?.requires_password) {
                setIsPasswordRequired(true);
            } else if (err.status === 410) {
                setError({ type: 'expired', message: err.message });
            } else {
                setError({ type: 'notFound', message: 'Gallery project not found.' });
            }
        } finally {
            setIsLoadingProject(false);
        }
    };

    useEffect(() => {
        if (projectSlug) {
            fetchProject();
        }
    }, [projectSlug]);

    // Fetch photos when active gallery changes
    useEffect(() => {
        if (!projectSlug || !activeGallerySlug) return;

        const fetchPhotos = async () => {
            setIsLoadingPhotos(true);
            try {
                const data = await client.get(`/projects/${projectSlug}/galleries/${activeGallerySlug}`);
                setPhotos(data.gallery.photos);
            } catch (err) {
                console.error('Failed to fetch photos:', err);
            } finally {
                setIsLoadingPhotos(false);
            }
        };

        fetchPhotos();
    }, [projectSlug, activeGallerySlug]);

    // Deep linking support: check ?photo=id and open lightbox
    useEffect(() => {
        if (photos.length === 0) return;

        const queryParams = new URLSearchParams(window.location.search);
        const photoId = queryParams.get('photo');
        
        if (photoId) {
            const index = photos.findIndex(p => p.id.toString() === photoId);
            if (index !== -1) {
                setLightboxIndex(index);
                setLightboxOpen(true);
            }
        }
    }, [photos]);

    // Enable/Disable right-click and dragging if downloads are disabled
    const antiTheftEnabled = project ? !project.allow_download : false;
    useAntiTheft(antiThertEnabledCheck());

    function antiThertEnabledCheck() {
        return project ? !project.allow_download : false;
    }

    if (isLoadingProject) {
        return <Spinner />;
    }

    if (isPasswordRequired) {
        return (
            <PasswordGate 
                projectSlug={projectSlug} 
                onSuccess={fetchProject} 
            />
        );
    }

    if (error) {
        return (
            <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', height: '100vh', padding: '24px', textAlign: 'center' }}>
                <span style={{ fontSize: '48px', marginBottom: '16px' }}>
                    {error.type === 'expired' ? '⏳' : '🔍'}
                </span>
                <h2 style={{ fontFamily: 'var(--font-display)', fontSize: '28px', marginBottom: '8px' }}>
                    {error.type === 'expired' ? 'Gallery Expired' : 'Project Not Found'}
                </h2>
                <p style={{ color: 'var(--text-secondary)', maxWidth: '460px' }}>
                    {error.type === 'expired' ? 'This photo collection is no longer available. Please contact the photographer.' : 'The requested link is invalid or has been deleted.'}
                </p>
            </div>
        );
    }

    if (!project) return null;

    return (
        <div style={{ backgroundColor: 'var(--bg-primary)', minHeight: '100vh' }}>
            {/* 1. Hero Section */}
            <HeroSection 
                imageUrl={project.hero_image_url || 'data:image/svg+xml;charset=UTF-8,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22100%22 height=%22100%22%3E%3Crect width=%22100%25%22 height=%22100%25%22 fill=%22%23121212%22/%3E%3C/svg%3E'} 
                title={project.title} 
            />

            {/* 2. Sticky Gallery Tabs Navigation */}
            <GalleryTabs 
                galleries={project.galleries}
                activeSlug={activeGallerySlug}
                onTabChange={setActiveGallerySlug}
                expiresAt={project.expires_at_formatted}
                allowDownload={project.allow_download}
                projectSlug={project.slug}
            />

            {/* 3. Photo Grid Layout */}
            {isLoadingPhotos ? (
                <div style={{ display: 'flex', justifyContent: 'center', alignItems: 'center', minHeight: '300px' }}>
                    <div className="spinner" style={{ width: '32px', height: '32px' }}></div>
                </div>
            ) : (
                <JustifiedGrid 
                    photos={photos} 
                    onPhotoClick={(index) => {
                        setLightboxIndex(index);
                        setLightboxOpen(true);
                    }}
                />
            )}

            {/* 4. Lightbox Overlay */}
            <Lightbox 
                photos={photos}
                activeIndex={lightboxIndex}
                isOpen={lightboxOpen}
                onClose={() => setLightboxOpen(false)}
                allowDownload={project.allow_download}
            />

            {/* 5. Minimalistic footer */}
            <Footer />
        </div>
    );
}
