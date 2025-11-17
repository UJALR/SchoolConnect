const modal = document.getElementById("postModal");
const backdrop = document.getElementById("modalBackdrop");
const openBtn = document.getElementById("openPostModal");
const closeBtn = document.getElementById("modalClose");
const mainInput = document.getElementById("mainPostInput");
const modalText = document.getElementById("modalPostText");

// OPEN MODAL
openBtn.onclick = () => {
    modalText.value = mainInput.value;   // Autofill input
    modal.classList.add("open");
    backdrop.classList.add("open");
};

// CLOSE MODAL FUNCTION
const closeModal = () => {
    modalText.value = "";
    modal.classList.remove("open");
    backdrop.classList.remove("open");
};

// Close by X
closeBtn.onclick = () => closeModal();

// Close by clicking backdrop
backdrop.onclick = () => closeModal();

// Close if clicking *outside* modal-content
modal.addEventListener("click", (e) => {
    if (e.target === modal) closeModal();
});
