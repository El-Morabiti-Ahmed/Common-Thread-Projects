const API = '../backend/api.php';
const esc = s => String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const RANK_COLORS = {iron:'text-slate-400',copper:'text-orange-400',bronze:'text-amber-600',gold:'text-yellow-300',diamond:'text-cyan-300',beast:'text-rose-400',immortal:'text-fuchsia-400'};
const RANKS = [[0,'iron'],[5,'copper'],[10,'bronze'],[20,'gold'],[35,'diamond'],[50,'beast'],[60,'immortal']];

class Api {
  static async get(resource) { return (await fetch(`${API}?resource=${resource}`)).json(); }
  static async post(action, data = {}) {
    const res = await fetch(API, {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({action, ...data})});
    const json = await res.json();
    if (!res.ok) throw new Error(json.error || 'Request failed');
    return json;
  }
}

// ---------- Games ----------
class BaseGame {
  constructor(el, onEnd) { this.el = el; this.onEnd = onEnd; }
}

class GuessGame extends BaseGame {
  start() {
    this.secret = 1 + Math.floor(Math.random() * 100);
    this.left = 7;
    this.el.innerHTML = `<p id="hint" class="mb-3">Pick a number from 1 to 100. Tries left: <b id="left">7</b></p>
      <div class="flex gap-2"><input id="guess" type="number" min="1" max="100" class="bg-slate-800 rounded px-3 py-2 w-32">
      <button id="go" class="bg-indigo-600 hover:bg-indigo-500 rounded px-4 py-2">Guess</button></div>`;
    this.el.querySelector('#go').onclick = () => this.guess();
  }
  guess() {
    const v = parseInt(this.el.querySelector('#guess').value);
    if (!v || v < 1 || v > 100) return;
    this.left--;
    this.el.querySelector('#left').textContent = this.left;
    const hint = this.el.querySelector('#hint');
    if (v === this.secret) return this.onEnd('win', `You found it: ${this.secret}`);
    if (this.left === 0) return this.onEnd('loss', `Out of tries. It was ${this.secret}`);
    hint.innerHTML = `${v} is too ${v < this.secret ? 'low' : 'high'}. Tries left: <b id="left">${this.left}</b>`;
  }
}

class TicTacToe extends BaseGame {
  static LINES = [[0,1,2],[3,4,5],[6,7,8],[0,3,6],[1,4,7],[2,5,8],[0,4,8],[2,4,6]];
  start() { this.b = Array(9).fill(''); this.done = false; this.draw(); }
  winner(m) { return TicTacToe.LINES.some(l => l.every(i => this.b[i] === m)); }
  draw(msg = '') {
    this.el.innerHTML = `<div class="grid grid-cols-3 gap-2 w-60">${this.b.map((c, i) =>
      `<button data-i="${i}" class="h-20 bg-slate-800 hover:bg-slate-700 rounded text-3xl font-bold ${c==='X'?'text-indigo-400':'text-rose-400'}">${c}</button>`).join('')}</div>
      <p class="mt-3 text-slate-400">${msg}</p>`;
    this.el.querySelectorAll('button').forEach(btn => btn.onclick = () => this.play(+btn.dataset.i));
  }
  play(i) {
    if (this.done || this.b[i]) return;
    this.b[i] = 'X';
    if (this.winner('X')) return this.end('win', 'You win!');
    if (!this.b.includes('')) return this.drawGame();
    const free = this.b.map((c, k) => c ? null : k).filter(k => k !== null);
    const pick = m => free.find(k => { this.b[k] = m; const w = this.winner(m); this.b[k] = ''; return w; });
    const move = pick('O') ?? pick('X') ?? (this.b[4] ? free[Math.floor(Math.random() * free.length)] : 4);
    this.b[move] = 'O';
    if (this.winner('O')) return this.end('loss', 'The computer wins.');
    if (!this.b.includes('')) return this.drawGame();
    this.draw();
  }
  drawGame() { this.draw('Draw, the board resets. Play again.'); this.b = Array(9).fill(''); setTimeout(() => this.draw('New round.'), 900); }
  end(result, msg) { this.done = true; this.draw(); this.onEnd(result, msg); }
}

const GAME_CLASSES = {guess: GuessGame, tictactoe: TicTacToe};

