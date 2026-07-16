/*
Programmer Name: Mr. Khoo Lay Bin
Program Name: /assets/js/global/choose_avatar.js
Description: shared choose avatar script for all pages with avatar choosing features (profile edit, complete profile)
First Written on: Thursday, 18-May-2026
Edited on: Tuesday, 26-May-2026
*/

const avatarFile = document.getElementById("avatar_file");
const uploadButton = document.querySelector(".avatar_file");
const avatarChoices = document.querySelectorAll("input[name='avatar_choice']");
const avatarPreview = document.getElementById("avatar_preview");

avatarFile.onchange = function () {
    if (avatarFile.files.length > 0) {
        uploadButton.classList.add("active");

        avatarChoices.forEach(function (choice) {
            choice.checked = false;
        });

        avatarPreview.src = URL.createObjectURL(avatarFile.files[0]);
    }
};

avatarChoices.forEach(function (choice) {
    choice.onchange = function () {
        uploadButton.classList.remove("active");
        avatarFile.value = "";

        avatarPreview.src = "/Implose.gg-src/" + choice.value;
    };
});