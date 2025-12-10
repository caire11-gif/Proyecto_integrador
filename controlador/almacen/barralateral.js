const mobileMenuBtn = document.getElementById('mobileMenuBtn');
const barraLateral = document.getElementById('barra-lateral');
        
if(mobileMenuBtn && barraLateral) {
    mobileMenuBtn.addEventListener('click', () => {
        barraLateral.classList.toggle('mobile-open');
    });
}