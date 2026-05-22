<footer style="text-align:center;padding:28px;color:var(--text-muted);font-size:0.82rem;border-top:1px solid var(--border);margin-top:40px;background:var(--surface);">
    &copy;2024 date('Y') ?> <?= APP_NAME ?> &mdash; All rights reserved
</footer>
<script>
// Auto-hide alerts after 5 seconds
document.querySelectorAll('.alert').forEach(a => {
    setTimeout(() => { a.style.transition = 'opacity .5s'; a.style.opacity = '0'; setTimeout(() => a.remove(), 500); }, 5000);
});
</script>
</body>
</html>
