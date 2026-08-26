// Leaderboard Toggle
document.getElementById('btn-residents').addEventListener('click', function () {
    document.getElementById('leaderboard-residents').classList.remove('hidden');
    document.getElementById('leaderboard-puroks').classList.add('hidden');
    this.classList.add('bg-green-100', 'text-green-700');
    document.getElementById('btn-puroks').classList.remove('bg-green-100', 'text-green-700');
    document.getElementById('btn-puroks').classList.add('bg-gray-100', 'text-gray-600');
});

document.getElementById('btn-puroks').addEventListener('click', function () {
    document.getElementById('leaderboard-puroks').classList.remove('hidden');
    document.getElementById('leaderboard-residents').classList.add('hidden');
    this.classList.add('bg-green-100', 'text-green-700');
    document.getElementById('btn-residents').classList.remove('bg-green-100', 'text-green-700');
    document.getElementById('btn-residents').classList.add('bg-gray-100', 'text-gray-600');
});

// Pickup Requests Chart
const pickupCtx = document.getElementById('pickupChart').getContext('2d');
new Chart(pickupCtx, {
    type: 'doughnut',
    data: {
        labels: ['Pending', 'Scheduled', 'Completed Today'],
        datasets: [{
            data: [
                pickupOverview.pending,
                pickupOverview.scheduled,
                pickupOverview.completed_today
            ],
            backgroundColor: ['#fbbf24', '#3b82f6', '#10b981'],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom' }
        }
    }
});

// Analytics Chart
const analyticsCtx = document.getElementById('analyticsChart').getContext('2d');
new Chart(analyticsCtx, {
    type: 'line',
    data: {
        labels: analyticsData.labels,
        datasets: [
            {
                label: 'Points Earned',
                data: analyticsData.points_earned,
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                tension: 0.4,
                fill: true
            },
            {
                label: 'Points Redeemed',
                data: analyticsData.points_redeemed,
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                tension: 0.4,
                fill: true
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'top' }
        },
        scales: {
            y: { beginAtZero: true }
        }
    }
});
