/*
Programmer Name: Mr. Chong Jun Yoong, Mr. Khoo Lay Bin
Program Name: /assets/js/admin/nav.js
Description: admin sidebar toggle - collapse / expand, remembers state in localStorage, auto collapse when screen is small
First Written on: Monday, 27-May-2026
Edited on: Monday, 22-Jun-2026
*/


function switchMacSidebar() {
    const sidebar = document.getElementById('mac-sidebar');
    if (!sidebar) return;

    sidebar.classList.toggle('collapsed');
    document.body.classList.toggle('sidebar-collapsed');

    // save state so next page load will remember
    if (sidebar.classList.contains('collapsed')) {
        localStorage.setItem('mac-sidebar-collapsed', '1');
    } else {
        localStorage.setItem('mac-sidebar-collapsed', '0');
    }
}


document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.getElementById('mac-sidebar');
    if (!sidebar) return;

    // restore last state
    if (localStorage.getItem('mac-sidebar-collapsed') === '1') {
        sidebar.classList.add('collapsed');
        document.body.classList.add('sidebar-collapsed');
    }

    // auto collapse on small screens
    if (window.innerWidth < 768) {
        sidebar.classList.add('collapsed');
        document.body.classList.add('sidebar-collapsed');
    }
});
