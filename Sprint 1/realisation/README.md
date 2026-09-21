# Board Game Hub

A small board game platform: register, play two JavaScript games, win or lose
points and climb the ranks. Built with PHP 8 (OOP + PDO), MySQL, JavaScript and CSS.

## Setup (XAMPP / WAMP / MAMP)

1. Copy the `boardgame-hub` folder into `htdocs` (or `www`).
2. Open MySQL Workbench and run `database.sql` (File > Run SQL Script).
3. Check `config/config.php`: DB user and password (XAMPP default is `root` with an empty password).
4. Open `http://localhost/boardgame-hub/` in the browser.

Requires PHP 8.0 or newer.

## Making an admin

Register a normal account, then in MySQL Workbench:

```sql
USE boardgame_hub;
UPDATE player SET role = 'admin' WHERE username = 'yourname';
```

Use `role = 'player'` to go back. The role is read from the database on every
page load, so it works immediately (no need to log out).

## Rank system

| Points | Rank     |
|-------:|----------|
| 0      | Iron     |
| 5      | Copper   |
| 10     | Bronze   |
| 20     | Gold     |
| 35     | Diamond  |
| 50     | Beast    |
| 60+    | Immortal |

Win = +2 points. Loss = -1 point (points never go below 0).
The rules live in one place: `classes/Rank.php`.

## Database tables

| Table           | Purpose                                                  |
|-----------------|----------------------------------------------------------|
| `player`        | accounts, `role` (player/admin), `points`, `player_rank` |
| `game_category` | categories used to filter the games                      |
| `game`          | games; `slug` = name of the JS file in `assets/js/games` |
| `game_session`  | one finished game: player, game, win/loss, points change |

`player_rank` is used instead of `rank` because RANK is a reserved word in MySQL 8.

## Project structure

```
boardgame-hub/
├── database.sql            schema + starter data
├── config/config.php       DB settings
├── classes/                OOP part
│   ├── Database.php        PDO connection (singleton)
│   ├── BaseModel.php       parent class of the models
│   ├── Player.php          register, login check, leaderboard
│   ├── Game.php            games CRUD
│   ├── GameCategory.php    categories CRUD
│   ├── GameSession.php     saves a session + updates points/rank (transaction)
│   ├── Rank.php            rank rules and badges
│   ├── Auth.php            login state, admin check, CSRF
│   ├── PlayToken.php       one-time token for saving a result
│   └── Helper.php          escaping, redirects, flash messages
├── includes/               bootstrap, header, footer, admin menu
├── api/save_session.php    receives the game result (JSON) from JavaScript
├── admin/                  dashboard, games, categories, players
├── assets/css/style.css    dark responsive theme
├── assets/js/
│   ├── main.js             mobile menu, delete confirmation
│   ├── play.js             sends the result to PHP, shows the result window
│   └── games/              tic-tac-toe.js, memory-match.js
└── index, games, play, profile, leaderboard, login, register, logout (.php)
```

## How a game is saved

1. `play.php` opens the game and gives the page a one-time token.
2. When the game ends, the JS calls `end('win')` or `end('loss')`.
3. `play.js` POSTs `{game_id, result, token}` to `api/save_session.php`.
4. PHP checks login + token, then `GameSession::record()` inserts the session and
   updates points and rank in one transaction.
5. PHP answers with the new points/rank and the JS shows the result.

Points are calculated on the server. The browser only reports win or loss.

## Adding a new game

1. Create `assets/js/games/my-game.js` and call `GameHub.register('my-game', (stage, end) => { ... return { start() {} }; })`.
   Look at `tic-tac-toe.js` for a small example.
2. In the admin panel, add a game with the slug `my-game`.

## Notes

- Tic Tac Toe: you are X. Draws are replayed and not saved (a session is only win or loss).
- Memory Match: find all 8 pairs before making 8 mistakes.
- Passwords use `password_hash`, all queries use PDO prepared statements, output is escaped
  with `e()`, forms have CSRF tokens.
- A player could still cheat by editing the JS in the browser. Fully preventing that would
  need the game logic to run on the server, which is out of scope for this sprint.
