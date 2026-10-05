/**
 * GameHub: loads the game, and when the game ends it sends the
 * result to PHP (api/save_session.php) and shows the result window.
 *
 * How a game file works (see tic-tac-toe.js / memory-match.js):
 *
 *   GameHub.register('my-slug', (stage, end) => {
 *       // build the game inside "stage"
 *       // call end('win') or end('loss') when it is over
 *       return { start() { ...reset and begin a round... } };
 *   });
 */
(() => {
    const games = {};

    window.GameHub = {
        register(slug, factory) {
            games[slug] = factory;
        },
    };

    // DOMContentLoaded runs after the game script has registered itself
    document.addEventListener('DOMContentLoaded', () => {
        const root = document.getElementById('game-root');
        if (!root) return;

        const config = {
            gameId:   Number(root.dataset.gameId),
            slug:     root.dataset.slug,
            endpoint: root.dataset.endpoint,
            token:    root.dataset.token,
        };

        const stage     = root.querySelector('.game-stage');
        const overlay   = root.querySelector('.result-overlay');
        const titleEl   = root.querySelector('.result-title');
        const pointsEl  = root.querySelector('.result-points');
        const rankEl    = root.querySelector('.result-rank');
        const actionBtn = root.querySelector('.result-action');
        const hudPoints = document.getElementById('hud-points');
        const hudRank   = document.getElementById('hud-rank');

        const factory = games[config.slug];
        if (!factory) {
            stage.innerHTML = '<p class="muted">The game script could not be loaded.</p>';
            return;
        }

        let sending = false;
        const game = factory(stage, sendResult);

        function showOverlay(title, points, rank, buttonText, onClick, tone) {
            overlay.dataset.tone = tone;
            titleEl.textContent  = title;
            pointsEl.textContent = points;
            rankEl.textContent   = rank;
            actionBtn.textContent = buttonText;
            actionBtn.onclick    = onClick;
            overlay.hidden       = false;
            actionBtn.focus();
        }

        function playAgain() {
            overlay.hidden = true;
            game.start();
        }

        async function sendResult(result) {
            if (sending) return;
            sending = true;
            showOverlay('Saving your result...', '', '', 'Please wait', null, 'neutral');
            actionBtn.disabled = true;

            try {
                const response = await fetch(config.endpoint, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        game_id: config.gameId,
                        result:  result,
                        token:   config.token,
                    }),
                });
                const data = await response.json();

                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'Could not save the result.');
                }

                config.token = data.token;      // fresh one-time token
                updateHud(data);
                showResult(data);
            } catch (error) {
                showOverlay(
                    'Result not saved',
                    error.message,
                    '',
                    'Try again',
                    () => sendResult(result),
                    'loss'
                );
            } finally {
                sending = false;
                actionBtn.disabled = false;
            }
        }

        function updateHud(data) {
            hudPoints.textContent = data.points;
            hudRank.innerHTML = '<span class="badge rank-' + data.rank + '">' + data.rank_label + '</span>';
        }

        function showResult(data) {
            const won = data.result === 'win';
            const change = data.points_change;

            let pointsText;
            if (change > 0)      pointsText = '+' + change + ' points';
            else if (change < 0) pointsText = change + ' point';
            else                 pointsText = 'No points lost (you are already at 0)';

            let rankText = 'You are ' + data.rank_label + ' with ' + data.points + ' points.';
            if (data.rank_change === 'up')   rankText = 'Rank up! You are now ' + data.rank_label + '.';
            if (data.rank_change === 'down') rankText = 'You dropped to ' + data.rank_label + '.';

            showOverlay(
                won ? 'Victory' : 'Defeat',
                pointsText,
                rankText,
                'Play again',
                playAgain,
                won ? 'win' : 'loss'
            );
        }

        game.start();
    });
})();
