// Profile page only: highlight the clicked tab.
// NOTE: the original design only restyles the tab buttons; there are no per-tab panels yet.
(function () {
  const tabs = document.querySelectorAll('#profile-tabs .profile-tab');
  const ON = ['bg-surface-slate', 'text-accent-hyper-emerald', 'font-semibold', 'shadow-sm'];
  const OFF = ['text-on-surface-variant', 'hover:text-text-crisp', 'hover:bg-surface-slate/50'];
  tabs.forEach(tab => tab.addEventListener('click', () => {
    tabs.forEach(t => {
      t.classList.remove(...ON); t.classList.add(...OFF);
      const dot = t.querySelector('.tab-dot'); if (dot) dot.classList.add('hidden');
    });
    tab.classList.remove(...OFF); tab.classList.add(...ON);
    const dot = tab.querySelector('.tab-dot'); if (dot) dot.classList.remove('hidden');
  }));
})();
