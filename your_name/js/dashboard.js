// --- DRAGGABLE ENGINE ---

let topZ = 5000;

function makeDraggable(element, handle = null) {
    let pos1 = 0, pos2 = 0, pos3 = 0, pos4 = 0;
    const dragHandle = handle || element;

    dragHandle.onmousedown = dragMouseDown;

    function dragMouseDown(e) {
        // Bring to front on click
        topZ++;
        element.style.zIndex = topZ;

        e = e || window.event;
        // Huwag hihila kung button o input ang pinindot
        if (['BUTTON', 'INPUT', 'TEXTAREA'].includes(e.target.tagName) || e.target.closest('button')) return;
        
        e.preventDefault();
        // Get mouse position sa simula
        pos3 = e.clientX;
        pos4 = e.clientY;
        
        document.onmouseup = closeDragElement;
        document.onmousemove = elementDrag;
        
        // Visual feedback
        element.style.transition = "none"; 
    }

    function elementDrag(e) {
        e = e || window.event;
        e.preventDefault();
        // Calculate new position
        pos1 = pos3 - e.clientX;
        pos2 = pos4 - e.clientY;
        pos3 = e.clientX;
        pos4 = e.clientY;
        
        // Set element's new position
        let newTop = element.offsetTop - pos2;
        let newLeft = element.offsetLeft - pos1;

        // Boundary check (huwag palabasin sa top bar)
        if (newTop < 50) newTop = 50; 
        
        element.style.top = newTop + "px";
        element.style.left = newLeft + "px";
    }

    function closeDragElement() {
        document.onmouseup = null;
        document.onmousemove = null;
        
        // Kung ID Card ang hinihila, ibalik sa pwesto (Bounce back effect)
        if (element.id === "id-card") {
            element.style.transition = "all 0.6s cubic-bezier(0.34, 1.56, 0.64, 1)";
            element.style.right = "50px";
            element.style.top = "70px";
            element.style.left = "auto"; 
        }
    }
}

// --- INITIALIZE & OBSERVER ---

window.addEventListener('DOMContentLoaded', () => {
    // 1. Gawing draggable ang static elements
    const idCard = document.getElementById('id-card');
    if (idCard) makeDraggable(idCard);

    const mainWindow = document.querySelector('.win31-window')?.parentElement;
    const titleBar = document.querySelector('.win31-titlebar');
    if (mainWindow && titleBar) makeDraggable(mainWindow, titleBar);

    // 2. MutationObserver para sa Dynamic Windows (Alpine.js templates)
    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                // Check if the added node is a window or contains a window
                if (node.nodeType === 1) {
                    const win = node.classList.contains('win31-window') ? node : node.querySelector('.win31-window');
                    if (win) {
                        const handle = win.querySelector('.win31-titlebar');
                        // Apply draggable to the window container
                        makeDraggable(win, handle);
                        // Auto-focus on appear
                        topZ++;
                        win.style.zIndex = topZ;
                    }
                }
            });
        });
    });

    observer.observe(document.body, { childList: true, subtree: true });
});