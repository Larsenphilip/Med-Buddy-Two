<?php
// Determine the current year
$currentYear = date('Y');
?>
<footer class="site-footer">
    <div class="footer-container">
        <div class="footer-left">
            <span class="footer-logo">Med Buddy</span>
            <span class="footer-copyright">&copy; <?php echo $currentYear; ?> Med Buddy. All rights reserved.</span>
        </div>
        <div class="footer-right">
            <nav class="footer-nav">
                <a href="index.html">Home</a>
                <a href="appointment.php">Appointments</a>
                <a href="#">Privacy Policy</a>
                <a href="#">Terms of Service</a>
            </nav>
        </div>
    </div>
</footer>

<style>
.site-footer {
    background: #ffffff;
    padding: 1.5rem 5%;
    border-top: 1px solid #e5e7eb;
    margin-top: 4rem;
    font-family: 'Inter', sans-serif;
    width: 100%;
}

.footer-container {
    max-width: 1400px;
    margin: 0 auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1.5rem;
}

.footer-left {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.footer-logo {
    font-weight: 800;
    color: #0066CC;
    font-size: 1.1rem;
    letter-spacing: -0.5px;
}

.footer-copyright {
    color: #6b7280;
    font-size: 0.85rem;
}

.footer-nav {
    display: flex;
    gap: 2rem;
}

.footer-nav a {
    color: #6b7280;
    text-decoration: none;
    font-size: 0.85rem;
    font-weight: 500;
    transition: all 0.2s ease;
}

.footer-nav a:hover {
    color: #0066CC;
}

@media (max-width: 768px) {
    .footer-container {
        flex-direction: column;
        justify-content: center;
        text-align: center;
    }
    .footer-left {
        flex-direction: column;
        gap: 0.5rem;
    }
    .footer-nav {
        gap: 1.25rem;
        flex-wrap: wrap;
        justify-content: center;
    }
}
</style>

