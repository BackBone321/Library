<script>

    // Filter function for Borrow Books tab
    function filterBooks() {
        const searchValue = document.getElementById('bookSearch').value.toLowerCase();
        const showOnlyAvailable = document.getElementById('showOnlyAvailable').checked;
        const rows = document.querySelectorAll('#bookTable tbody tr');
        let visibleCount = 0;
        
        rows.forEach(row => {
            const title = row.getAttribute('data-title').toLowerCase();
            const author = row.getAttribute('data-author').toLowerCase();
            const genre = row.getAttribute('data-genre').toLowerCase();
            const status = row.getAttribute('data-status');
            
            const matchesSearch = title.includes(searchValue) || 
                                  author.includes(searchValue) || 
                                  genre.includes(searchValue);
                                  
            const matchesAvailable = !showOnlyAvailable || status === 'Available';
            
            if (matchesSearch && matchesAvailable) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
        
        // Show/hide no books message
        const noBooksMessage = document.getElementById('noBooks');
        if (visibleCount === 0) {
            noBooksMessage.classList.remove('d-none');
        } else {
            noBooksMessage.classList.add('d-none');
        }
    }
    
    // Filter function for Books Catalog tab
    function filterCatalogBooks() {
        const searchValue = document.getElementById('catalogSearch').value.toLowerCase();
        const showOnlyAvailable = document.getElementById('filterAvailable').checked;
        const rows = document.querySelectorAll('#catalogTable tbody tr');
        let visibleCount = 0;
        
        rows.forEach(row => {
            const title = row.getAttribute('data-title').toLowerCase();
            const author = row.getAttribute('data-author').toLowerCase();
            const genre = row.getAttribute('data-genre').toLowerCase();
            const status = row.getAttribute('data-status');
            
            const matchesSearch = title.includes(searchValue) || 
                                  author.includes(searchValue) || 
                                  genre.includes(searchValue);
                                  
            const matchesAvailable = !showOnlyAvailable || status === 'Available';
            
            if (matchesSearch && matchesAvailable) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
        
        // Show/hide no books message
        const noBooksMessage = document.getElementById('noCatalogBooks');
        if (visibleCount === 0) {
            noBooksMessage.classList.remove('d-none');
        } else {
            noBooksMessage.classList.add('d-none');
        }
    }
    
    // Initialize tooltips and event listeners
    document.addEventListener('DOMContentLoaded', function() {
        // Apply initial filtering for both tabs
        filterBooks();
        filterCatalogBooks();
        
        // Add event listeners for search and filter
        document.getElementById('bookSearch').addEventListener('keyup', filterBooks);
        document.getElementById('showOnlyAvailable').addEventListener('change', filterBooks);
        document.getElementById('catalogSearch').addEventListener('keyup', filterCatalogBooks);
        document.getElementById('filterAvailable').addEventListener('change', filterCatalogBooks);
        
        // Set up Bootstrap components if available
        if (typeof bootstrap !== 'undefined') {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        }
    });
</script>
