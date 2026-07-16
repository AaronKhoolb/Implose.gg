/*
Programmer Name: Mr. Khoo Lay Bin
Program Name: /assets/js/user/ai_explanation.js
Description: AI explanation page js
             loading verbs
First Written on: Thursday, 02-Jul-2026
Edited on: Saturday, 04-Jul-2026
*/


const askUrl = '/Implose.gg-src/api/game/ai_explanation/ask.php';

const chatBox = document.getElementById('ai-chat');
const form    = document.getElementById('ai-form');
const input   = document.getElementById('ai-input');
const sendBtn = document.getElementById('ai-send');

const loadingVerbs = ['Accomplishing', 'Actioning', 'Actualizing', 'Architecting', 'Baking', 'Beaming', 'Beboppin\'', 'Befuddling', 'Billowing', 'Blanching', 'Bloviating', 'Boogieing', 'Boondoggling', 'Booping', 'Bootstrapping', 'Brewing', 'Burrowing', 'Calculating', 'Canoodling', 'Caramelizing', 'Cascading', 'Catapulting', 'Cerebrating', 'Channeling', 'Channelling', 'Choreographing', 'Churning', 'Clauding', 'Coalescing', 'Cogitating', 'Combobulating', 'Composing', 'Computing', 'Concocting', 'Considering', 'Contemplating', 'Cooking', 'Crafting', 'Creating', 'Crunching', 'Crystallizing', 'Cultivating', 'Deciphering', 'Deliberating', 'Determining', 'Dilly-dallying', 'Discombobulating', 'Doing', 'Doodling', 'Drizzling', 'Ebbing', 'Effecting', 'Elucidating', 'Embellishing', 'Enchanting', 'Envisioning', 'Evaporating', 'Fermenting', 'Fiddle-faddling', 'Finagling', 'Flambeing', 'Flibbertigibbeting', 'Flowing', 'Flummoxing', 'Fluttering', 'Forging', 'Forming', 'Frolicking', 'Frosting', 'Gallivanting', 'Galloping', 'Garnishing', 'Generating', 'Germinating', 'Gitifying', 'Grooving', 'Gusting', 'Harmonizing', 'Hashing', 'Hatching', 'Herding', 'Honking', 'Hullaballooing', 'Hyperspacing', 'Ideating', 'Imagining', 'Improvising', 'Incubating', 'Inferring', 'Infusing', 'Ionizing', 'Jitterbugging', 'Julienning', 'Kneading', 'Leavening', 'Levitating', 'Lollygagging', 'Manifesting', 'Marinating', 'Meandering', 'Metamorphosing', 'Misting', 'Moonwalking', 'Moseying', 'Mulling', 'Mustering', 'Musing', 'Nebulizing', 'Nesting', 'Newspapering', 'Noodling', 'Nucleating', 'Orbiting', 'Orchestrating', 'Osmosing', 'Perambulating', 'Percolating', 'Perusing', 'Philosophising', 'Photosynthesizing', 'Pollinating', 'Pondering', 'Pontificating', 'Pouncing', 'Precipitating', 'Prestidigitating', 'Processing', 'Proofing', 'Propagating', 'Puttering', 'Puzzling', 'Quantumizing', 'Razzle-dazzling', 'Razzmatazzing', 'Recombobulating', 'Reticulating', 'Roosting', 'Ruminating', 'Sauteing', 'Scampering', 'Schlepping', 'Scurrying', 'Seasoning', 'Shenaniganing', 'Shimmying', 'Simmering', 'Skedaddling', 'Sketching', 'Slithering', 'Smooshing', 'Sock-hopping', 'Spelunking', 'Spinning', 'Sprouting', 'Stewing', 'Sublimating', 'Swirling', 'Swooping', 'Symbioting', 'Synthesizing', 'Tempering', 'Thinking', 'Thundering', 'Tinkering', 'Tomfoolering', 'Topsy-turvying', 'Transfiguring', 'Transmuting', 'Twisting', 'Undulating', 'Unfurling', 'Unravelling', 'Vibing', 'Waddling', 'Wandering', 'Warping', 'Whatchamacalliting', 'Whirlpooling', 'Whirring', 'Whisking', 'Wibbling', 'Working', 'Wrangling', 'Zesting', 'Zigzagging'];

let isBusy = false;


// add an empty tutor bubble and rotate a random fun verb until the reply arrives
function addTutorBubble() {
    const bubble = document.createElement('div');
    bubble.className = 'ai-msg ai-msg-tutor';
    bubble.innerHTML =
        '<div class="ai-msg-loading">' +
            '<span class="ai-loading-verb"></span>' +

            '<span class="ai-loading-dots">' +
                '<i>.</i>' +
                '<i>.</i>' +
                '<i>.</i>' +
            '</span>' +
        '</div>' +
        
        '<div class="ai-msg-body"></div>';

    chatBox.appendChild(bubble);
    chatBox.scrollTop = chatBox.scrollHeight;

    const verbElement = bubble.querySelector('.ai-loading-verb');
    const randomIndex = Math.floor(Math.random() * loadingVerbs.length);
    verbElement.textContent = loadingVerbs[randomIndex];

    const verbTimer = setInterval(function () {
        const nextIndex = Math.floor(Math.random() * loadingVerbs.length);
        verbElement.textContent = loadingVerbs[nextIndex];
    }, 900);

    return { bubble: bubble, verbTimer: verbTimer };
}


// ask the tutor for a reply and show it when it comes back
function askTutor(mode, message) {
    isBusy = true;
    input.disabled = true;
    sendBtn.disabled = true;

    const bubbleInfo = addTutorBubble();
    const loadingElement = bubbleInfo.bubble.querySelector('.ai-msg-loading');
    const bodyElement = bubbleInfo.bubble.querySelector('.ai-msg-body');

    const body = new FormData();
    body.append('mode', mode);
    body.append('message', message || '');

    const request = new XMLHttpRequest();
    request.open('POST', askUrl);

    request.onload = function () {
        const data = JSON.parse(request.responseText);

        clearInterval(bubbleInfo.verbTimer);
        loadingElement.remove();

        if (data.error) {
            bodyElement.innerHTML = '<span class="ai-error">&#9888;&#65039; ' + data.error + '</span>';
        } else {
            bodyElement.innerHTML = data.reply_html;
        }

        chatBox.scrollTop = chatBox.scrollHeight;

        isBusy = false;
        input.disabled = false;
        sendBtn.disabled = false;
        input.focus();
    };

    request.send(body);
}


// follow-up box
form.addEventListener('submit', function (event) {
    event.preventDefault();

    if (isBusy) {
        return;
    }

    const text = input.value.trim();
    input.value = '';

    // add the user prompt message bubble
    const userBubble = document.createElement('div');
    userBubble.className = 'ai-msg ai-msg-user';
    userBubble.textContent = text;
    chatBox.appendChild(userBubble);
    chatBox.scrollTop = chatBox.scrollHeight;

    askTutor('followup', text);
});


// kick off the first explanation as soon as the page loads
askTutor('explain');