// ---------- App ----------
class App {
  constructor() { this.user = null; this.games = []; this.cats = []; this.app = document.getElementById('app'); this.nav = document.getElementById('nav'); }
  async init() {
    [this.user, this.games, this.cats] = await Promise.all([Api.get('me'), Api.get('games'), Api.get('categories')]);
    this.renderNav(); this.show('games');
  }
  renderNav() {
    const link = (v, t) => `<button data-v="${v}" class="px-3 py-1.5 rounded hover:bg-slate-800">${t}</button>`;
    this.nav.innerHTML = `<span class="font-bold text-indigo-400 mr-4 text-lg">Rankboard</span>
      ${link('games','Games')}${link('leaderboard','Leaderboard')}
      ${this.user ? link('profile','Profile') : ''}${this.user?.role === 'admin' ? link('admin','Admin') : ''}
      <span class="ml-auto flex items-center gap-2">${this.user
        ? `<span class="${RANK_COLORS[this.user.rank]} text-sm">${esc(this.user.username)} · ${this.user.rank} · ${this.user.points}p</span><button id="out" class="px-3 py-1.5 rounded bg-slate-800 hover:bg-slate-700">Log out</button>`
        : link('auth','Log in / Register')}</span>`;
    this.nav.querySelectorAll('[data-v]').forEach(b => b.onclick = () => this.show(b.dataset.v));
    const out = this.nav.querySelector('#out');
    if (out) out.onclick = async () => { await Api.post('logout'); this.user = null; this.renderNav(); this.show('games'); };
  }
  show(view) { this[`view_${view}`](); }
  catName(id) { return esc(this.cats.find(c => c.id === id)?.name ?? 'Other'); }
  card(html) { return `<div class="bg-slate-900 border border-slate-800 rounded-xl p-5">${html}</div>`; }

