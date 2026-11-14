    </div><!-- End of container -->
    
    <footer class="bg-light mt-5 py-3 border-top">
        <div class="container text-center">
            <p class="mb-0">Project of 23LBTCSE01 &copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
        </div>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/script.js"></script>
    <?php if (isset($extraJs)): ?>
        <?php echo $extraJs; ?>
    <?php endif; ?>
</body>
</html> 