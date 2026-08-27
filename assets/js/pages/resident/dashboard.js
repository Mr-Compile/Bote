import { getPageData } from '../../shared/dom.js';
function showActivityTab(tabName) {
    document.querySelectorAll('.activity-tab').forEach((tab) => {
        tab.classList.add('hidden');
    });
    document.querySelectorAll('[id^="tab-"]').forEach((btn) => {
        btn.classList.remove('bg-bote-green-600', 'text-white');
        btn.classList.add('text-bote-stone-600', 'hover:text-bote-stone-800');
    });
    const activityTab = document.getElementById(`activity-${tabName}`);
    if (activityTab) {
        activityTab.classList.remove('hidden');
    }
    const activeBtn = document.getElementById(`tab-${tabName}`);
    if (activeBtn) {
        activeBtn.classList.add('bg-bote-green-600', 'text-white');
        activeBtn.classList.remove('text-bote-stone-600', 'hover:text-bote-stone-800');
    }
}
document.addEventListener('DOMContentLoaded', () => {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
    const data = getPageData('resident-dashboard-data');
    const pointsEarned = data?.pointsEarned ?? 0;
    const pointsRedeemed = data?.pointsRedeemed ?? 0;
    const totalPoints = data?.totalPoints ?? 0;
    const chartContainer = document.getElementById('pointsChart');
    if (chartContainer) {
        const ctx = chartContainer.getContext('2d');
        if (ctx && typeof Chart !== 'undefined') {
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Points Earned', 'Points Redeemed', 'Available Balance'],
                    datasets: [{
                            data: [pointsEarned, pointsRedeemed, totalPoints],
                            backgroundColor: ['#10b981', '#3b82f6', '#f97316'],
                            borderWidth: 0,
                            hoverOffset: 15,
                        }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 25,
                                usePointStyle: true,
                                font: {
                                    size: 14,
                                    weight: '500',
                                },
                                generateLabels: (chart) => {
                                    const dataset = chart.data.datasets[0];
                                    return chart.data.labels.map((label, i) => {
                                        const value = dataset.data[i] ?? 0;
                                        const color = dataset.backgroundColor[i] ?? '#000000';
                                        return {
                                            text: `${label}: ${value.toLocaleString()}`,
                                            fillStyle: color,
                                            strokeStyle: color,
                                            lineWidth: 0,
                                            pointStyle: 'circle',
                                        };
                                    });
                                },
                            },
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            cornerRadius: 12,
                            padding: 15,
                            displayColors: false,
                            callbacks: {
                                label: (context) => {
                                    const label = context.label ?? '';
                                    const value = context.parsed ?? 0;
                                    const dataset = context.dataset;
                                    const total = dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : '0.0';
                                    return `${label}: ${value.toLocaleString()} points (${percentage}%)`;
                                },
                            },
                        },
                    },
                    cutout: '65%',
                    animation: {
                        animateScale: true,
                        animateRotate: true,
                        duration: 2000,
                        easing: 'easeInOutQuart',
                    },
                },
            });
        }
    }
    // Animate stats cards on load
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach((card, index) => {
        setTimeout(() => {
            card.style.transform = 'translateY(0)';
            card.style.opacity = '1';
        }, index * 150);
    });
    // Enhanced hover effects for action buttons
    const actionButtons = document.querySelectorAll('a[href*="resident_"]');
    actionButtons.forEach((button) => {
        button.addEventListener('mouseenter', function () {
            this.style.transform = 'translateY(-2px) scale(1.02)';
        });
        button.addEventListener('mouseleave', function () {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });
    // Progress bar animations
    const progressBars = document.querySelectorAll('.progress-bar');
    progressBars.forEach((bar) => {
        const width = bar.style.width;
        bar.style.width = '0%';
        setTimeout(() => {
            bar.style.transition = 'width 2s ease-in-out';
            bar.style.width = width;
        }, 500);
    });
    // Add ripple effect to cards
    document.querySelectorAll('.card-hover').forEach((card) => {
        card.addEventListener('click', function (e) {
            const ripple = document.createElement('span');
            ripple.classList.add('ripple');
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const event = e;
            const x = event.clientX - rect.left - size / 2;
            const y = event.clientY - rect.top - size / 2;
            ripple.style.cssText = `
                position: absolute;
                width: ${size}px;
                height: ${size}px;
                left: ${x}px;
                top: ${y}px;
                background: radial-gradient(circle, rgba(255,255,255,0.3) 0%, transparent 70%);
                border-radius: 50%;
                transform: scale(0);
                animation: ripple-animation 0.6s ease-out;
                pointer-events: none;
                z-index: 10;
            `;
            this.style.position = 'relative';
            this.style.overflow = 'hidden';
            this.appendChild(ripple);
            setTimeout(() => {
                ripple.remove();
            }, 600);
        });
    });
    // Auto-refresh data every 5 minutes
    setInterval(() => {
        fetch(window.location.href, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
        }).then((response) => {
            if (response.ok) {
                console.log('Dashboard data refreshed');
            }
        }).catch((error) => {
            console.log('Auto-refresh failed:', error);
        });
    }, 300000);
    // Keyboard shortcuts
    document.addEventListener('keydown', (e) => {
        if (e.ctrlKey || e.metaKey) {
            switch (e.key) {
                case 'n':
                    e.preventDefault();
                    window.location.href = 'resident_new_request.php';
                    break;
                case 'r':
                    e.preventDefault();
                    window.location.href = 'resident_rewards.php';
                    break;
                case 'l':
                    e.preventDefault();
                    window.location.href = 'resident_leaderboard.php';
                    break;
            }
        }
    });
});
Object.assign(window, { showActivityTab });
//# sourceMappingURL=dashboard.js.map