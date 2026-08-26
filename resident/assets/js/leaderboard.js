
    // Initialize Lucide icons
    lucide.createIcons();
    
    function showTab(tab) {
        // Hide all tabs
        document.getElementById('individualLeaderboard').classList.add('hidden');
        document.getElementById('purokLeaderboard').classList.add('hidden');
        
        // Remove active class from all tab buttons
        document.getElementById('individualTab').classList.remove('bg-gradient-to-r', 'from-green-500', 'to-emerald-600', 'text-white');
        document.getElementById('purokTab').classList.remove('bg-gradient-to-r', 'from-green-500', 'to-emerald-600', 'text-white');
        document.getElementById('individualTab').classList.add('text-gray-600', 'hover:text-gray-800');
        document.getElementById('purokTab').classList.add('text-gray-600', 'hover:text-gray-800');
        
        // Show selected tab and activate button
        if (tab === 'individual') {
            document.getElementById('individualLeaderboard').classList.remove('hidden');
            document.getElementById('individualTab').classList.add('bg-gradient-to-r', 'from-green-500', 'to-emerald-600', 'text-white');
            document.getElementById('individualTab').classList.remove('text-gray-600', 'hover:text-gray-800');
        } else {
            document.getElementById('purokLeaderboard').classList.remove('hidden');
            document.getElementById('purokTab').classList.add('bg-gradient-to-r', 'from-green-500', 'to-emerald-600', 'text-white');
            document.getElementById('purokTab').classList.remove('text-gray-600', 'hover:text-gray-800');
        }
    }

    // Initialize with individual tab active
    document.addEventListener('DOMContentLoaded', function() {
        showTab('individual');
    });
