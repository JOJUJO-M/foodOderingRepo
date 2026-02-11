<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Misosi Kiganjani - Premium Food Delivery</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #ff4757;
            --primary-dark: #e84118;
            --secondary: #2f3542;
            --accent: #ffa502;
            --bg: #f1f2f6;
            --card-bg: rgba(255, 255, 255, 0.9);
        }

        body {
            font-family: 'Outfit', sans-serif;
            background: var(--bg);
            margin: 0;
            color: var(--secondary);
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 5%;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nav-links {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .btn-login {
            background: var(--primary);
            color: white;
            padding: 0.6rem 1.5rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(255, 71, 87, 0.3);
        }

        .hero {
            height: 60vh;
            background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=1350&q=80');
            background-size: cover;
            background-position: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: white;
            text-align: center;
            padding: 0 20px;
        }

        .hero h1 {
            font-size: 3.5rem;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 10px rgba(0, 0, 0, 0.3);
        }

        .hero p {
            font-size: 1.2rem;
            max-width: 600px;
            opacity: 0.9;
        }

        .menu-section {
            padding: 4rem 5%;
        }

        .section-title {
            text-align: center;
            margin-bottom: 3rem;
        }

        .section-title h2 {
            font-size: 2.5rem;
            color: var(--secondary);
            margin-bottom: 0.5rem;
        }

        .section-title .underline {
            width: 80px;
            height: 4px;
            background: var(--primary);
            margin: 0 auto;
            border-radius: 2px;
        }

        .food-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 30px;
        }

        .food-card {
            background: var(--card-bg);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            position: relative;
        }

        .food-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        .food-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .food-info {
            padding: 1.5rem;
        }

        .food-name {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--secondary);
        }

        .food-desc {
            font-size: 0.9rem;
            color: #7f8c8d;
            margin-bottom: 1rem;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .food-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .food-price {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--primary);
        }

        .btn-order {
            background: var(--secondary);
            color: white;
            border: none;
            padding: 0.5rem 1.2rem;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-order:hover {
            background: var(--primary);
        }

        footer {
            background: var(--secondary);
            color: white;
            padding: 3rem 5%;
            text-align: center;
        }

        .footer-logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 1rem;
            display: block;
        }

        .copyright {
            opacity: 0.6;
            font-size: 0.9rem;
        }

        /* Animations */
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

        .fade-up {
            animation: fadeInUp 0.8s ease forwards;
        }

        .delay-1 {
            animation-delay: 0.2s;
        }

        .delay-2 {
            animation-delay: 0.4s;
        }
    </style>
</head>

<body>

    <nav class="navbar">
        <a href="index.php" class="logo">
            <span>🍽️</span> Misosi Kiganjani
        </a>
        <div class="nav-links">
            <?php if (isset($_SESSION['user_id'])): ?>
            <a href="dashboard_<?php echo $_SESSION['role']; ?>.php" class="btn-login">Dashboard</a>
            <?php
else: ?>
            <a href="login.php" class="btn-login">Login / Register</a>
            <?php
endif; ?>
        </div>
    </nav>

    <header class="hero">
        <h1 class="fade-up">Delicious Food, <br>Delivered Fast</h1>
        <p class="fade-up delay-1">The best local and international cuisines at your doorstep. Order now and enjoy the
            taste of excellence.</p>
    </header>

    <main class="menu-section">
        <div class="section-title fade-up delay-2">
            <h2>Our Menu</h2>
            <div class="underline"></div>
        </div>

        <div id="food-container" class="food-grid">
            <!-- Products will be loaded here -->
            <div class="loading-spinner" style="grid-column: 1/-1; text-align: center; padding: 3rem;">
                <p>Loading delicious food...</p>
            </div>
        </div>
    </main>

    <footer>
        <span class="footer-logo">Misosi Kiganjani</span>
        <p>Your favorite food delivery partner in the city.</p>
        <div class="copyright">
            &copy;
            <?php echo date('Y'); ?> Misosi Kiganjani. All rights reserved.
        </div>
    </footer>

    <script src="assets/js/main.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', async () => {
            await loadProducts();
        });

        async function loadProducts() {
            const container = document.getElementById('food-container');
            try {
                // We use the relative path to our public API endpoint
                const products = await apiCall('api/data.php?type=products', 'GET');

                if (products && products.length > 0) {
                    container.innerHTML = products.map(product => `
                        <div class="food-card">
                            <img src="${product.image || 'https://via.placeholder.com/400x300?text=Food'}" alt="${product.name}" class="food-image">
                            <div class="food-info">
                                <div class="food-name">${product.name}</div>
                                <div class="food-desc">${product.description || 'Tasty and delicious food prepared with fresh ingredients.'}</div>
                                <div class="food-footer">
                                    <div class="food-price">Tsh ${product.price}</div>
                                    <button class="btn-order" onclick="handleOrder()">Order Now</button>
                                </div>
                            </div>
                        </div>
                    `).join('');
                } else {
                    container.innerHTML = '<p style="grid-column: 1/-1; text-align: center;">No products available at the moment.</p>';
                }
            } catch (error) {
                console.error('Error loading products:', error);
                container.innerHTML = '<p style="grid-column: 1/-1; text-align: center; color: red;">Failed to load products. Please try again later.</p>';
            }
        }

        function handleOrder() {
            // Check if user is logged in (session check is on server, but we can check if button text is Dashboard)
            const isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;

            if (isLoggedIn) {
                window.location.href = 'dashboard_customer.php';
            } else {
                alert('Please login to place an order.');
                window.location.href = 'login.php';
            }
        }
    </script>
</body>

</html>