 <script>
// Filter function for Borrow Books tab
function filterBooks() {
    const searchValue = document.getElementById('bookSearch')?.value.toLowerCase() || '';
    const showOnlyAvailable = document.getElementById('showOnlyAvailable')?.checked || false;
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

    const noBooksMessage = document.getElementById('noBooks');
    if (noBooksMessage) {
        noBooksMessage.classList.toggle('d-none', visibleCount > 0);
    }
}

// Filter function for Books Catalog tab
function filterCatalogBooks(submitForm = false) {
    const searchValue = document.getElementById('catalogSearch')?.value.toLowerCase() || '';
    const showOnlyAvailable = document.getElementById('filterAvailable')?.checked || false;
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

    const noBooksMessage = document.getElementById('noCatalogBooks');
    if (noBooksMessage) {
        noBooksMessage.classList.toggle('d-none', visibleCount > 0);
    }

    if (submitForm) {
        const form = document.querySelector('#books-tab-pane form');
        if (form) form.submit();
    }
}

// Filter function for Admin Panel
function filterAdminBooks() {
    const searchValue = document.getElementById('admin-search-input')?.value.toLowerCase() || '';
    const rows = document.querySelectorAll('#admin-books-table tr');
    let visibleCount = 0;

    rows.forEach(row => {
        const id = row.cells[0]?.textContent.toLowerCase() || '';
        const title = row.cells[1]?.textContent.toLowerCase() || '';
        const author = row.cells[2]?.textContent.toLowerCase() || '';
        const genre = row.cells[3]?.textContent.toLowerCase() || '';
        const status = row.cells[4]?.textContent.toLowerCase() || '';
        const borrower = row.cells[5]?.textContent.toLowerCase() || '';

        const matchesSearch = id.includes(searchValue) ||
                             title.includes(searchValue) ||
                             author.includes(searchValue) ||
                             genre.includes(searchValue) ||
                             status.includes(searchValue) ||
                             borrower.includes(searchValue);

        if (matchesSearch) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    // Optionally, add a "No books found" message if needed
    const noBooksMessage = document.getElementById('noAdminBooks');
    if (noBooksMessage) {
        noBooksMessage.classList.toggle('d-none', visibleCount > 0);
    }
}

// Filter function for Borrowed Books
function filterBorrowedBooks() {
    const searchValue = document.getElementById('borrowed-search-input')?.value.toLowerCase() || '';
    const rows = document.querySelectorAll('.table-responsive table tbody tr');
    let visibleCount = 0;

    rows.forEach(row => {
        const borrower = row.cells[0]?.textContent.toLowerCase() || '';
        const book = row.cells[1]?.textContent.toLowerCase() || '';
        const dateBorrowed = row.cells[2]?.textContent.toLowerCase() || '';
        const daysOut = row.cells[3]?.textContent.toLowerCase() || '';

        const matchesSearch = borrower.includes(searchValue) ||
                             book.includes(searchValue) ||
                             dateBorrowed.includes(searchValue) ||
                             daysOut.includes(searchValue);

        if (matchesSearch) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    // Optionally, add a "No borrowed books found" message if needed
    const noBooksMessage = document.getElementById('noBorrowedBooks');
    if (noBooksMessage) {
        noBooksMessage.classList.toggle('d-none', visibleCount > 0);
    }
}

// Initialize event listeners
document.addEventListener('DOMContentLoaded', function() {
    // Initialize filters
    filterBooks();
    filterCatalogBooks();
    filterAdminBooks();
    filterBorrowedBooks();

    // Event listeners for Borrow Books tab
    const bookSearch = document.getElementById('bookSearch');
    const showOnlyAvailable = document.getElementById('showOnlyAvailable');
    if (bookSearch) bookSearch.addEventListener('input', filterBooks);
    if (showOnlyAvailable) showOnlyAvailable.addEventListener('change', filterBooks);

    // Event listeners for Books Catalog tab
    const catalogSearch = document.getElementById('catalogSearch');
    const filterAvailable = document.getElementById('filterAvailable');
    if (catalogSearch) catalogSearch.addEventListener('input', () => filterCatalogBooks(false));
    if (filterAvailable) filterAvailable.addEventListener('change', () => filterCatalogBooks(true));

    // Event listener for Admin Panel search
    const adminSearch = document.getElementById('admin-search-input');
    if (adminSearch) adminSearch.addEventListener('input', filterAdminBooks);

    // Event listener for Borrowed Books search
    const borrowedSearch = document.getElementById('borrowed-search-input');
    if (borrowedSearch) borrowedSearch.addEventListener('input', filterBorrowedBooks);

    // Initialize Bootstrap tooltips
    if (typeof bootstrap !== 'undefined') {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.forEach(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
    }
});
</script>