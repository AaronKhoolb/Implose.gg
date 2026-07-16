/*
Programmer Name: Mr. Khoo Lay Bin
Program Name: /assets/js/auth/verify_otp.js
Description: otp resend timer js
First Written on: Thursday, 18-May-2026
Edited on: Sunday, 24-May-2026
*/

const timer = document.getElementById('timer');
const resendBtn = document.getElementById('resend-btn');

resendBtn.addEventListener('click', function(event) {
    if (resendBtn.classList.contains('disabled')) {
        event.preventDefault();
    }
});

const countdown = setInterval(function() {
    timer.textContent = secondsLeft + "s";

    if (secondsLeft <= 0) {
        clearInterval(countdown);
        timer.textContent = "now";
        resendBtn.classList.remove('disabled');
    }

    secondsLeft--;
}, 1000);