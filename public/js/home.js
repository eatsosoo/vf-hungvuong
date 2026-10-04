const modelCards = document.getElementById('modelCards');
const slideButtons = document.querySelectorAll('[data-slide]');
const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
const updateSlideButtons = () => {
  slideButtons[0].disabled = modelCards.scrollLeft <= 2;
  slideButtons[1].disabled = modelCards.scrollLeft + modelCards.clientWidth >= modelCards.scrollWidth - 2;
};

slideButtons.forEach(button => button.addEventListener('click', () => {
  const card = modelCards.querySelector('.model-card');
  if (!card) return;
  const cardWidth = card.getBoundingClientRect().width;
  modelCards.scrollBy({
    left: Number(button.dataset.slide) * (cardWidth + 20),
    behavior: prefersReducedMotion.matches ? 'instant' : 'smooth',
  });
}));
modelCards.addEventListener('scroll', updateSlideButtons, { passive: true });
window.addEventListener('resize', updateSlideButtons);
updateSlideButtons();

