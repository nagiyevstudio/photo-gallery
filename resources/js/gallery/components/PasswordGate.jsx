import React, { useState } from 'react';
import { client } from '../api/client';

export default function PasswordGate({ projectSlug, onSuccess }) {
    const [password, setPassword] = useState('');
    const [error, setError] = useState('');
    const [shake, setShake] = useState(false);
    const [isLoading, setIsLoading] = useState(false);

    const handleSubmit = async (e) => {
        e.preventDefault();
        if (!password.trim()) return;

        setIsLoading(true);
        setError('');

        try {
            const res = await client.post(`/projects/${projectSlug}/verify-password`, { password });
            if (res.success) {
                onSuccess();
            } else {
                triggerShake();
            }
        } catch (err) {
            triggerShake();
            setError(err.data?.message || 'Access Denied. Check your password.');
        } finally {
            setIsLoading(false);
        }
    };

    const triggerShake = () => {
        setShake(true);
        setTimeout(() => setShake(false), 500);
    };

    return (
        <div className="password-gate-overlay">
            <div className={`password-card ${shake ? 'shake' : ''}`}>
                <div style={{ fontSize: '40px', marginBottom: '16px', color: 'var(--accent)' }}>🔒</div>
                <h3>Protected Gallery</h3>
                <p>Please enter the password provided to access this album.</p>
                
                <form onSubmit={handleSubmit}>
                    <input 
                        type="password" 
                        className="password-input" 
                        value={password}
                        onChange={(e) => setPassword(e.target.value)}
                        placeholder="Password"
                        required
                        disabled={isLoading}
                    />
                    
                    {error && (
                        <div style={{ color: 'var(--danger)', fontSize: '13px', marginBottom: '20px' }}>
                            {error}
                        </div>
                    )}

                    <button 
                        type="submit" 
                        className="btn-gold" 
                        style={{ width: '100%' }}
                        disabled={isLoading}
                    >
                        {isLoading ? 'Verifying...' : 'Access Album'}
                    </button>
                </form>
            </div>
        </div>
    );
}
