    <!-- End main-content -->
</div>

<!-- Bootstrap JS + jQuery -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>

<script>
// Auto-dismiss flash messages
setTimeout(() => {
    $('.flash-alert').fadeOut(400, function() { $(this).remove(); });
}, 4000);

// Topbar date real-time clock
function updateClock() {
    const now = new Date();
    const opts = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    // Clock update if element exists
}
</script>

<?php if (isset($extraJs)): ?>
<script><?= $extraJs ?></script>
<?php endif; ?>

</body>
</html>
