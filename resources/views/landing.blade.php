<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Private Client Galleries | Nagiyev Studio</title>
    <link rel="icon" type="image/png" href="/logo.png">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg-primary: #0a0a0a;
            --bg-secondary: #111111;
            --bg-elevated: #161616;
            --accent: #c8a97e;
            --accent-hover: #d4b88a;
            --text-primary: #f5f5f5;
            --text-secondary: #999999;
            --text-muted: #666666;
            --border: rgba(255, 255, 255, 0.06);
            --font-display: 'Outfit', sans-serif;
            --font-body: 'Inter', sans-serif;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: var(--bg-primary);
            color: var(--text-primary);
            font-family: var(--font-body);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow-x: hidden;
            position: relative;
        }

        /* Ambient Glowing Background Backgrounds */
        body::before {
            content: '';
            position: absolute;
            top: -20%;
            left: 50%;
            transform: translateX(-50%);
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(200, 169, 126, 0.08) 0%, rgba(10, 10, 10, 0) 70%);
            z-index: 0;
            pointer-events: none;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 40px 24px;
            width: 100%;
            z-index: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            flex-grow: 1;
        }

        /* Header / Logo */
        header {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 60px;
            text-align: center;
            animation: fadeInDown 0.8s ease-out forwards;
        }

        .logo-img {
            width: 64px;
            height: 64px;
            object-fit: contain;
            margin-bottom: 20px;
        }

        h1 {
            font-family: var(--font-display);
            font-size: 28px;
            font-weight: 500;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: var(--text-primary);
            margin-bottom: 8px;
        }

        .subtitle {
            font-size: 15px;
            color: var(--text-secondary);
            max-width: 500px;
            line-height: 1.6;
            font-weight: 300;
        }

        /* Card Section */
        .cards-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            width: 100%;
            margin-bottom: 50px;
            animation: fadeInUp 0.8s ease-out 0.2s forwards;
            opacity: 0;
        }

        .info-card {
            background-color: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 32px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: transform 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .info-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--accent), transparent);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .info-card:hover {
            transform: translateY(-4px);
            border-color: rgba(200, 169, 126, 0.25);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }

        .info-card:hover::after {
            opacity: 1;
        }

        .card-title {
            font-family: var(--font-display);
            font-size: 18px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--accent);
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .card-text {
            color: var(--text-secondary);
            font-size: 14px;
            line-height: 1.6;
            font-weight: 300;
            margin-bottom: 24px;
        }

        /* Buttons & Actions */
        .btn-gold {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background-color: var(--accent);
            color: #0a0a0a;
            border: none;
            padding: 12px 24px;
            font-family: var(--font-display);
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            transition: background-color 0.2s ease, transform 0.2s ease;
            width: fit-content;
        }

        .btn-gold:hover {
            background-color: var(--accent-hover);
            transform: translateY(-1px);
        }

        .btn-gold:active {
            transform: translateY(1px);
        }

        /* Footer */
        footer {
            text-align: center;
            padding: 30px 24px;
            border-top: 1px solid var(--border);
            font-size: 12px;
            color: var(--text-muted);
            width: 100%;
            z-index: 1;
            animation: fadeIn 1s ease-out 0.4s forwards;
            opacity: 0;
        }

        /* Keyframes Animations */
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeIn {
            to {
                opacity: 1;
            }
        }

        /* Mobile adaptation */
        @media (max-width: 768px) {
            .cards-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }
            header {
                margin-bottom: 40px;
            }
            h1 {
                font-size: 24px;
            }
            .info-card {
                padding: 24px;
            }
        }
    </style>
</head>
<body>

    <div class="container">
        <header>
            <img src="/logo.png" alt="Nagiyev Studio Logo" class="logo-img">
            <h1>Nagiyev Studio</h1>
            <p class="subtitle">Private client gallery space. View your photos in high-resolution, share with friends, and download original files securely.</p>
        </header>

        <div class="cards-grid">
            <!-- Card 1: Clients -->
            <div class="info-card">
                <div>
                    <h3 class="card-title">
                        <span>For Clients</span>
                    </h3>
                    <p class="card-text">
                        If you had a photo session with me, you will receive a unique direct link to your personal gallery. Open the link to browse, review, and download your high-quality original photos. If your gallery has password protection, enter the passcode provided by the studio.
                    </p>
                </div>
            </div>

            <!-- Card 2: Bookings -->
            <div class="info-card">
                <div>
                    <h3 class="card-title">
                        <span>Book a Shoot</span>
                    </h3>
                    <p class="card-text">
                        Looking for professional photography services, commercial projects, or personal portrait sessions? Visit my main portfolio website to explore my work, view completed projects, and get in touch for booking availability.
                    </p>
                </div>
                <a href="https://photo.nagiyev.com" target="_blank" class="btn-gold">
                    Visit Portfolio ↗
                </a>
            </div>
        </div>
    </div>

    <footer>
        <p>© {{ date('Y') }} Nagiyev Studio. All rights reserved.</p>
    </footer>

</body>
</html>
