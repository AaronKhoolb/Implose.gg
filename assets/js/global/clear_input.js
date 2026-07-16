/*
Programmer Name: Mr. Khoo Lay Bin
Program Name: /assets/js/global/clear_input.js
Description: shared clear input btn script for all txt box, txt area, search bar, ...
First Written on: Thursday, 19-May-2026
Edited on: Tuesday, 26-May-2026
*/


document.addEventListener('input', function (event) {
    const input = event.target;
    input.parentElement.classList.toggle('has-text', input.value.length > 0);
});


document.addEventListener('click', function (event) {
    const clearBtn = event.target.closest('.clear-btn');

    if (!clearBtn) return;

    const input = document.getElementById(clearBtn.getAttribute('data-target'));

    if (!input) return;

    input.value = '';
    input.parentElement.classList.remove('has-text');
    input.focus();
});