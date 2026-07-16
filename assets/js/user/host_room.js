/*
Program Name: /assets/js/user/host_room.js
Description : When the user picks a course, fetch quizzes for that
              course from a tiny JSON endpoint and populate the
              quiz dropdown.
First Written on: Tuesday, 30-Jun-2026
*/

(function () {
    const courseSelect = document.getElementById('course_select');
    const quizSelect   = document.getElementById('quiz_select');

    if (!courseSelect || !quizSelect) return;

    courseSelect.addEventListener('change', function () {
        const courseId = parseInt(courseSelect.value, 10);

        quizSelect.innerHTML = '<option value="">Loading quizzes...</option>';
        quizSelect.disabled  = true;

        if (!courseId) {
            quizSelect.innerHTML = '<option value="">-- Pick a course first --</option>';
            return;
        }

        fetch('/Implose.gg-src/api/game/quiz_room/list_quizzes_for_course.php?course_id=' + courseId)
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (!data || !Array.isArray(data.quizzes) || data.quizzes.length === 0) {
                    quizSelect.innerHTML = '<option value="">No quizzes available for this course</option>';
                    quizSelect.disabled  = true;
                    return;
                }

                let html = '<option value="">-- Select a quiz --</option>';
                data.quizzes.forEach(function (q) {
                    const label = q.level_number
                        ? ('LV ' + q.level_number + ' - ' + q.title)
                        : q.title;
                    html += '<option value="' + q.quiz_id + '">' + escapeHtml(label) + '</option>';
                });
                quizSelect.innerHTML = html;
                quizSelect.disabled  = false;
            })
            .catch(function () {
                quizSelect.innerHTML = '<option value="">Failed to load quizzes</option>';
                quizSelect.disabled  = true;
            });
    });

    function escapeHtml(s) {
        return String(s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
})();
