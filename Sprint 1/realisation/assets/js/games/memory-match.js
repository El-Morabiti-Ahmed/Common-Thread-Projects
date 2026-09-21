/**
 * Memory Match: 16 cards, 8 pairs.
 * Win  = find every pair.
 * Loss = 8 wrong guesses before that.
 */
GameHub.register('memory-match', (stage, end) => {
    const SYMBOLS = ['♞', '♜', '♛', '♚', '🎲', '🃏', '🧩', '🏆'];
    const MAX_MISTAKES = 8;

    let cards = [];
    let firstIndex = null;
    let busy = false;
    let mistakes = 0;
    let pairsFound = 0;
    const buttons = [];

    stage.innerHTML = `
        <div class="mem-info">
            <span>Pairs: <strong class="mem-pairs">0</strong> / ${SYMBOLS.length}</span>
            <span>Mistakes: <strong class="mem-mistakes">0</strong> / ${MAX_MISTAKES}</span>
        </div>
        <div class="mem-grid"></div>
        <p class="muted small center">Flip two cards. Same symbol = a pair. Too many wrong guesses and you lose.</p>
    `;
    const pairsEl    = stage.querySelector('.mem-pairs');
    const mistakesEl = stage.querySelector('.mem-mistakes');
    const gridEl     = stage.querySelector('.mem-grid');

    // Fisher-Yates shuffle
    function shuffle(list) {
        const copy = list.slice();
        for (let i = copy.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [copy[i], copy[j]] = [copy[j], copy[i]];
        }
        return copy;
    }

    function start() {
        cards = shuffle([...SYMBOLS, ...SYMBOLS]);
        firstIndex = null;
        busy = false;
        mistakes = 0;
        pairsFound = 0;
        updateInfo();

        gridEl.innerHTML = '';
        buttons.length = 0;

        cards.forEach((symbol, index) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'mem-card';
            button.setAttribute('aria-label', 'Hidden card ' + (index + 1));
            button.innerHTML = `
                <span class="mem-inner">
                    <span class="mem-face mem-front">?</span>
                    <span class="mem-face mem-back">${symbol}</span>
                </span>`;
            button.addEventListener('click', () => flip(index));
            gridEl.appendChild(button);
            buttons.push(button);
        });
    }

    function updateInfo() {
        pairsEl.textContent = pairsFound;
        mistakesEl.textContent = mistakes;
    }

    function flip(index) {
        const button = buttons[index];
        if (busy || button.classList.contains('flipped') || button.classList.contains('matched')) return;

        button.classList.add('flipped');

        if (firstIndex === null) {          // first card of the attempt
            firstIndex = index;
            return;
        }

        const secondIndex = index;          // second card: compare
        const first = firstIndex;
        firstIndex = null;
        busy = true;

        if (cards[first] === cards[secondIndex]) {
            [first, secondIndex].forEach((i) => {
                buttons[i].classList.remove('flipped');
                buttons[i].classList.add('matched');
            });
            pairsFound++;
            updateInfo();
            busy = false;

            if (pairsFound === SYMBOLS.length) {
                setTimeout(() => end('win'), 700);
            }
            return;
        }

        // wrong guess: show both for a moment, then hide them
        mistakes++;
        updateInfo();
        setTimeout(() => {
            buttons[first].classList.remove('flipped');
            buttons[secondIndex].classList.remove('flipped');

            if (mistakes >= MAX_MISTAKES) {
                end('loss');                 // busy stays true so no more clicks
            } else {
                busy = false;
            }
        }, 850);
    }

    return { start };
});
