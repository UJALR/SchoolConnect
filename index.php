<?php require_once 'includes/header.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Algonquin Social Media Website - Welcome</title>
    <style>
        /* Reset and base styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        html, body {
            height: 100%;
            overflow: hidden;
        }

        /* Full-screen container */
        .fullscreen-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        /* Slideshow background */
        .slideshow {
            position: absolute;
            width: 100%;
            height: 100%;
            z-index: 1;
        }

        .slide {
            position: absolute;
            width: 100%;
            height: 100%;
            opacity: 0;
            transition: opacity 0.5s ease-in-out;
        }

        .slide.active {
            opacity: 1;
        }

        .slide img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            animation: zoomEffect 25s infinite ease-in-out;
        }

        @keyframes zoomEffect {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        /* Overlay */
        .overlay {
            position: absolute;
            width: 100%;
            height: 100%;
            background: linear-gradient(
                to bottom,
                rgba(0, 0, 0, 0.7) 0%,
                rgba(0, 0, 0, 0.5) 50%,
                rgba(0, 0, 0, 0.7) 100%
            );
            z-index: 2;
        }

        /* Content */
        .content {
            position: relative;
            z-index: 3;
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            color: white;
            padding: 20px;
        }

        .logo {
            max-height: 120px;
            width: auto;
            margin-bottom: 30px;
            filter: brightness(0) invert(1);
        }

        .welcome-title {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 20px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.7);
            line-height: 1.2;
        }

        .welcome-subtitle {
            font-size: 1.5rem;
            margin-bottom: 40px;
            max-width: 700px;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.7);
            line-height: 1.5;
        }

        .action-buttons {
            display: flex;
            gap: 20px;
        }

        .btn {
            display: inline-block;
            padding: 15px 40px;
            border-radius: 50px;
            font-size: 1.1rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: #006341;
            color: white;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .btn-primary:hover {
            background: #004d32;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
        }

        .btn-secondary {
            background: transparent;
            color: white;
            border: 2px solid white;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .welcome-title {
                font-size: 2.5rem;
            }
            
            .welcome-subtitle {
                font-size: 1.2rem;
                margin-bottom: 30px;
            }
            
            .logo {
                max-height: 90px;
                margin-bottom: 20px;
            }
            
            .action-buttons {
                flex-direction: column;
                gap: 15px;
            }
            
            .btn {
                width: 220px;
            }
        }

        @media (max-width: 480px) {
            .welcome-title {
                font-size: 2rem;
            }
            
            .welcome-subtitle {
                font-size: 1rem;
            }
            
            .logo {
                max-height: 70px;
            }
            
            .btn {
                padding: 12px 30px;
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="fullscreen-container">
        <!-- Background slideshow -->
        <div class="slideshow">
            <div class="slide active">
                <img src="assets/images/algonquin_main_image.jpeg" alt="Algonquin College Campus">
            </div>
            <div class="slide active">
                <img src="assets/images/Algonquin-6191-scaled.jpg" alt="Algonquin College">
            </div>
            <div class="slide">
                <img src="assets/images/algonquin_secondary_image.jpg" alt="Algonquin College Students">
            </div>
            
        </div>
        
        <!-- Overlay -->
        <div class="overlay"></div>
        
        <!-- Content -->
        <div class="content">
            <img src="assets/images/algonquin_logo_footer.png" alt="Algonquin College Logo" class="logo">
            
            <h1 class="welcome-title">Welcome to Algonquin Social</h1>
            <p class="welcome-subtitle">Connect with students, share experiences, and build your campus network</p>
            
            <div class="action-buttons">
                <a href="NewUser.php" class="btn btn-primary">Sign Up</a>
                <a href="Login.php" class="btn btn-secondary">Log In</a>
            </div>
        </div>
    </div>

    <script>
        // Simple slideshow functionality
        document.addEventListener('DOMContentLoaded', function() {
            const slides = document.querySelectorAll('.slide');
            let currentSlide = 0;
            
            function showNextSlide() {
                slides[currentSlide].classList.remove('active');
                currentSlide = (currentSlide + 1) % slides.length;
                slides[currentSlide].classList.add('active');
            }
            
            // Change slide every 5 seconds
            setInterval(showNextSlide, 3000);
            
            // Preload images
            const images = [
                'assets/images/algonquin_main_image.jpeg',
                'assets/images/algonquin_secondary_image.jpg',
                'assets/images/Algonquin-6191-scaled.jpg'
            ];
            
            images.forEach(src => {
                const img = new Image();
                img.src = src;
            });
        });
    </script>
<?php require_once 'includes/footer.php'; ?>