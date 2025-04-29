<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include authentication functions
require_once 'includes/auth.php';

// Check if user is logged in, redirect to dashboard
if (is_logged_in()) {
    header('Location: pages/dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Equipment Tracking System</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .hero {
            background-color: var(--secondary);
            padding: 4rem 0;
            text-align: center;
        }
        
        .hero-content {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .hero h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: var(--foreground);
        }
        
        .hero p {
            font-size: 1.125rem;
            color: var(--muted-foreground);
            margin-bottom: 2rem;
        }
        
        .cta-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }
        
        .features {
            padding: 4rem 0;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .feature-card {
            background-color: var(--card);
            border-radius: var(--radius);
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }
        
        .feature-icon {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background-color: var(--primary);
            color: var(--primary-foreground);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
        }
        
        .feature-card h3 {
            margin-bottom: 0.75rem;
            font-size: 1.25rem;
        }
        
        .feature-card p {
            color: var(--muted-foreground);
            font-size: 0.875rem;
        }
        
        @media (max-width: 640px) {
            .cta-buttons {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .hero {
                padding: 2rem 0;
            }
            
            .hero h1 {
                font-size: 1.75rem;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container header-container">
            <div class="logo">
                <a href="index.php">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: inline-block; vertical-align: -0.25em; margin-right: 0.5rem;">
                        <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                        <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                        <line x1="12" y1="22.08" x2="12" y2="12"></line>
                    </svg>
                    Equipment Tracking
                </a>
            </div>
            
            <nav>
                <ul>
                    <li><a href="index.php" class="active">Home</a></li>
                    <li><a href="login.php">Login</a></li>
                </ul>
            </nav>
        </div>
    </header>
    
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <h1>Manage Your Equipment Efficiently</h1>
                <p>Track equipment usage, manage projects, and ensure nothing gets lost or broken. The complete solution for educational institutions and labs.</p>
                
                <div class="cta-buttons">
                    <a href="login.php" class="btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.5rem;">
                            <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                            <polyline points="10 17 15 12 10 7"></polyline>
                            <line x1="15" y1="12" x2="3" y2="12"></line>
                        </svg>
                        Sign In
                    </a>
                </div>
            </div>
        </div>
    </section>
    
    <section class="features">
        <div class="container">
            <h2 style="text-align: center; margin-bottom: 1rem;">Key Features</h2>
            <p style="text-align: center; color: var(--muted-foreground); max-width: 600px; margin: 0 auto;">
                Designed specifically for academic and research environments, our system provides everything you need to keep track of your valuable equipment.
            </p>
            
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="3" y1="9" x2="21" y2="9"></line>
                            <line x1="9" y1="21" x2="9" y2="9"></line>
                        </svg>
                    </div>
                    <h3>Equipment Management</h3>
                    <p>Keep track of all equipment with detailed records including photos, technical specifications, and maintenance history.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                            <polyline points="2 17 12 22 22 17"></polyline>
                            <polyline points="2 12 12 17 22 12"></polyline>
                        </svg>
                    </div>
                    <h3>Project Tracking</h3>
                    <p>Associate equipment with specific projects, track usage, and ensure resources are allocated efficiently.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                    </div>
                    <h3>User Management</h3>
                    <p>Control access with role-based permissions, ensuring only authorized personnel can check out or modify equipment.</p>
                </div>
            </div>
        </div>
    </section>
    
    <section style="background-color: var(--primary); color: var(--primary-foreground); padding: 3rem 0; text-align: center;">
        <div class="container">
            <h2 style="margin-bottom: 1rem;">Ready to Get Started?</h2>
            <p style="margin-bottom: 2rem; max-width: 600px; margin-left: auto; margin-right: auto;">
                Start managing your equipment more efficiently today. Login with your credentials to access the system.
            </p>
            
            <a href="login.php" class="btn-secondary" style="background-color: white; color: var(--primary);">
                Sign In Now
            </a>
        </div>
    </section>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>