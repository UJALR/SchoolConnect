<?php require_once 'includes/header.php'; ?>
<body>
    <div class="fullscreen-container">
        <!-- Background slideshow -->
        <div class="slideshow">
            <div class="slide active">
                <!-- <img src="assets/images/algonquin_main_image.jpeg" alt="Algonquin College Campus"> -->
            </div>
            <div class="slide active">
                <!-- <img src="assets/images/Algonquin-6191-scaled.jpg" alt="Algonquin College"> -->
            </div>
            <div class="slide">
                <!-- <img src="assets/images/algonquin_secondary_image.jpg" alt="Algonquin College Students"> -->
            </div>
            
        </div>
        
        <!-- Overlay -->
        <div class="overlay"></div>
        
        <!-- Content -->
        <div class="content">
            <!-- <img src="assets/images/algonquin_logo_footer.png" alt="Algonquin College Logo" class="logo"> -->
            
            <h1 class="welcome-title">School Connect</h1>
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