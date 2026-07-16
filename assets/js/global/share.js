/*
    Programmer Name: Mr. Khoo Lay Bin
    Program Name: /assets/js/global/share.js
    Description: share button on any page (uses native share on mobile, copies link on desktop)
    First Written on: Wednesday, 02-Jul-2026
    Edited on: Wednesday, 02-Jul-2026
*/

document.querySelectorAll(".share-btn").forEach(btn => {
    btn.addEventListener("click", () => {
        if (navigator.share) {
            navigator.share({
                title: btn.dataset.title,
                text: `${btn.dataset.title}\n${btn.dataset.description}\n\nCheck out this course on `,
                url: btn.dataset.url
            });
        } else {
            navigator.clipboard.writeText(btn.dataset.url);
            alert("Link copied!");
        }
    });
});
