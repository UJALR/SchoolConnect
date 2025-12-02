document.addEventListener('DOMContentLoaded', function () {
    const deleteModal = document.getElementById('deleteModal');
    const deleteBackdrop = document.getElementById('deleteBackdrop');
    const openDeleteBtn = document.getElementById('openDeleteModal');
    const closeDeleteBtn = document.getElementById('deleteModalClose');
    const cancelDeleteBtn = document.getElementById('deleteCancel');

    function openDelete() {
        if (!deleteModal || !deleteBackdrop) return;
        deleteModal.style.display = 'flex';
        deleteBackdrop.style.display = 'block';
        // small delay to allow CSS transitions if present
        setTimeout(function () { deleteModal.classList.add('open'); }, 10);
        deleteBackdrop.classList.add('open');
    }

    function closeDelete() {
        if (!deleteModal || !deleteBackdrop) return;
        deleteModal.classList.remove('open');
        deleteBackdrop.classList.remove('open');
        setTimeout(function () { deleteModal.style.display = 'none'; deleteBackdrop.style.display = 'none'; }, 200);
    }

    if (openDeleteBtn) openDeleteBtn.addEventListener('click', openDelete);
    if (closeDeleteBtn) closeDeleteBtn.addEventListener('click', closeDelete);
    if (cancelDeleteBtn) cancelDeleteBtn.addEventListener('click', closeDelete);
    if (deleteBackdrop) deleteBackdrop.addEventListener('click', closeDelete);
    if (deleteModal) {
        deleteModal.addEventListener('click', function (e) {
            if (e.target === deleteModal) closeDelete();
        });
    }
});
