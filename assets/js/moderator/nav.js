/*
Programmer Name: Mr. Chong Jun Yoong, Mr. Khoo Lay Bin
Program Name: /assets/js/admin/nav.js
Description: macOS-style admin sidebar behavior
            - collapse / expand toggle
            - persists collapsed state in localStorage
            - auto-collapse on narrow screens
First Written on: Monday, 27-May-2026
Edited on: Monday, 22-Jun-2026
*/

function switchMacSidebar() {
    const sidebar = document.getElementById('mac-sidebar');
    if (!sidebar) return;

    sidebar.classList.toggle('collapsed');
    document.body.classList.toggle('sidebar-collapsed');

    const isCollapsed = sidebar.classList.contains('collapsed');
    localStorage.setItem('mac-sidebar-collapsed', isCollapsed ? '1' : '0');
}

document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.getElementById('mac-sidebar');
    if (!sidebar) return;

    // Restore collapsed state
    const savedState = localStorage.getItem('mac-sidebar-collapsed');
    if (savedState === '1') {
        sidebar.classList.add('collapsed');
        document.body.classList.add('sidebar-collapsed');
    }

    // Auto-collapse on narrow screens
    if (window.innerWidth < 768) {
        sidebar.classList.add('collapsed');
        document.body.classList.add('sidebar-collapsed');
    }
});
