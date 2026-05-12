<!-- ── FAB (Quick Actions) ── -->
<div class="fab-container" id="fabContainer">
    <div class="fab-actions" id="fabActions">
        <?php if (isset($_SESSION['user'])): ?>
            <?php
            $docRoot2    = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
            $projectDir2 = str_replace('\\', '/', __DIR__);
            $bp          = '/' . ltrim(str_replace($docRoot2, '', $projectDir2), '/');
            ?>
            <?php if ($_SESSION['user']['role'] === 'admin'): ?>
                <a class="fab-action" href="<?= $bp ?>/admin/events.php?new=1">
                    <span class="fab-icon">➕</span> Nouvel événement
                </a>
                <a class="fab-action" href="<?= $bp ?>/admin/reservations.php">
                    <span class="fab-icon">🎟️</span> Réservations
                </a>
                <a class="fab-action" href="<?= $bp ?>/admin/dashboard.php">
                    <span class="fab-icon">📊</span> Dashboard
                </a>
            <?php else: ?>
                <a class="fab-action" href="<?= $bp ?>/user/account.php#favorites">
                    <span class="fab-icon">⭐</span> Mes favoris
                </a>
                <a class="fab-action" href="<?= $bp ?>/user/account.php#reservations">
                    <span class="fab-icon">🎟️</span> Mes réservations
                </a>
                <a class="fab-action" href="<?= $bp ?>/index.php">
                    <span class="fab-icon">🔍</span> Explorer
                </a>
            <?php endif; ?>
        <?php else: ?>
            <a class="fab-action" href="<?= $bp ?? '' ?>/register.php">
                <span class="fab-icon">✨</span> Créer un compte
            </a>
            <a class="fab-action" href="<?= $bp ?? '' ?>/login.php">
                <span class="fab-icon">🔑</span> Se connecter
            </a>
        <?php endif; ?>
    </div>
    <button class="fab-main" id="fabMain" title="Actions rapides">+</button>
</div>

<footer class="footer">
    <div class="footer-inner">
        <div class="footer-brand"><span>Smart</span>Event</div>
        <div class="footer-links">
            <a href="#">À propos</a>
            <a href="#">Contact</a>
            <a href="#">CGU</a>
        </div>
        <span class="footer-copy">© <?= date('Y') ?> SmartEvent · Tunisie</span>
    </div>
</footer>

<script>
/* ── Theme Toggle ── */
const html    = document.documentElement;
const toggle  = document.getElementById('themeToggle');
const icons   = { dark: '🌙', light: '☀️' };

function applyTheme(t) {
  html.setAttribute('data-theme', t);
  localStorage.setItem('se-theme', t);
  if (toggle) toggle.textContent = icons[t];
}
applyTheme(localStorage.getItem('se-theme') || 'dark');

if (toggle) toggle.addEventListener('click', () => {
  applyTheme(html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark');
});

/* ── Mobile Hamburger ── */
const hamburger = document.getElementById('hamburger');
const navLinks  = document.getElementById('navLinks');
if (hamburger && navLinks) {
  hamburger.addEventListener('click', () => navLinks.classList.toggle('open'));
  document.addEventListener('click', e => {
    if (!hamburger.contains(e.target) && !navLinks.contains(e.target)) {
      navLinks.classList.remove('open');
    }
  });
}

/* ── FAB ── */
const fab    = document.getElementById('fabContainer');
const fabBtn = document.getElementById('fabMain');
if (fab && fabBtn) {
  fabBtn.addEventListener('click', () => fab.classList.toggle('open'));
  document.addEventListener('click', e => {
    if (!fab.contains(e.target)) fab.classList.remove('open');
  });
}

/* ── Toast system ── */
window.showToast = function(msg, type = 'info', duration = 3200) {
  const c = document.getElementById('toastContainer');
  if (!c) return;
  const icons = { success:'✓', error:'✕', info:'ℹ' };
  const t = document.createElement('div');
  t.className = `toast ${type}`;
  t.innerHTML = `<span>${icons[type]||'ℹ'}</span><span>${msg}</span>`;
  c.appendChild(t);
  setTimeout(() => {
    t.style.animation = 'slide-in-right .3s ease reverse both';
    setTimeout(() => t.remove(), 300);
  }, duration);
};

/* ── Reveal on scroll ── */
const observer = new IntersectionObserver((entries) => {
  entries.forEach(e => e.isIntersecting && e.target.classList.add('visible'));
}, { threshold: .1 });
document.querySelectorAll('.reveal').forEach(el => observer.observe(el));

/* ── Highlight active nav link ── */
document.querySelectorAll('.nav-links a').forEach(a => {
  if (a.href === location.href) a.style.color = 'var(--accent)';
});
</script>
</body>
</html>
