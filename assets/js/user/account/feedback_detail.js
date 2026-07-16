/*
Programmer Name: Damian Loh Yi Feng
Program Name: /assets/js/user/account/feedback_detail.js
Description: Wires up the My Feedback page: Edit + Delete + Add.
              - Edit opens a modal seeded with the row's current
                emoji + comment, saves via /actions/user/update_feedback.php
              - Delete confirms then hits /actions/user/delete_feedback.php
                and removes the card from the DOM
              - Add asks for a course + emoji + comment and reuses
                /actions/user/submit_feedback.php
            Every action reload()s the page on success so the list
            (and count) stays exactly in sync with the DB.
First Written on: Sunday, 05-Jul-2026
Edited on: Sunday, 05-Jul-2026
*/

(function () {
    const $ = (id) => document.getElementById(id);

    // helper: mark one emoji tile in a row as selected
    function selectEmoji(rowEl, rating) {
        const tiles = rowEl.querySelectorAll('.fb-emoji');
        for (let i = 0; i < tiles.length; i++) {
            if (tiles[i].dataset.rating == rating) {
                tiles[i].classList.add('selected');
            } else {
                tiles[i].classList.remove('selected');
            }
        }
    }

    /* ============ EDIT ============ */
    const editModal   = $('fbd-edit-modal');
    const editEmojis  = $('fbd-edit-emojis');
    const editComment = $('fbd-edit-comment');
    const editStatus  = $('fbd-edit-status');
    const editSave    = $('fbd-edit-save');
    let editingId     = 0;
    let editRating    = '';

    function openEdit(id, emoji, desc) {
        editingId  = id;
        editRating = emoji;

        if (desc) {
            editComment.value = desc;
        } else {
            editComment.value = '';
        }

        editStatus.textContent = '';
        selectEmoji(editEmojis, emoji);
        editModal.classList.add('is-open');
    }
    function closeEdit() {
        editModal.classList.remove('is-open');
        editingId = 0;
    }

    const editTiles = editEmojis.querySelectorAll('.fb-emoji');
    for (let i = 0; i < editTiles.length; i++) {
        const btn = editTiles[i];
        btn.addEventListener('click', function () {
            editRating = btn.dataset.rating;
            selectEmoji(editEmojis, editRating);
        });
    }
    $('fbd-edit-close').addEventListener('click', closeEdit);
    $('fbd-edit-cancel').addEventListener('click', closeEdit);
    editModal.addEventListener('click', function (e) {
        // click on the dim area outside the modal card closes it
        if (e.target == editModal) {
            closeEdit();
        }
    });

    editSave.addEventListener('click', function () {
        // guard against a click with nothing selected
        if (!editingId || !editRating) {
            return;
        }
        editSave.disabled = true;
        editStatus.textContent = 'Saving...';

        const fd = new FormData();
        fd.append('feedback_id',  editingId);
        fd.append('emoji_rating', editRating);
        fd.append('description',  editComment.value.trim());

        fetch('/Implose.gg-src/actions/user/update_feedback.php', { method: 'POST', body: fd })
            .then(function (r) {
                return r.text();
            })
            .then(function (txt) {
                if (txt == 'success') {
                    editStatus.textContent = 'Saved.';
                    setTimeout(function () {
                        window.location.reload();
                    }, 400);
                } else {
                    editSave.disabled = false;
                    if (txt) {
                        editStatus.textContent = txt;
                    } else {
                        editStatus.textContent = 'Failed to save.';
                    }
                }
            })
            .catch(function () {
                editSave.disabled = false;
                editStatus.textContent = 'Network error. Try again.';
            });
    });

    /* ============ DELETE ============ */
    function deleteFeedback(id, cardEl) {
        // simple browser confirm — matches how other pages ask for delete
        const ok = confirm('Delete this feedback? This cannot be undone.');
        if (!ok) {
            return;
        }

        const fd = new FormData();
        fd.append('feedback_id', id);

        fetch('/Implose.gg-src/actions/user/delete_feedback.php', { method: 'POST', body: fd })
            .then(function (r) {
                return r.text();
            })
            .then(function (txt) {
                if (txt == 'success') {
                    // reload so the count pill + empty state stay in sync
                    cardEl.remove();
                    window.location.reload();
                } else {
                    if (txt) {
                        alert(txt);
                    } else {
                        alert('Failed to delete.');
                    }
                }
            })
            .catch(function () {
                alert('Network error. Try again.');
            });
    }

    /* ============ WIRE UP EACH LIST ITEM ============ */
    const cards = document.querySelectorAll('.fbd-item');
    for (let i = 0; i < cards.length; i++) {
        const card  = cards[i];
        const id    = parseInt(card.dataset.id, 10);
        const emoji = card.dataset.emoji;

        let desc = card.dataset.desc;
        if (!desc) {
            desc = '';
        }

        // wrap in a closure so the id/emoji/desc for THIS card don't get
        // overwritten by the next loop iteration
        (function (cid, ce, cd, cel) {
            card.querySelector('.fbd-edit-btn').addEventListener('click', function () {
                openEdit(cid, ce, cd);
            });
            card.querySelector('.fbd-delete-btn').addEventListener('click', function () {
                deleteFeedback(cid, cel);
            });
        })(id, emoji, desc, card);
    }

    /* ============ ADD ============ */
    const addModal   = $('fbd-add-modal');
    const addEmojis  = $('fbd-add-emojis');
    const addComment = $('fbd-add-comment');
    const addCourse  = $('fbd-add-course');
    const addStatus  = $('fbd-add-status');
    const addSave    = $('fbd-add-save');
    let addRating    = '';

    $('fbd-add-open').addEventListener('click', function () {
        // reset the form each time the modal opens
        addRating = '';
        addSave.disabled = true;
        addStatus.textContent = '';
        addComment.value = '';
        selectEmoji(addEmojis, null);
        addModal.classList.add('is-open');
    });

    function closeAdd() {
        addModal.classList.remove('is-open');
    }

    $('fbd-add-close').addEventListener('click', closeAdd);
    $('fbd-add-cancel').addEventListener('click', closeAdd);
    addModal.addEventListener('click', function (e) {
        if (e.target == addModal) {
            closeAdd();
        }
    });

    // helper — Submit button lights up only when both fields are set
    function refreshAddSaveState() {
        if (addRating && addCourse.value) {
            addSave.disabled = false;
        } else {
            addSave.disabled = true;
        }
    }

    const addTiles = addEmojis.querySelectorAll('.fb-emoji');
    for (let i = 0; i < addTiles.length; i++) {
        const btn = addTiles[i];
        btn.addEventListener('click', function () {
            addRating = btn.dataset.rating;
            selectEmoji(addEmojis, addRating);
            refreshAddSaveState();
        });
    }
    addCourse.addEventListener('change', function () {
        refreshAddSaveState();
    });

    addSave.addEventListener('click', function () {
        if (!addRating || !addCourse.value) {
            return;
        }
        addSave.disabled = true;
        addStatus.textContent = 'Saving...';

        const fd = new FormData();
        fd.append('emoji_rating', addRating);
        fd.append('description',  addComment.value.trim());
        fd.append('course_id',    addCourse.value);

        fetch('/Implose.gg-src/actions/user/submit_feedback.php', { method: 'POST', body: fd })
            .then(function (r) {
                return r.text();
            })
            .then(function (txt) {
                if (txt == 'success') {
                    addStatus.textContent = 'Thanks for your feedback!';
                    setTimeout(function () {
                        window.location.reload();
                    }, 500);
                } else {
                    addSave.disabled = false;
                    if (txt) {
                        addStatus.textContent = txt;
                    } else {
                        addStatus.textContent = 'Failed to save.';
                    }
                }
            })
            .catch(function () {
                addSave.disabled = false;
                addStatus.textContent = 'Network error. Try again.';
            });
    });
})();
