
// ===================================
// TRASH MANAGEMENT FUNCTIONS
// ===================================

/**
 * Switch between All and Trash view
 */
function switchView(view) {
    currentView = view;

    // Update active tab
    document.getElementById('tab-all').classList.toggle('active', view === 'all');
    document.getElementById('tab-trash').classList.toggle('active', view === 'trash');

    // Show/hide Empty Trash button
    document.getElementById('emptyTrashBtn').style.display = view === 'trash' ? 'block' : 'none';

    // Show loading state immediately
    const container = document.getElementById('tableContainer');
    container.innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="text-muted mt-2">กำลังโหลด${view === 'trash' ? 'ถังขยะ' : 'บล็อก'}...</p>
        </div>
    `;

    // Reload table with new view
    loadBlogTable(1);
}

/**
 * Restore blog from trash
 */
async function restoreBlog(id) {
    try {
        const response = await fetch('../api/restore_blog.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ blog_id: id })
        });

        const result = await response.json();

        if (!result.success) {
            throw new Error(result.message);
        }

        Swal.fire({
            icon: 'success',
            title: 'Restored!',
            text: 'Blog has been restored successfully',
            timer: 1500,
            showConfirmButton: false
        });

        loadBlogTable(currentPage);

    } catch (error) {
        console.error('Error restoring blog:', error);
        Swal.fire('Error', error.message, 'error');
    }
}

/**
 * Delete blog permanently
 */
async function deleteBlogPermanently(id) {
    const result = await Swal.fire({
        title: 'Delete Forever?',
        html: '<strong>This action cannot be undone!</strong><br>All images will be permanently deleted.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Delete Forever',
        cancelButtonText: 'Cancel'
    });

    if (!result.isConfirmed) return;

    // Show loading
    Swal.fire({
        title: 'Deleting...',
        html: 'Removing images and data',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    try {
        const response = await fetch('../api/delete_blog_permanently.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ blog_id: id })
        });

        const apiResult = await response.json();

        if (!apiResult.success) {
            throw new Error(apiResult.message);
        }

        Swal.fire({
            icon: 'success',
            title: 'Deleted Forever',
            text: 'Blog and all images removed permanently',
            timer: 2000,
            showConfirmButton: false
        });

        loadBlogTable(currentPage);

    } catch (error) {
        console.error('Error deleting blog permanently:', error);
        Swal.fire('Error', error.message, 'error');
    }
}

/**
 * Empty entire trash
 */
async function emptyTrash() {
    // Get current trash count
    const trashCount = parseInt(document.getElementById('count-trash').textContent) || 0;

    if (trashCount === 0) {
        Swal.fire({
            icon: 'info',
            title: 'Trash is Empty',
            text: 'There are no items in trash',
            timer: 2000,
            showConfirmButton: false
        });
        return;
    }

    const result = await Swal.fire({
        title: 'Empty Trash?',
        html: `<strong>Delete ${trashCount} item(s) forever?</strong><br>This cannot be undone! All images will be removed.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Empty Trash',
        cancelButtonText: 'Cancel'
    });

    if (!result.isConfirmed) return;

    // Show loading
    Swal.fire({
        title: 'Emptying Trash...',
        html: 'Deleting all items and images',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    try {
        const response = await fetch('../api/empty_trash.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' }
        });

        const apiResult = await response.json();

        if (!apiResult.success) {
            throw new Error(apiResult.message);
        }

        Swal.fire({
            icon: 'success',
            title: 'Trash Emptied',
            text: `Deleted ${apiResult.deleted_count} blog(s) permanently`,
            timer: 2000,
            showConfirmButton: false
        });

        loadBlogTable(1);

    } catch (error) {
        console.error('Error emptying trash:', error);
        Swal.fire('Error', error.message, 'error');
    }
}
