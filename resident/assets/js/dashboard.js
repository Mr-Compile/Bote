
// Initialize all dashboard functions
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Lucide icons
    lucide.createIcons();

    // Get chart data from data attributes in the HTML
    const chartContainer = document.getElementById('pointsChart');
    const pointsEarned = parseInt(chartContainer.getAttribute('data-points-earned') || 0);
    const pointsRedeemed = parseInt(chartContainer.getAttribute('data-points-redeemed') || 0);
    const totalPoints = parseInt(chartContainer.getAttribute('data-total-points') || 0);

    // Enhanced Points Chart with 3D effect
    const ctx = chartContainer.getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Points Earned', 'Points Redeemed', 'Available Balance'],
            datasets: [{
                data: [pointsEarned, pointsRedeemed, totalPoints],
                backgroundColor: [
                    'linear-gradient(135deg, #10b981, #059669)',
                    'linear-gradient(135deg, #3b82f6, #1d4ed8)',
                    'linear-gradient(135deg, #f97316, #ea580c)'
                ],
                borderWidth: 0,
                hoverOffset: 15
            }]
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
                                weight: '500'
                            },
                            generateLabels: function(chart) {
                                const data = chart.data;
                                if (data.labels.length && data.datasets.length) {
                                    return data.labels.map((label, i) => {
                                        const dataset = data.datasets[0];
                                        const value = dataset.data[i];
                                        return {
                                            text: `${label}: ${value.toLocaleString()}`,
                                            fillStyle: dataset.backgroundColor[i],
                                            strokeStyle: dataset.backgroundColor[i],
                                            lineWidth: 0,
                                            pointStyle: 'circle'
                                        };
                                    });
                                }
                                return [];
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        cornerRadius: 12,
                        padding: 15,
                        displayColors: false,
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((value / total) * 100).toFixed(1);
                                return `${label}: ${value.toLocaleString()} points (${percentage}%)`;
                            }
                        }
                    }
                },
                cutout: '65%',
                animation: {
                    animateScale: true,
                    animateRotate: true,
                    duration: 2000,
                    easing: 'easeInOutQuart'
                }
            }
        });

        // Activity Tab Management
        function showActivityTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.activity-tab').forEach(tab => {
                tab.classList.add('hidden');
            });
            
            // Remove active state from all buttons
            document.querySelectorAll('[id^="tab-"]').forEach(btn => {
                btn.classList.remove('bg-blue-600', 'text-white');
                btn.classList.add('text-gray-600', 'hover:text-gray-800');
            });
            
            // Show selected tab
            document.getElementById(`activity-${tabName}`).classList.remove('hidden');
            
            // Add active state to selected button
            const activeBtn = document.getElementById(`tab-${tabName}`);
            activeBtn.classList.add('bg-blue-600', 'text-white');
            activeBtn.classList.remove('text-gray-600', 'hover:text-gray-800');
        }

    // Make showActivityTab function global
    window.showActivityTab = showActivityTab;
    
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
    actionButtons.forEach(button => {
        button.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px) scale(1.02)';
        });
        
        button.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });

    // Notification animations
    const notifications = document.querySelectorAll('[class*="notification-enter"]');
    notifications.forEach((notification, index) => {
        setTimeout(() => {
            notification.classList.add('notification-enter');
        }, index * 200);
    });

    // Progress bar animations
    const progressBars = document.querySelectorAll('.progress-bar');
    progressBars.forEach(bar => {
        const width = bar.style.width;
        bar.style.width = '0%';
        setTimeout(() => {
            bar.style.transition = 'width 2s ease-in-out';
            bar.style.width = width;
        }, 500);
    });

    // Add ripple effect to cards
    document.querySelectorAll('.card-hover').forEach(card => {
        card.addEventListener('click', function(e) {
            const ripple = document.createElement('span');
            ripple.classList.add('ripple');
            
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;
            
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
    setInterval(function() {
        // Check for new notifications or updates
        fetch(window.location.href, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        }).then(response => {
            if (response.ok) {
                // Could update specific sections without full page reload
                console.log('Dashboard data refreshed');
            }
        }).catch(error => {
            console.log('Auto-refresh failed:', error);
        });
    }, 300000); // 5 minutes

    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey || e.metaKey) {
            switch(e.key) {
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

    // Show keyboard shortcuts hint
    const showShortcuts = () => {
        const hint = document.createElement('div');
        hint.className = 'fixed bottom-4 right-4 bg-gray-800 text-white px-4 py-2 rounded-lg text-sm z-50 opacity-0 transition-opacity duration-300';
        hint.innerHTML = 'Shortcuts: Ctrl+N (New Request), Ctrl+R (Rewards), Ctrl+L (Leaderboard)';
        document.body.appendChild(hint);
        
        setTimeout(() => hint.style.opacity = '1', 100);
        setTimeout(() => {
            hint.style.opacity = '0';
            setTimeout(() => hint.remove(), 300);
        }, 3000);
    };

    // Show shortcuts on first visit
    if (!localStorage.getItem('dashboard_shortcuts_shown')) {
        setTimeout(showShortcuts, 2000);
        localStorage.setItem('dashboard_shortcuts_shown', 'true');
    }
    // Add CSS animations
    const style = document.createElement('style');
    style.textContent = `
        @keyframes ripple-animation {
            to { transform: scale(2); opacity: 0; }
        }
        
        .stat-card {
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.6s ease-out;
        }
        
        .notification-enter {
            animation: slideInRight 0.5s ease-out;
        }
        
        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(100%); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        .leaderboard-item:hover {
            transform: translateX(5px);
            transition: transform 0.3s ease;
        }
        
        .pulse-glow {
            animation: pulse-glow 3s ease-in-out infinite;
        }
        
        @keyframes pulse-glow {
            0%, 100% { box-shadow: 0 0 20px rgba(16, 185, 129, 0.4); }
            50% { box-shadow: 0 0 40px rgba(16, 185, 129, 0.8); }
        }
    `;
    document.head.appendChild(style);

    // Welcome animation on page load
    setTimeout(() => {
        const welcome = document.createElement('div');
        welcome.className = 'fixed top-4 right-4 bg-gradient-to-r from-green-500 to-emerald-600 text-white px-6 py-4 rounded-2xl shadow-xl z-50 transform translate-x-full transition-transform duration-500';
        welcome.innerHTML = `
            <div class="flex items-center gap-3">
                <i data-lucide="sparkles" class="w-5 h-5"></i>
                <div>
                    <div class="font-semibold">Welcome back!</div>
                    <div class="text-sm opacity-90">Ready to make a difference today?</div>
                </div>
            </div>
        `;
        document.body.appendChild(welcome);
        lucide.createIcons();
        
        setTimeout(() => welcome.style.transform = 'translateX(0)', 100);
        setTimeout(() => {
            welcome.style.transform = 'translateX(100%)';
            setTimeout(() => welcome.remove(), 500);
        }, 4000);
    }, 1000);
});
