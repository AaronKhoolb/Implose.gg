/*
Programmer Name: Damian Loh Yi Feng
Program Name: /assets/js/user/game/quiz_complete.js
Description: Fires the end-of-quiz notification to the server so
            complete_quiz.php can:
              1. queue the emoji-feedback popup for the next page
              2. award the first-time achievements the run just met
                 (first question answered, first quiz clear, first
                 course clear, first boss battle)
            Exposed as window.notifyQuizComplete(quizId) so it can be
            called from quiz.js (solo) and any future results screen
            (e.g. a dedicated boss-battle finish page).
            keepalive:true means the request still finishes even if
            the user immediately clicks "Back to Course".
First Written on: Saturday, 04-Jul-2026
Edited on: Sunday, 05-Jul-2026
*/

(function () {

    window.notifyQuizComplete = function (quizId) {
        // build the form body
        const fd = new FormData();
        fd.append('quiz_id', quizId);

        // POST to the server so it can queue the feedback popup + award
        // any first-time achievements the run just satisfied
        return fetch('/Implose.gg-src/actions/user/game/quiz/complete_quiz.php', {
            method: 'POST',
            body: fd,
            keepalive: true
        })
        .then(function (response) {
            return response.json();
        })
        .then(function (data) {
            if (data && data.success == true) {
                if (data.course_completed == true) {
                    console.log('Course completed! Feedback prompt queued for next page.');
                }
            } else if (data && data.error) {
                console.error('Backend Error: ' + data.error);
            }
            return data;
        })
        .catch(function (error) {
            console.error('Network or Fetch error: ', error);
        });
    };

})();
