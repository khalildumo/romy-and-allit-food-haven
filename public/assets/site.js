document.querySelectorAll('.category-tabs button').forEach((tab) => tab.addEventListener('click', () => {
    document.querySelector('.category-tabs .active')?.classList.remove('active');
    tab.classList.add('active');
    filterMenu();
}));

const search = document.querySelector('#menuSearch');
const menuCards = [...document.querySelectorAll('.menu-card')];
let activeCategory = 'all';

function filterMenu() {
    activeCategory = document.querySelector('.category-tabs .active')?.dataset.category || 'all';
    const query = search.value.trim().toLocaleLowerCase();
    menuCards.forEach((card) => {
        const matchesCategory = activeCategory === 'all' || card.dataset.category === activeCategory;
        const matchesSearch = card.textContent.toLocaleLowerCase().includes(query);
        card.hidden = !matchesCategory || !matchesSearch;
    });
}

search?.addEventListener('input', filterMenu);

const menuToggle = document.querySelector('#menuToggle');
menuToggle?.addEventListener('click', () => {
    const navigation = document.querySelector('.desktop-nav');
    const open = navigation.classList.toggle('open');
    menuToggle.setAttribute('aria-expanded', String(open));
});
