document.addEventListener('DOMContentLoaded', () => {
    
    const dropdownToggle = document.querySelector('.dropdown__toggle');
    const dropdownMenu = document.querySelector('.dropdown__menu');

    
    dropdownToggle.addEventListener('click', (event) => {
        event.preventDefault(); 

        
        dropdownMenu.style.display = 
            dropdownMenu.style.display === 'block' ? 'none' : 'block';
    });

    document.addEventListener('click', (event) => {
        if (!event.target.closest('.nav__item')) {
            dropdownMenu.style.display = 'none';
        }
    });
});