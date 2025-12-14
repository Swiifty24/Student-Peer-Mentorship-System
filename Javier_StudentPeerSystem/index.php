<?php
require_once 'pages/init.php';

// If the user is already logged in, redirect them to the appropriate primary page
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['isTutorNow'] ?? false) {
        header("Location: pages/tutorRequests.php");
        exit();
    } else {
        header("Location: pages/findTutor.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home | PeerMentor</title>
    <link href="styles/landingStyle.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <style>
        /* Enhanced homepage styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            overflow-x: hidden;
        }

        .hero-section {
            position: relative;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            /* overflow: hidden; Removed to allow scrolling */
        }

        .hero-background {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            opacity: 0.15;
            z-index: 0;
        }

        .hero-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.95) 0%, rgba(118, 75, 162, 0.95) 100%);
            z-index: 1;
        }

        .hero-content {
            position: relative;
            z-index: 2;
            text-align: center;
            color: #ffffff;
            max-width: 900px;
            padding: 40px 20px;
            animation: fadeInUp 0.8s ease-out;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .hero-content h1 {
            font-size: clamp(2.5rem, 6vw, 4.5rem);
            font-weight: 800;
            margin-bottom: 24px;
            line-height: 1.1;
            text-shadow: 0 2px 20px rgba(0, 0, 0, 0.2);
        }

        .hero-content .tagline {
            font-size: clamp(1.1rem, 2.5vw, 1.5rem);
            font-weight: 300;
            margin-bottom: 16px;
            opacity: 0.95;
            letter-spacing: 0.5px;
        }

        .hero-content .subtitle {
            font-size: clamp(1rem, 2vw, 1.25rem);
            font-weight: 400;
            margin-bottom: 48px;
            opacity: 0.9;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }

        .cta-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 40px;
        }

        .btn {
            display: inline-block;
            padding: 18px 48px;
            font-size: 1.1rem;
            font-weight: 600;
            text-decoration: none;
            border-radius: 50px;
            transition: all 0.3s ease;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .btn-primary {
            background: white;
            color: #667eea;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(255, 255, 255, 0.3);
            background: #f8f9fa;
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 2px solid white;
            backdrop-filter: blur(10px);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(255, 255, 255, 0.2);
        }

        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 32px;
            margin-top: 60px;
            max-width: 1000px;
            margin-left: auto;
            margin-right: auto;
        }

        .feature-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 32px 24px;
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }

        .feature-card:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: translateY(-4px);
        }

        .feature-icon {
            font-size: 3rem;
            margin-bottom: 16px;
            display: block;
        }

        .feature-card h3 {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 12px;
        }

        .feature-card p {
            font-size: 1rem;
            opacity: 0.9;
            line-height: 1.6;
        }

        .stats {
            display: flex;
            justify-content: center;
            gap: 60px;
            margin-top: 60px;
            flex-wrap: wrap;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 3rem;
            font-weight: 800;
            display: block;
            margin-bottom: 8px;
        }

        .stat-label {
            font-size: 1rem;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 500;
        }

        .login-link {
            margin-top: 32px;
            font-size: 1.1rem;
        }

        .login-link a {
            color: white;
            text-decoration: none;
            border-bottom: 2px solid rgba(255, 255, 255, 0.5);
            padding-bottom: 2px;
            transition: all 0.3s ease;
        }

        .login-link a:hover {
            border-bottom-color: white;
        }

        /* Floating particles effect */
        .particle {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            pointer-events: none;
            animation: float 15s infinite;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0) translateX(0);
                opacity: 0.3;
            }

            50% {
                transform: translateY(-100px) translateX(50px);
                opacity: 0.6;
            }
        }

        @media (max-width: 768px) {
            .hero-content {
                padding: 20px;
            }

            .cta-buttons {
                flex-direction: column;
                align-items: stretch;
            }

            .btn {
                width: 100%;
                max-width: 400px;
                margin: 0 auto;
            }

            .features {
                grid-template-columns: 1fr;
                gap: 24px;
            }

            .stats {
                gap: 40px;
            }
        }
    </style>
</head>

<body>
    <div class="hero-section">
        <!-- Background image -->
        <img src="styles/hero-image.png" alt="Students learning together" class="hero-background">

        <!-- Gradient overlay -->
        <div class="hero-overlay"></div>

        <!-- Floating particles -->
        <div class="particle" style="width: 100px; height: 100px; top: 10%; left: 10%; animation-delay: 0s;"></div>
        <div class="particle" style="width: 60px; height: 60px; top: 60%; left: 80%; animation-delay: 3s;"></div>
        <div class="particle" style="width: 80px; height: 80px; top: 80%; left: 15%; animation-delay: 6s;"></div>
        <div class="particle" style="width: 40px; height: 40px; top: 30%; left: 70%; animation-delay: 9s;"></div>

        <!-- Main content -->
        <div class="hero-content">
            <h1>Connect. Learn. Succeed.</h1>
            <p class="tagline">Your Free Peer-to-Peer Tutoring Network</p>
            <p class="subtitle">Find help from fellow students or share your expertise. Academic success starts with
                collaboration.</p>

            <div class="cta-buttons">
                <a href="pages/register.php" class="btn btn-primary">Get Started Free</a>
                <a href="pages/login.php" class="btn btn-secondary">Sign In</a>
            </div>

            <!-- Feature cards -->
            <div class="features">
                <div class="feature-card">
                    <span class="feature-icon">🎓</span>
                    <h3>Expert Tutors</h3>
                    <p>Connect with top-performing students in your courses</p>
                </div>
                <div class="feature-card">
                    <span class="feature-icon">💬</span>
                    <h3>Easy Scheduling</h3>
                    <p>Request sessions and get confirmations instantly</p>
                </div>
                <div class="feature-card">
                    <span class="feature-icon">✨</span>
                    <h3>100% Free</h3>
                    <p>No hidden fees. Just students helping students</p>
                </div>
            </div>

            <!-- Stats -->
            <div class="stats">
                <div class="stat-item">
                    <span class="stat-number">100%</span>
                    <span class="stat-label">Free</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">24/7</span>
                    <span class="stat-label">Available</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">∞</span>
                    <span class="stat-label">Courses</span>
                </div>
            </div>

            <div class="login-link">
                Already a member? <a href="pages/login.php">Log in here</a>
            </div>
        </div>
    </div>
</body>

</html>