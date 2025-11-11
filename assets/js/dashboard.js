document.addEventListener('DOMContentLoaded', () => {
    const navLinks = document.querySelectorAll('.nav-links a[href^="#"]');

    navLinks.forEach((link) => {
        link.addEventListener('click', () => {
            navLinks.forEach((item) => item.classList.remove('active'));
            link.classList.add('active');
        });
    });
});
