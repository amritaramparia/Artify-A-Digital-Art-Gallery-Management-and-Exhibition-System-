document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu toggle functionality can be added here
    console.log('Artify gallery loaded');
    
    // You can add any interactive elements here
    // For example, adding click events to painting cards
    const paintingCards = document.querySelectorAll('.painting-card');
    paintingCards.forEach(card => {
        card.addEventListener('click', function() {
            // Navigate to artwork detail page
            window.location.href = 'artwork-detail.html';
        });
    });
});