import { getPageData } from '../../shared/dom.js';

interface DashboardData {
    pickupOverview: {
        pending: number;
        scheduled: number;
        completed_today: number;
    };
    analyticsData: {
        labels: string[];
        points_earned: number[];
        points_redeemed: number[];
    };
}

document.addEventListener('DOMContentLoaded', () => {
    const data = getPageData<DashboardData>('admin-dashboard-data');
    if (!data) return;

    const residentBtn = document.getElementById('btn-residents');
    const purokBtn = document.getElementById('btn-puroks');
    const residentLeaderboard = document.getElementById('leaderboard-residents');
    const purokLeaderboard = document.getElementById('leaderboard-puroks');

    if (residentBtn && purokBtn && residentLeaderboard && purokLeaderboard) {
        residentBtn.addEventListener('click', () => {
            residentLeaderboard.classList.remove('hidden');
            purokLeaderboard.classList.add('hidden');
            residentBtn.classList.add('bg-bote-green-100', 'text-bote-green-700');
            residentBtn.classList.remove('bg-bote-stone-100', 'text-bote-stone-600');
            purokBtn.classList.remove('bg-bote-green-100', 'text-bote-green-700');
            purokBtn.classList.add('bg-bote-stone-100', 'text-bote-stone-600');
        });

        purokBtn.addEventListener('click', () => {
            purokLeaderboard.classList.remove('hidden');
            residentLeaderboard.classList.add('hidden');
            purokBtn.classList.add('bg-bote-green-100', 'text-bote-green-700');
            purokBtn.classList.remove('bg-bote-stone-100', 'text-bote-stone-600');
            residentBtn.classList.remove('bg-bote-green-100', 'text-bote-green-700');
            residentBtn.classList.add('bg-bote-stone-100', 'text-bote-stone-600');
        });
    }

    const pickupChart = document.getElementById('pickupChart') as HTMLCanvasElement | null;
    if (pickupChart) {
        const pickupCtx = pickupChart.getContext('2d');
        if (pickupCtx) {
            new Chart(pickupCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Pending', 'Scheduled', 'Completed Today'],
                    datasets: [{
                        data: [
                            data.pickupOverview.pending,
                            data.pickupOverview.scheduled,
                            data.pickupOverview.completed_today,
                        ],
                        backgroundColor: ['#fbbf24', '#3b82f6', '#10b981'],
                        borderWidth: 0,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' },
                    },
                },
            });
        }
    }

    const analyticsChart = document.getElementById('analyticsChart') as HTMLCanvasElement | null;
    if (analyticsChart) {
        const analyticsCtx = analyticsChart.getContext('2d');
        if (analyticsCtx) {
            new Chart(analyticsCtx, {
                type: 'line',
                data: {
                    labels: data.analyticsData.labels,
                    datasets: [
                        {
                            label: 'Points Earned',
                            data: data.analyticsData.points_earned,
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            tension: 0.4,
                            fill: true,
                        },
                        {
                            label: 'Points Redeemed',
                            data: data.analyticsData.points_redeemed,
                            borderColor: '#3b82f6',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            tension: 0.4,
                            fill: true,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'top' },
                    },
                    scales: {
                        y: { beginAtZero: true },
                    },
                },
            });
        }
    }
});

function refreshDashboard(): void {
    window.location.reload();
}

Object.assign(window, { refreshDashboard });

export {};
