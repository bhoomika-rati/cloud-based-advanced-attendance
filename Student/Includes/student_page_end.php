                <footer class="student-footer">Attendly Student Portal · <?= date('Y') ?></footer>
            </main>
        </div>
    </div>
</div>
<script src="vendor/bootstrap5/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('sidebarToggleTop').addEventListener('click', function () {
    var sidebar = document.getElementById('accordionSidebar');
    if (window.matchMedia('(max-width: 767.98px)').matches) {
        sidebar.classList.toggle('student-sidebar-expanded');
    } else {
        sidebar.classList.toggle('student-sidebar-toggled');
    }
});
</script>
</body>
</html>
