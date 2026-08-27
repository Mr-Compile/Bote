import { getPageData } from '../../shared.js';

interface DailyEntry {
    collection_date: string;
    total_weight: number;
}

interface StatusEntry {
    status: string;
    count: number;
}

interface ReportsData {
    daily: DailyEntry[];
    status: StatusEntry[];
}

document.addEventListener('DOMContentLoaded', () => {
    lucide.createIcons();

    const data = getPageData<ReportsData>('admin-reports-data');
    if (!data) return;

    const dailyCanvas = document.getElementById('dailyChart') as HTMLCanvasElement | null;
    if (dailyCanvas) {
        const dailyCtx = dailyCanvas.getContext('2d');
        if (dailyCtx) {
            new Chart(dailyCtx, {
                type: 'line',
                data: {
                    labels: data.daily.map(d => d.collection_date),
                    datasets: [{
                        label: 'Weight (kg)',
                        data: data.daily.map(d => d.total_weight),
                        borderColor: 'rgb(34, 197, 94)',
                        backgroundColor: 'rgba(34, 197, 94, 0.1)',
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
        }
    }

    const statusCanvas = document.getElementById('statusChart') as HTMLCanvasElement | null;
    if (statusCanvas) {
        const statusCtx = statusCanvas.getContext('2d');
        if (statusCtx) {
            new Chart(statusCtx, {
                type: 'doughnut',
                data: {
                    labels: data.status.map(s => s.status.charAt(0).toUpperCase() + s.status.slice(1)),
                    datasets: [{
                        data: data.status.map(s => s.count),
                        backgroundColor: ['#fbbf24', '#3b82f6', '#f97316', '#22c55e']
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { position: 'bottom' }
                    }
                }
            });
        }
    }
});

export {};
