<footer class="footer">SmartEvent © <?= date('Y') ?></footer>
<script>
document.querySelectorAll('.reveal').forEach((el) => {
    new IntersectionObserver((entries) => {
        entries.forEach((entry) => entry.isIntersecting && entry.target.classList.add('visible'));
    }, { threshold: .12 }).observe(el);
});
</script>
</body>
</html>