  view_games(filter = 0) {
    const list = this.games.filter(g => !filter || g.category_id === filter);
    this.app.innerHTML = `<h1 class="text-2xl font-bold mb-4">Games</h1>
      <div class="flex flex-wrap gap-2 mb-6"><button data-c="0" class="px-3 py-1 rounded-full ${!filter?'bg-indigo-600':'bg-slate-800'}">All</button>
      ${this.cats.map(c => `<button data-c="${c.id}" class="px-3 py-1 rounded-full ${filter===c.id?'bg-indigo-600':'bg-slate-800'}">${esc(c.name)}</button>`).join('')}</div>
      <div class="grid sm:grid-cols-2 gap-4">${list.map(g => this.card(`<span class="text-xs text-slate-400">${this.catName(g.category_id)}</span>
        <h2 class="text-lg font-semibold">${esc(g.name)}</h2><p class="text-slate-400 mb-4">${esc(g.description)}</p>
        <button data-g="${g.id}" class="bg-indigo-600 hover:bg-indigo-500 rounded px-4 py-2">Play</button>`)).join('') || '<p>No games yet.</p>'}</div>`;
    this.app.querySelectorAll('[data-c]').forEach(b => b.onclick = () => this.view_games(+b.dataset.c));
    this.app.querySelectorAll('[data-g]').forEach(b => b.onclick = () => this.view_play(+b.dataset.g));
  }
  view_play(id) {
    if (!this.user) return this.view_auth('Log in to play and earn points.');
    const g = this.games.find(x => x.id === id), Cls = GAME_CLASSES[g.key];
    if (!Cls) return this.app.innerHTML = '<p>This game has no playable code yet.</p>';
    this.app.innerHTML = `<h1 class="text-2xl font-bold mb-4">${esc(g.name)}</h1>${this.card('<div id="stage"></div><p id="result" class="mt-4 font-semibold"></p>')}`;
    const stage = this.app.querySelector('#stage'), result = this.app.querySelector('#result');
    const game = new Cls(stage, async (res, msg) => {
      try {
        this.user = await Api.post('session', {game_id: g.id, result: res});
        this.renderNav();
        result.innerHTML = `<span class="${res==='win'?'text-emerald-400':'text-rose-400'}">${esc(msg)} (${res==='win'?'+2':'-1'} points)</span>
          <button id="again" class="ml-3 bg-slate-800 hover:bg-slate-700 rounded px-3 py-1">Play again</button>`;
        result.querySelector('#again').onclick = () => { result.innerHTML = ''; game.start(); };
      } catch (e) { result.textContent = e.message; }
    });
    game.start();
  }
  async view_leaderboard() {
    const players = (await Api.get('players')).sort((a, b) => b.points - a.points);
    this.app.innerHTML = `<h1 class="text-2xl font-bold mb-4">Leaderboard</h1>` + this.card(`<div class="overflow-x-auto"><table class="w-full text-left">
      <thead class="text-slate-400"><tr><th class="py-2">#</th><th>Player</th><th>Rank</th><th>Points</th></tr></thead><tbody>
      ${players.map((p, i) => `<tr class="border-t border-slate-800"><td class="py-2">${i+1}</td><td>${esc(p.username)}</td>
        <td class="${RANK_COLORS[p.rank]}">${p.rank}</td><td>${p.points}</td></tr>`).join('')}</tbody></table></div>`);
  }
  async view_profile() {
    const sessions = (await Api.get('sessions')).filter(s => s.player_id === this.user.id).reverse();
    const next = RANKS.find(([min]) => min > this.user.points);
    const wins = sessions.filter(s => s.result === 'win').length;
    this.app.innerHTML = `<h1 class="text-2xl font-bold mb-4">${esc(this.user.username)}</h1>
      <div class="grid sm:grid-cols-3 gap-4 mb-6">
      ${this.card(`<p class="text-slate-400">Rank</p><p class="text-2xl font-bold ${RANK_COLORS[this.user.rank]}">${this.user.rank}</p>`)}
      ${this.card(`<p class="text-slate-400">Points</p><p class="text-2xl font-bold">${this.user.points}</p><p class="text-xs text-slate-500">${next ? `${next[0]-this.user.points} more for ${next[1]}` : 'Max rank reached'}</p>`)}
      ${this.card(`<p class="text-slate-400">Wins / Losses</p><p class="text-2xl font-bold">${wins} / ${sessions.length - wins}</p>`)}</div>
      ${this.card(`<h2 class="font-semibold mb-2">History</h2>${this.sessionTable(sessions)}`)}`;
  }
  sessionTable(list) {
    if (!list.length) return '<p class="text-slate-400">No sessions yet. Go play a game.</p>';
    return `<div class="overflow-x-auto"><table class="w-full text-left"><tbody>${list.slice(0, 50).map(s => `<tr class="border-t border-slate-800">
      <td class="py-2">${esc(this.games.find(g => g.id === s.game_id)?.name ?? '?')}</td>
      <td class="${s.result==='win'?'text-emerald-400':'text-rose-400'}">${s.result}</td><td>${s.points_change>0?'+':''}${s.points_change}</td>
      <td class="text-slate-500 text-sm">${new Date(s.played_at).toLocaleString()}</td></tr>`).join('')}</tbody></table></div>`;
  }
  view_auth(note = '') {
    this.app.innerHTML = `<div class="max-w-sm mx-auto">${this.card(`<h1 class="text-xl font-bold mb-1">Welcome</h1>
      <p class="text-slate-400 text-sm mb-4">${esc(note)}</p>
      <input id="u" placeholder="Username" class="w-full bg-slate-800 rounded px-3 py-2 mb-2">
      <input id="p" type="password" placeholder="Password" class="w-full bg-slate-800 rounded px-3 py-2 mb-3">
      <p id="err" class="text-rose-400 text-sm mb-2"></p>
      <div class="flex gap-2"><button id="login" class="flex-1 bg-indigo-600 hover:bg-indigo-500 rounded py-2">Log in</button>
      <button id="register" class="flex-1 bg-slate-800 hover:bg-slate-700 rounded py-2">Register</button></div>`)}</div>`;
    ['login', 'register'].forEach(action => this.app.querySelector('#' + action).onclick = async () => {
      try {
        this.user = await Api.post(action, {username: this.app.querySelector('#u').value, password: this.app.querySelector('#p').value});
        this.renderNav(); this.show('games');
      } catch (e) { this.app.querySelector('#err').textContent = e.message; }
    });
  }
  async view_admin() {
    const [sessions, players] = await Promise.all([Api.get('sessions'), Api.get('players')]);
    const inp = 'bg-slate-800 rounded px-3 py-2 w-full mb-2';
    this.app.innerHTML = `<h1 class="text-2xl font-bold mb-4">Admin</h1><div class="grid md:grid-cols-2 gap-4 mb-6">
      ${this.card(`<h2 class="font-semibold mb-2">New category</h2><input id="cn" placeholder="Name" class="${inp}"><button id="addc" class="bg-indigo-600 rounded px-4 py-2">Add</button>`)}
      ${this.card(`<h2 class="font-semibold mb-2">New game</h2><input id="gn" placeholder="Name" class="${inp}">
        <input id="gk" placeholder="Code key (guess or tictactoe)" class="${inp}"><input id="gd" placeholder="Description" class="${inp}">
        <select id="gc" class="${inp}">${this.cats.map(c => `<option value="${c.id}">${esc(c.name)}</option>`).join('')}</select>
        <button id="addg" class="bg-indigo-600 rounded px-4 py-2">Add</button>`)}</div>
      ${this.card(`<h2 class="font-semibold mb-2">All sessions (${sessions.length}) from ${players.length} players</h2>${this.sessionTable([...sessions].reverse().map(s => ({...s, player: 1})))}`)}`;
    const reload = async () => { [this.games, this.cats] = await Promise.all([Api.get('games'), Api.get('categories')]); this.view_admin(); };
    const q = id => this.app.querySelector(id).value;
    this.app.querySelector('#addc').onclick = async () => { await Api.post('add_category', {name: q('#cn')}).catch(e => alert(e.message)); reload(); };
    this.app.querySelector('#addg').onclick = async () => {
      await Api.post('add_game', {name: q('#gn'), key: q('#gk'), description: q('#gd'), category_id: q('#gc')}).catch(e => alert(e.message)); reload();
    };
  }
}
new App().init();
