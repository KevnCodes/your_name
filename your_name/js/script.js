/**
 * YOUR NAME - Desktop Interaction Script
 * Draggable Logic for Windows, ID Cards, and Stickers
 */

let highestZIndex = 10000;

document.addEventListener('mousedown', function(e) {
    // 1. Detect target
    const draggableHeader = e.target.closest('.win31-titlebar');
    const idCard = e.target.closest('.yn-id-card');
    const sticker = e.target.closest('.yn-sticker');

    // Prevent dragging if clicking the sticker delete button
    if (e.target.closest('.sticker-delete-btn')) return;

    const target = draggableHeader || idCard || sticker;
    if (!target) return;

    // 2. Identify the actual element to move
    const elementToMove = draggableHeader ? draggableHeader.closest('.win31-window') : target;
    if (!elementToMove) return;

    // 3. Bring to Front
    highestZIndex++;
    elementToMove.style.zIndex = highestZIndex;
    
    // Deactivate other windows
    if (elementToMove.classList.contains('win31-window')) {
        document.querySelectorAll('.win31-window').forEach(win => win.style.opacity = '0.9');
        elementToMove.style.opacity = '1';
    }

    // 4. Position & Dragging State
    let rect = elementToMove.getBoundingClientRect();
    let shiftX = e.clientX - rect.left;
    let shiftY = e.clientY - rect.top;
    
    let isDragging = false;
    const startX = e.clientX;
    const startY = e.clientY;

    function moveAt(pageX, pageY) {
        let newTop = pageY - shiftY;
        // Don't let it drag above the top navbar
        if (newTop < 50) newTop = 50; 

        elementToMove.style.left = pageX - shiftX + 'px';
        elementToMove.style.top = newTop + 'px';
        elementToMove.style.right = 'auto';
        elementToMove.style.bottom = 'auto';
        elementToMove.style.position = 'absolute';
    }

    function onMouseMove(event) {
        if (!isDragging && (Math.abs(event.clientX - startX) > 5 || Math.abs(event.clientY - startY) > 5)) {
            isDragging = true;
            elementToMove.style.transition = "none"; // Remove transition while dragging
        }
        
        if (isDragging) {
            moveAt(event.pageX, event.pageY);
            if (idCard) elementToMove.classList.add('dragging-id');
        }
    }

    document.addEventListener('mousemove', onMouseMove);

    // 5. Mouse Up / Cleanup
    document.onmouseup = function() {
        document.removeEventListener('mousemove', onMouseMove);
        
        // BOUNCY RETRACTION FOR ID CARD
        if (idCard) {
            elementToMove.classList.remove('dragging-id');
            elementToMove.style.transition = "all 0.6s cubic-bezier(0.34, 1.56, 0.64, 1)";
            elementToMove.style.left = (window.innerWidth - rect.width - 50) + "px";
            elementToMove.style.top = "70px";

            setTimeout(() => {
                elementToMove.style.transition = "none";
            }, 600);
        }

        document.onmouseup = null;
    };
});

// Prevent ghosting images while dragging
document.ondragstart = function() { return false; };