/**
 * Tic Tac Toe: you are X, the computer is O.
 * The computer always takes a win when it sees one, blocks you most
 * of the time and otherwise plays a semi-random move, so it can be beaten.
 * A draw is not saved: the round is simply replayed.
 */
GameHub.register('tic-tac-toe', (stage, end) => {
    const LINES = [
        [0, 1, 2], [3, 4, 5], [6, 7, 8],     // rows
        [0, 3, 6], [1, 4, 7], [2, 5, 8],     // columns
        [0, 4, 8], [2, 4, 6],                // diagonals
    ];

    let board = [];
    let locked = false;
    let cells = [];

    stage.innerHTML = `
        <p class="game-status" aria-live="polite"></p>
        <div class="ttt-board"></div>
        <p class="muted small center">You are X. Three in a row wins. A draw replays the round.</p>
    `;
    const statusEl = stage.querySelector('.game-status');
    const boardEl  = stage.querySelector('.ttt-board');

    // build the 9 cells once
    for (let i = 0; i < 9; i++) {
        const cell = document.createElement('button');
        cell.type = 'button';
        cell.className = 'ttt-cell';
        cell.setAttribute('aria-label', 'Cell ' + (i + 1));
        cell.addEventListener('click', () => playerMove(i));
        boardEl.appendChild(cell);
        cells.push(cell);
    }

    function start() {
        board = Array(9).fill(null);
        locked = false;
        cells.forEach((cell) => {
            cell.textContent = '';
            cell.disabled = false;
            cell.className = 'ttt-cell';
        });
        statusEl.textContent = 'Your turn';
    }

    function place(index, mark) {
        board[index] = mark;
        cells[index].textContent = mark;
        cells[index].classList.add('mark-' + mark.toLowerCase());
        cells[index].disabled = true;
    }

    function winningLine() {
        return LINES.find(([a, b, c]) => board[a] && board[a] === board[b] && board[a] === board[c]) || null;
    }

    // returns true if the round is finished
    function checkEnd() {
        const line = winningLine();

        if (line) {
            const winner = board[line[0]];
            line.forEach((i) => cells[i].classList.add('line'));
            locked = true;
            statusEl.textContent = winner === 'X' ? 'You win!' : 'The computer wins';
            setTimeout(() => end(winner === 'X' ? 'win' : 'loss'), 900);
            return true;
        }

        if (board.every((cell) => cell !== null)) {
            locked = true;
            statusEl.textContent = 'Draw. Replaying the round...';
            setTimeout(start, 1400);
            return true;
        }

        return false;
    }

    function playerMove(index) {
        if (locked || board[index] !== null) return;

        place(index, 'X');
        if (checkEnd()) return;

        locked = true;
        statusEl.textContent = 'Computer is thinking...';
        setTimeout(() => {
            place(computerChoice(), 'O');
            if (!checkEnd()) {
                locked = false;
                statusEl.textContent = 'Your turn';
            }
        }, 500);
    }

    // the empty cell that would complete a line for "mark" (or -1)
    function completingCell(mark) {
        for (const line of LINES) {
            const values = line.map((i) => board[i]);
            const owned  = values.filter((v) => v === mark).length;
            if (owned === 2 && values.includes(null)) {
                return line[values.indexOf(null)];
            }
        }
        return -1;
    }

    function pickRandom(list) {
        return list[Math.floor(Math.random() * list.length)];
    }

    function computerChoice() {
        let move = completingCell('O');                          // 1. win if possible
        if (move === -1 && Math.random() < 0.7) {
            move = completingCell('X');                          // 2. block (70% of the time)
        }
        if (move === -1) {
            const free = board.map((v, i) => (v === null ? i : null)).filter((i) => i !== null);
            const good = [4, 0, 2, 6, 8].filter((i) => board[i] === null);   // centre + corners
            move = good.length && Math.random() < 0.6 ? pickRandom(good) : pickRandom(free);
        }
        return move;
    }

    return { start };
});
