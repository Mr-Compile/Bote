document.addEventListener('DOMContentLoaded', () => {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});

function showTab(tab: 'individual' | 'purok') {
    const individualLeaderboard = document.getElementById('individualLeaderboard');
    const purokLeaderboard = document.getElementById('purokLeaderboard');
    const individualTab = document.getElementById('individualTab');
    const purokTab = document.getElementById('purokTab');

    if (!individualLeaderboard || !purokLeaderboard || !individualTab || !purokTab) {
        return;
    }

    individualLeaderboard.classList.add('hidden');
    purokLeaderboard.classList.add('hidden');

    individualTab.classList.remove('bg-gradient-to-r', 'from-bote-green-500', 'to-bote-green-600', 'text-white');
    purokTab.classList.remove('bg-gradient-to-r', 'from-bote-green-500', 'to-bote-green-600', 'text-white');
    individualTab.classList.add('text-bote-stone-600', 'hover:text-bote-stone-800');
    purokTab.classList.add('text-bote-stone-600', 'hover:text-bote-stone-800');

    if (tab === 'individual') {
        individualLeaderboard.classList.remove('hidden');
        individualTab.classList.add('bg-gradient-to-r', 'from-bote-green-500', 'to-bote-green-600', 'text-white');
        individualTab.classList.remove('text-bote-stone-600', 'hover:text-bote-stone-800');
    } else {
        purokLeaderboard.classList.remove('hidden');
        purokTab.classList.add('bg-gradient-to-r', 'from-bote-green-500', 'to-bote-green-600', 'text-white');
        purokTab.classList.remove('text-bote-stone-600', 'hover:text-bote-stone-800');
    }
}

window.showTab = showTab;

document.addEventListener('DOMContentLoaded', () => {
    showTab('individual');
});

export {};
