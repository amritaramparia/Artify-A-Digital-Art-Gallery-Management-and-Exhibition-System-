<?php 
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us | Artify - Online Art Gallery</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --orange1: #f43a09;
            --orange2: #ffb766;
            --bluegreen: #c2edda;
            --green: #68d388;
            --dark: #2c2c54;
            --light: #f5f5f5;
            --dark-bg: #1a1a1a;
            --dark-secondary: #2a2a2a;
            --text-light: #ccc;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, var(--light) 0%, #e8f4f1 100%);
            color: var(--dark);
            min-height: 100vh;
            padding-top: 85px;
        }

        .about-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .page-header {
            text-align: center;
            margin-bottom: 60px;
            padding: 60px 40px;
            background: linear-gradient(135deg, var(--dark-bg) 0%, var(--dark-secondary) 50%, var(--dark-bg) 100%);
            border-radius: 20px;
            color: var(--text-light);
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .page-header::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 20% 80%, 
                    rgba(255, 183, 102, 0.15) 0%, 
                    transparent 20%),
                radial-gradient(circle at 80% 20%, 
                    rgba(104, 211, 136, 0.15) 0%, 
                    transparent 20%);
            z-index: 1;
        }

        .page-title {
            font-size: 4rem;
            font-weight: 800;
            margin-bottom: 20px;
            position: relative;
            z-index: 2;
            background: linear-gradient(45deg, #f43a09, #ffb766);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .page-subtitle {
            font-size: 1.3rem;
            opacity: 0.9;
            font-weight: 300;
            position: relative;
            z-index: 2;
            color: var(--text-light);
        }

        /* Introduction Section */
        .intro-section {
            text-align: center;
            margin-bottom: 60px;
            padding: 50px 40px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(44, 44, 84, 0.1);
        }

        .intro-text {
            font-size: 1.4rem;
            line-height: 1.8;
            color: var(--dark);
            max-width: 900px;
            margin: 0 auto;
            font-weight: 400;
        }

        .intro-highlight {
            color: var(--orange1);
            font-weight: 600;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 50px;
            margin-bottom: 60px;
        }

        .story-section, .mission-section {
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(44, 44, 84, 0.15);
            transition: all 0.4s ease;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .story-section:hover, .mission-section:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(44, 44, 84, 0.25);
        }

        .section-title {
            font-size: 2.2rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 25px;
            position: relative;
            display: inline-block;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 0;
            width: 60px;
            height: 4px;
            background: linear-gradient(90deg, var(--orange1), var(--orange2));
            border-radius: 2px;
        }

        .story-text, .mission-text {
            font-size: 1.1rem;
            line-height: 1.8;
            color: #555;
            margin-bottom: 25px;
        }

        .highlight {
            color: var(--orange1);
            font-weight: 600;
        }

        .benefits-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-bottom: 60px;
        }

        .benefit-card {
            background: white;
            padding: 35px 30px;
            border-radius: 16px;
            text-align: center;
            box-shadow: 0 10px 25px rgba(44, 44, 84, 0.1);
            transition: all 0.4s ease;
            border: 2px solid transparent;
            transform-style: preserve-3d;
            transform: perspective(1000px) rotateX(0deg) rotateY(0deg) scale(1);
        }

        .benefit-card:hover {
            transform: perspective(1000px) rotateX(5deg) rotateY(-5deg) scale(1.03);
            border-color: var(--orange1);
            box-shadow: 0 20px 40px rgba(244, 58, 9, 0.15);
        }

        .benefit-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--orange1), var(--orange2));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 2rem;
            color: white;
            box-shadow: 0 8px 20px rgba(244, 58, 9, 0.3);
        }

        .benefit-title {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 15px;
        }

        .benefit-description {
            color: #666;
            line-height: 1.6;
            font-size: 1rem;
        }

        .values-section {
            background: linear-gradient(135deg, var(--dark-bg) 0%, var(--dark-secondary) 100%);
            padding: 60px 40px;
            border-radius: 20px;
            color: var(--text-light);
            margin-bottom: 60px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .values-title {
            font-size: 2.5rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 50px;
            color: var(--text-light);
        }

        .values-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
        }

        .value-item {
            text-align: center;
            padding: 30px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .value-item:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-5px);
        }

        .value-icon {
            font-size: 2.5rem;
            color: var(--orange2);
            margin-bottom: 20px;
        }

        .value-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--text-light);
        }

        .value-description {
            color: #ccc;
            line-height: 1.6;
        }

        .contact-section {
            background: white;
            padding: 50px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(44, 44, 84, 0.1);
            text-align: center;
        }

        .contact-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 40px;
        }

        .contact-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }

        .contact-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 25px;
            background: var(--light);
            border-radius: 15px;
            transition: all 0.3s ease;
        }

        .contact-item:hover {
            background: linear-gradient(135deg, var(--bluegreen), var(--green));
            transform: scale(1.05);
        }

        .contact-icon {
            width: 60px;
            height: 60px;
            background: var(--dark);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            font-size: 1.5rem;
            color: white;
        }

        .contact-label {
            font-size: 1rem;
            color: #666;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .contact-value {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--dark);
        }

        .back-to-home {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 15px 30px;
            background: linear-gradient(135deg, var(--orange1), var(--orange2));
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            margin-top: 30px;
            border: none;
            cursor: pointer;
            box-shadow: 0 6px 15px rgba(244, 58, 9, 0.3);
        }

        .back-to-home:hover {
            background: linear-gradient(135deg, var(--dark), #3a3a70);
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(44, 44, 84, 0.3);
        }

        @media (max-width: 768px) {
            .content-grid {
                grid-template-columns: 1fr;
                gap: 30px;
            }

            .page-title {
                font-size: 2.5rem;
            }

            .section-title {
                font-size: 1.8rem;
            }

            .contact-info {
                grid-template-columns: 1fr;
            }

            .benefits-grid {
                grid-template-columns: 1fr;
            }

            .story-section, .mission-section, .contact-section, .intro-section {
                padding: 30px 20px;
            }

            .intro-text {
                font-size: 1.2rem;
            }

            body {
                padding-top: 120px;
            }
        }

        @media (max-width: 480px) {
            .page-title {
                font-size: 2rem;
            }

            .section-title {
                font-size: 1.5rem;
            }

            .values-grid {
                grid-template-columns: 1fr;
            }

            .intro-text {
                font-size: 1.1rem;
            }
        }
    </style>
</head>
<body>

    <?php include('header.php'); ?>

    <div class="about-container">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">About Artify</h1>
            <p class="page-subtitle">Transforming the Art World - One Connection at a Time</p>
        </div>

        <!-- Introduction Section -->
        <div class="intro-section">
            <p class="intro-text">
                Welcome to <span class="intro-highlight">Artify</span>, where creativity meets opportunity. 
                We are a revolutionary online art platform dedicated to bridging the gap between 
                <span class="intro-highlight">talented artists</span> and <span class="intro-highlight">passionate art collectors</span>. 
                Our mission is to create a vibrant, inclusive community where artistic expression flourishes 
                and beautiful connections are made across the globe.
            </p>
        </div>

        <!-- Vision & Commitment -->
        <div class="content-grid">
            <div class="story-section">
                <h2 class="section-title">Our Vision</h2>
                <p class="story-text">
                    Artify was born from a simple yet powerful belief: <span class="highlight">great art deserves to be seen</span>. In a world where talented artists often struggle to find their audience and art lovers face barriers in discovering authentic creations, we envisioned a platform that bridges this gap seamlessly.
                </p>
                <p class="story-text">
                    We understand that every brushstroke tells a story, every sculpture holds emotion, and every photograph captures a moment in time. Our mission is to ensure these artistic expressions find their way to people who will cherish them, creating meaningful connections between creators and collectors worldwide.
                </p>
                <p class="story-text">
                    Through cutting-edge technology and a deep understanding of the art community, we've built a space where creativity flourishes, talent is recognized, and beautiful relationships between artists and art enthusiasts are formed every day.
                </p>
            </div>

            <div class="mission-section">
                <h2 class="section-title">Our Commitment</h2>
                <p class="mission-text">
                    At Artify, we are committed to <span class="highlight">democratizing art access</span> while maintaining the highest standards of quality and authenticity. We believe that art should be accessible to everyone, regardless of their location or background.
                </p>
                <p class="mission-text">
                    Our platform is built on the principles of transparency, fairness, and community. We ensure that artists receive the recognition and compensation they deserve while providing buyers with genuine, high-quality artworks and complete peace of mind.
                </p>
                <p class="mission-text">
                    We continuously innovate to make the art-buying experience seamless, secure, and enjoyable for all parties involved, fostering a sustainable ecosystem where art can thrive and evolve for generations to come.
                </p>
            </div>
        </div>

        <!-- Benefits Grid -->
        <div class="benefits-section">
            <h2 class="section-title" style="text-align: center; margin-bottom: 50px;">Why Artify Makes a Difference</h2>
            <div class="benefits-grid">
                <div class="benefit-card">
                    <div class="benefit-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3 class="benefit-title">For Art Lovers</h3>
                    <p class="benefit-description">Discover authentic artworks from verified artists worldwide. Enjoy secure transactions, quality assurance, and direct connections with creators. Find pieces that truly resonate with your soul and transform your space.</p>
                </div>

                <div class="benefit-card">
                    <div class="benefit-icon">
                        <i class="fas fa-palette"></i>
                    </div>
                    <h3 class="benefit-title">For Artists</h3>
                    <p class="benefit-description">Showcase your talent to a global audience without barriers. Receive fair compensation, build your brand, and connect directly with art enthusiasts who appreciate your unique creative vision and style.</p>
                </div>

                <div class="benefit-card">
                    <div class="benefit-icon">
                        <i class="fas fa-globe-americas"></i>
                    </div>
                    <h3 class="benefit-title">Global Impact</h3>
                    <p class="benefit-description">We're building bridges across cultures through art. By connecting artists and collectors worldwide, we're fostering cultural exchange and preserving diverse artistic traditions for future generations.</p>
                </div>

                <div class="benefit-card">
                    <div class="benefit-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3 class="benefit-title">Trust & Security</h3>
                    <p class="benefit-description">Your privacy and security are our top priorities. We employ bank-level encryption, secure payment processing, and strict verification processes to ensure every transaction is safe and protected.</p>
                </div>

                <div class="benefit-card">
                    <div class="benefit-icon">
                        <i class="fas fa-hand-holding-heart"></i>
                    </div>
                    <h3 class="benefit-title">Community Building</h3>
                    <p class="benefit-description">Join a supportive network where artists can collaborate, share knowledge, and grow together. We're more than a marketplace - we're a community that nurtures creativity and artistic development.</p>
                </div>

                <div class="benefit-card">
                    <div class="benefit-icon">
                        <i class="fas fa-rocket"></i>
                    </div>
                    <h3 class="benefit-title">Innovation</h3>
                    <p class="benefit-description">Leveraging the latest technology to revolutionize how art is discovered, purchased, and appreciated. We're constantly evolving to provide the best possible experience for our community.</p>
                </div>
            </div>
        </div>

        <!-- Core Values -->
        <div class="values-section">
            <h2 class="values-title">Our Core Values</h2>
            <div class="values-grid">
                <div class="value-item">
                    <div class="value-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <h3 class="value-title">Quality First</h3>
                    <p class="value-description">Every artwork undergoes rigorous quality checks to ensure excellence and authenticity for our customers.</p>
                </div>

                <div class="value-item">
                    <div class="value-icon">
                        <i class="fas fa-handshake"></i>
                    </div>
                    <h3 class="value-title">Transparency</h3>
                    <p class="value-description">Clear communication, honest pricing, and open processes build trust with both artists and collectors.</p>
                </div>

                <div class="value-item">
                    <div class="value-icon">
                        <i class="fas fa-heart"></i>
                    </div>
                    <h3 class="value-title">Community Focus</h3>
                    <p class="value-description">We prioritize building meaningful relationships and supporting the growth of our artistic community.</p>
                </div>

                <div class="value-item">
                    <div class="value-icon">
                        <i class="fas fa-lock"></i>
                    </div>
                    <h3 class="value-title">Privacy Protection</h3>
                    <p class="value-description">Your personal information and data are protected with the highest security standards and practices.</p>
                </div>
            </div>
        </div>

        <!-- Contact Information -->
        <div class="contact-section">
            <h2 class="contact-title">Get In Touch With Us</h2>
            <p style="color: #666; margin-bottom: 30px; font-size: 1.1rem; line-height: 1.6;">
                Have questions about our platform? Interested in showcasing your artwork? Need assistance with a purchase? 
                We're here to help you every step of the way in your artistic journey.
            </p>
            <div class="contact-info">
                <div class="contact-item">
                    <div class="contact-icon">
                        <i class="fas fa-phone"></i>
                    </div>
                    <div class="contact-label">Phone Number</div>
                    <div class="contact-value">+91 8585869586</div>
                </div>

                <div class="contact-item">
                    <div class="contact-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="contact-label">Email Address</div>
                    <div class="contact-value">artify@gmail.com</div>
                </div>
            </div>
            
            <a href="artgallary.php" class="back-to-home">
                <i class="fas fa-arrow-left"></i>
                Explore Beautiful Artworks
            </a>
        </div>
    </div>

    <?php include('footer.html'); ?>
</body>
</html>