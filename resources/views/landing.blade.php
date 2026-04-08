<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Natural Health Clinic in Lagos | Naturopathic & Wellness Center – Optimal Consult</title>
    <meta name="description" content="Optimal Consult is a leading naturopathic clinic in Lagos offering natural health treatments, massage therapy, hydrotherapy, and holistic wellness care. Call 08067131990.">
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('images/logo.png') }}?v=3" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <!-- Swiper.js CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <style>
        :root {
            --primary: #7D9D85;
            --primary-dark: #5A7260;
            --secondary: #E9E5D6;
            --accent: #D4A373;
            --text-dark: #2C3333;
            --text-light: #F9F9F9;
            --bg-light: #FAF9F6;
            --white: #FFFFFF;
            --shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            line-height: 1.6;
            color: var(--text-dark);
            background-color: var(--bg-light);
            overflow-x: hidden;
        }

        h1, h2, h3 {
            font-family: 'Playfair Display', serif;
            color: var(--text-dark);
            line-height: 1.2;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        /* --- NAVIGATION --- */
        nav {
            position: fixed;
            top: 0;
            width: 100%;
            height: 80px;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            z-index: 1000;
            display: flex;
            align-items: center;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .nav-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-dark);
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .nav-links a {
            text-decoration: none;
            color: var(--text-dark);
            margin-left: 2rem;
            font-weight: 600;
            font-size: 0.9rem;
            transition: var(--transition);
        }

        .nav-links a:hover {
            color: var(--primary);
        }

        /* --- HERO SECTION --- */
        .hero {
            height: calc(100vh - 0px);
            position: relative;
            background: #000;
            overflow: hidden;
        }

        .swiper {
            width: 100%;
            height: 100%;
        }

        .swiper-slide {
            position: relative;
        }

        .swiper-slide img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            filter: brightness(0.7);
        }

        .hero-slider-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            color: var(--white);
            z-index: 10;
            width: 90%;
            max-width: 800px;
        }

        .hero-slider-content h2 {
            font-size: 3.5rem;
            color: var(--white);
            margin-bottom: 1.5rem;
            animation: fadeInUp 1s ease-out forwards;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .hero h1 {
            font-size: 3.5rem;
            margin-bottom: 1.5rem;
        }

        .btn {
            display: inline-block;
            background: var(--primary);
            color: var(--white);
            padding: 1.2rem 2.5rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(125, 157, 133, 0.3);
            transition: var(--transition);
        }

        .btn:hover {
            background: var(--primary-dark);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(125, 157, 133, 0.4);
        }

        .phone-cta {
            display: block;
            margin-top: 1rem;
            font-weight: 700;
            color: var(--primary-dark);
            font-size: 1.1rem;
        }

        /* --- SERVICES --- */
        .services {
            padding: 100px 0;
            background: var(--white);
        }

        .section-header {
            text-align: center;
            max-width: 700px;
            margin: 0 auto 60px;
        }

        .section-header h2 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .card {
            background: var(--bg-light);
            padding: 3rem 2rem;
            border-radius: 20px;
            text-align: center;
            transition: var(--transition);
            border: 1px solid rgba(0, 0, 0, 0.02);
        }

        .card:hover {
            transform: translateY(-10px);
            background: var(--white);
            box-shadow: var(--shadow);
        }

        .card-icon {
            font-size: 2.5rem;
            margin-bottom: 1.5rem;
            color: var(--primary);
        }

        .card h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .cms-content p {
            margin-bottom: 1.5rem;
        }

        .cms-content ul {
            list-style: none;
            margin-bottom: 1.5rem;
        }

        .cms-content li {
            position: relative;
            padding-left: 0;
            margin-bottom: 0.8rem;
        }

        /* --- KEYWORDS SECTION --- */
        .keywords-content {
            padding: 100px 0;
            background: var(--secondary);
        }

        .content-flex {
            display: flex;
            align-items: center;
            gap: 4rem;
        }

        .content-text {
            flex: 1;
        }

        .content-image {
            flex: 1;
            background: var(--primary);
            height: 400px;
            border-radius: 30px;
            position: relative;
            overflow: hidden;
        }

        .content-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            opacity: 0.8;
        }

        /* --- FOOTER --- */
        footer {
            background: var(--text-dark);
            color: var(--text-light);
            padding: 80px 0 30px;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 4rem;
            margin-bottom: 60px;
        }

        .footer-col h4 {
            color: var(--primary);
            font-family: 'Inter', sans-serif;
            font-weight: 700;
            margin-bottom: 1.5rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .footer-col p, .footer-col a {
            color: #ccc;
            text-decoration: none;
            margin-bottom: 0.8rem;
            display: block;
        }

        .footer-bottom {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 30px;
            text-align: center;
            font-size: 0.9rem;
            color: #888;
        }

        /* --- ANIMATIONS --- */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .hero-content {
            animation: fadeIn 1s ease-out forwards;
        }

        /* --- MOBILE RESPONSIVE --- */
        @media (max-width: 768px) {
            .hero h1 { font-size: 2.5rem; }
            .content-flex { flex-direction: column; }
            .nav-links { display: none; }
        }
    </style>
</head>
<body>

    <nav>
        <div class="container nav-content">
            <div class="logo">Optimal Consult</div>
            <div class="nav-links">
                <a href="/shop">Shop Products</a>
                <a href="/blog">Blog</a>
                <a href="#services">Services</a>
                <a href="#about">About</a>
                <a href="#contact">Contact</a>
            </div>
        </div>
    </nav>

    <header class="hero">
        <div class="swiper myHeroSwiper">
            <div class="swiper-wrapper">
                @forelse($sliders as $slider)
                <div class="swiper-slide">
                    <img src="{{ $slider->image_url }}" alt="{{ $slider->title }}">
                    @if($slider->title || $slider->sub_title)
                    <div class="hero-slider-content">
                        @if($slider->title) <h2>{{ $slider->title }}</h2> @endif
                        @if($slider->sub_title) <p>{{ $slider->sub_title }}</p> @endif
                        @if($slider->link)
                            <a href="{{ $slider->link }}" class="btn">Learn More</a>
                        @endif
                    </div>
                    @endif
                </div>
                @empty
                <div class="swiper-slide">
                    <div style="width:100%; height:100%; background:#2C3333; display:flex; align-items:center; justify-content:center; color:white;">
                        <h2>Welcome to Optimal Consult</h2>
                    </div>
                </div>
                @endforelse
            </div>
            <!-- Add Pagination if needed -->
            <div class="swiper-pagination"></div>
            <!-- Add Navigation if needed -->
            <div class="swiper-button-next"></div>
            <div class="swiper-button-prev"></div>
        </div>
    </header>

    <section id="services" class="services">
        <div class="container">
            <div class="section-header">
                <h2>Our Holistic Wellness Services</h2>
                <p>Comprehensive natural healing clinic near you, offering the best in alternative medicine in Nigeria.</p>
            </div>
            <div class="grid">
                @foreach($services as $service)
                <div class="card">
                    <div class="card-icon">{{ $service->icon }}</div>
                    <h3>{{ $service->title }}</h3>
                    <p>{{ $service->description }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    <section id="about" class="keywords-content">
        <div class="container content-flex">
            <div class="content-text">
                <h2>{{ $about->title ?? 'Holistic Wellness Center Lagos' }}</h2>
                <div class="cms-content mt-4">
                    {!! $about->content ?? 'Welcome to Optimal Consult, the leading wellness center in Lagos Nigeria. We believe in the power of natural healing and preventive healthcare. Our clinic provides a serene environment for your journey toward complete physical and mental well-being.' !!}
                </div>
                <br>
                @if($about->button_text)
                    <a href="{{ $about->button_link ?? '#' }}" class="btn">{{ $about->button_text }}</a>
                @endif
            </div>
            <div class="content-image">
                @if($about->image)
                    <img src="{{ asset('storage/' . $about->image) }}" alt="{{ $about->title }}" style="width: 100%; height: 100%; object-fit: cover;">
                @else
                    <div style="width:100%; height:100%; background: linear-gradient(45deg, #7D9D85, #E9E5D6); display:flex; align-items:center; justify-content:center; color:white; font-size:2rem; font-family:\'Playfair Display\';">
                        Wellness & Healing
                    </div>
                @endif
            </div>
        </div>
    </section>

    <section class="services" style="background:#FAF9F6">
        <div class="container">
            <div class="section-header">
                <h2>Why Choose Natural Healing?</h2>
                <p>Join the movement towards alternative medicine in Nigeria for a more sustainable and healthy lifestyle.</p>
            </div>
            <div class="grid">
                <div style="padding:1rem; border-left: 4px solid var(--primary);">
                    <h4 style="margin-bottom:0.5rem">Preventive Care</h4>
                    <p>Focus on maintaining health and preventing disease before it starts.</p>
                </div>
                <div style="padding:1rem; border-left: 4px solid var(--primary);">
                    <h4 style="margin-bottom:0.5rem">Stress Relief</h4>
                    <p>Tailored stress relief therapy in Lagos to combat the hustle of city life.</p>
                </div>
                <div style="padding:1rem; border-left: 4px solid var(--primary);">
                    <h4 style="margin-bottom:0.5rem">Personalized Care</h4>
                    <p>Every treatment plan at our naturopathic clinic is unique to the individual.</p>
                </div>
            </div>
        </div>
    </section>

    <footer id="contact">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <div class="logo" style="color:var(--white); margin-bottom:1.5rem">Optimal Consult</div>
                    <p>Your sanctuary for natural health and holistic wellness in Lagos.</p>
                </div>
                <div class="footer-col">
                    <h4>Quick Links</h4>
                    <a href="/shop">Shop Products</a>
                    <a href="#services">Our Services</a>
                    <a href="#about">About Us</a>
                    <a href="/login">Patient Login</a>
                </div>
                <div class="footer-col">
                    <h4>Contact Us</h4>
                    <p>📍 Lagos, Nigeria</p>
                    <a href="tel:08067131990">📞 08067131990</a>
                    <p>✉️ info@optimalconsult.org</p>
                </div>
                <div class="footer-col">
                    <h4>Stay Healthy</h4>
                    <p>Subscribe to our wellness tips and natural healing guides.</p>
                </div>
            </div>
            <div class="footer-bottom">
                &copy; {{ date('Y') }} Optimal Consult Pharmacy. All rights reserved. | SEO by Antigravity AI
            </div>
            @include('partials.cloud-footer')
        </div>
    </footer>

    <!-- Swiper.js JS -->
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const swiper = new Swiper('.myHeroSwiper', {
                loop: true,
                effect: 'fade',
                fadeEffect: {
                    crossFade: true
                },
                autoplay: {
                    delay: 5000,
                    disableOnInteraction: false,
                },
                pagination: {
                    el: '.swiper-pagination',
                    clickable: true,
                },
                navigation: {
                    nextEl: '.swiper-button-next',
                    prevEl: '.swiper-button-prev',
                },
            });
        });
    </script>
</body>
</html>
