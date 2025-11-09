import { CONFIG } from './config.js';
import { GameState } from './state.js';
import { dealRound, calculatePayouts } from './baccarat.js';
import { createRoadmapState, updateRoadmaps } from './roadmap.js';
import { toast } from './utils.js';
import { toggleMusic, toggleSFX, playSFX } from './audio.js';
import { initRenderer, updateBalance, updateBets, updateTimer, updateRound, renderCards, showResult, hideResult, pushHistoryItem } from './renderer.js';

const controls = {
    clear: document.getElementById('btn-clear'),
    rebet: document.getElementById('btn-rebet'),
    double: document.getElementById('btn-double'),
    deal: document.getElementById('btn-deal'),
    chips: Array.from(document.querySelectorAll('.chip')),
    betZones: Array.from(document.querySelectorAll('.bet-zone')),
    toggleMusic: document.getElementById('toggle-music'),
    toggleSfx: document.getElementById('toggle-sfx'),
    tabs: Array.from(document.querySelectorAll('.history-tabs .tab'))
};

const boardCanvas = document.getElementById('board-canvas');
const boardCtx = boardCanvas ? boardCanvas.getContext('2d') : null;
const summaryPanel = document.getElementById('summary-stats');
const summaryCounters = {
    banker: document.getElementById('stat-banker'),
    player: document.getElementById('stat-player'),
    tie: document.getElementById('stat-tie')
};

let currentBoard = 'bead';
let timerInterval = null;
let roadmapState = createRoadmapState();

function selectChip(value) {
    controls.chips.forEach(chip => chip.classList.toggle('active', Number(chip.dataset.value) === value));
    GameState.set('selectedChip', value);
}

function startTimer() {
    clearInterval(timerInterval);
    let time = CONFIG.round.initialTimer;
    GameState.set('timer', time);
    updateTimer(time);
    timerInterval = setInterval(() => {
        time -= 1;
        GameState.set('timer', time);
        updateTimer(time);
        if (time <= 0) {
            clearInterval(timerInterval);
            controls.deal.click();
        }
    }, 1000);
}

function colorForSymbol(symbol) {
    switch (symbol) {
        case 'B': return '#ef4444';
        case 'P': return '#3b82f6';
        case 'T': return '#22c55e';
        default: return '#94a3b8';
    }
}

function drawBeadRoad() {
    if (!boardCtx) return;
    const rows = 6;
    const cols = Math.max(12, Math.ceil(roadmapState.bead.length / rows));
    const cellW = boardCanvas.width / cols;
    const cellH = boardCanvas.height / rows;
    boardCtx.clearRect(0, 0, boardCanvas.width, boardCanvas.height);
    roadmapState.bead.forEach((symbol, idx) => {
        const col = Math.floor(idx / rows);
        const row = idx % rows;
        const x = col * cellW + cellW / 2;
        const y = row * cellH + cellH / 2;
        const radius = Math.min(cellW, cellH) / 2 - 4;
        boardCtx.fillStyle = colorForSymbol(symbol);
        boardCtx.beginPath();
        boardCtx.arc(x, y, radius, 0, Math.PI * 2);
        boardCtx.fill();
        if (symbol === 'T') {
            boardCtx.strokeStyle = '#fff';
            boardCtx.lineWidth = 2;
            boardCtx.beginPath();
            boardCtx.moveTo(x - radius / 1.5, y - radius / 1.5);
            boardCtx.lineTo(x + radius / 1.5, y + radius / 1.5);
            boardCtx.stroke();
        }
    });
}

function drawBigRoad() {
    if (!boardCtx || roadmapState.big.length === 0) {
        boardCtx?.clearRect(0, 0, boardCanvas.width, boardCanvas.height);
        return;
    }
    const columns = Math.max(...roadmapState.big.map(c => c.column)) + 1;
    const rows = Math.max(6, Math.max(...roadmapState.big.map(c => c.row)) + 1);
    const cellW = boardCanvas.width / Math.max(columns, 12);
    const cellH = boardCanvas.height / Math.max(rows, 6);
    boardCtx.clearRect(0, 0, boardCanvas.width, boardCanvas.height);
    roadmapState.big.forEach(item => {
        const x = item.column * cellW + cellW / 2;
        const y = item.row * cellH + cellH / 2;
        const radius = Math.min(cellW, cellH) / 2 - 4;
        boardCtx.fillStyle = colorForSymbol(item.symbol);
        boardCtx.beginPath();
        boardCtx.arc(x, y, radius, 0, Math.PI * 2);
        boardCtx.fill();
        if (item.ties) {
            boardCtx.fillStyle = '#22c55e';
            boardCtx.beginPath();
            boardCtx.arc(x, y, radius / 2, 0, Math.PI * 2);
            boardCtx.fill();
        }
    });
}

function drawDerivedRoad(data, type) {
    if (!boardCtx) return;
    const rows = 6;
    const cols = Math.max(12, Math.ceil(data.length / rows));
    const cellW = boardCanvas.width / cols;
    const cellH = boardCanvas.height / rows;
    boardCtx.clearRect(0, 0, boardCanvas.width, boardCanvas.height);

    data.forEach((symbol, idx) => {
        const col = Math.floor(idx / rows);
        const row = idx % rows;
        const x = col * cellW + cellW / 2;
        const y = row * cellH + cellH / 2;
        const color = symbol === 'R' ? '#f87171' : '#60a5fa';
        switch (type) {
            case 'circle':
                boardCtx.fillStyle = color;
                boardCtx.beginPath();
                boardCtx.arc(x, y, Math.min(cellW, cellH) / 2 - 4, 0, Math.PI * 2);
                boardCtx.fill();
                break;
            case 'slash':
                boardCtx.strokeStyle = color;
                boardCtx.lineWidth = 4;
                boardCtx.beginPath();
                if (symbol === 'R') {
                    boardCtx.moveTo(x - cellW / 3, y - cellH / 3);
                    boardCtx.lineTo(x + cellW / 3, y + cellH / 3);
                } else {
                    boardCtx.moveTo(x + cellW / 3, y - cellH / 3);
                    boardCtx.lineTo(x - cellW / 3, y + cellH / 3);
                }
                boardCtx.stroke();
                break;
            default:
                break;
        }
    });
}

function renderBoard() {
    if (!boardCanvas) return;
    if (currentBoard === 'summary') {
        boardCanvas.classList.add('hidden');
        summaryPanel.classList.remove('hidden');
        return;
    }
    summaryPanel.classList.add('hidden');
    boardCanvas.classList.remove('hidden');

    switch (currentBoard) {
        case 'bead':
            drawBeadRoad();
            break;
        case 'big':
            drawBigRoad();
            break;
        case 'big-eye':
            drawDerivedRoad(roadmapState.bigEye, 'circle');
            break;
        case 'small':
            drawDerivedRoad(roadmapState.small, 'circle');
            break;
        case 'roach':
            drawDerivedRoad(roadmapState.roach, 'slash');
            break;
        default:
            boardCtx.clearRect(0, 0, boardCanvas.width, boardCanvas.height);
            boardCtx.fillStyle = 'rgba(255,255,255,0.25)';
            boardCtx.font = '16px Poppins';
            boardCtx.textAlign = 'center';
            boardCtx.fillText('Board in progress', boardCanvas.width / 2, boardCanvas.height / 2);
            break;
    }
}

function updateSummaryCounters() {
    if (!summaryCounters.banker) return;
    summaryCounters.banker.textContent = roadmapState.summary.banker;
    summaryCounters.player.textContent = roadmapState.summary.player;
    summaryCounters.tie.textContent = roadmapState.summary.tie;
}

async function settleRound(outcome, roundId, bets) {
    roadmapState = updateRoadmaps(roadmapState, outcome);
    updateSummaryCounters();
    renderBoard();

    const { win, net } = calculatePayouts(bets, outcome.winner, CONFIG.payouts);
    GameState.updateBalance(win);
    const historyEntry = GameState.pushHistoryItem({
        roundId,
        bet: Object.entries(bets).filter(([, amt]) => amt > 0).map(([side, amt]) => `${side}: ${amt}`).join(', ') || '—',
        result: outcome.winner.toUpperCase(),
        resultColor: outcome.winner === 'banker' ? '#ef4444' : outcome.winner === 'player' ? '#3b82f6' : '#22c55e',
        net
    });
    pushHistoryItem(historyEntry);

    showResult({ winner: outcome.winner, payout: win, totals: outcome.totals });
    playSFX(outcome.winner === 'tie' ? 'tie' : 'win');

    await fetch(CONFIG.api.updatePoints, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            round: roundId,
            bets,
            outcome,
            net,
            balance: GameState.get('balance')
        })
    }).catch(() => {});

    setTimeout(() => {
        hideResult();
        GameState.resetRound();
        startTimer();
    }, 3500);
}

function handleDeal() {
    const bets = { ...GameState.get('bets') };
    if (!Object.values(bets).some(v => v > 0)) {
        toast('Place a bet before dealing.');
        return;
    }

    clearInterval(timerInterval);
    GameState.set('phase', 'dealing');
    GameState.savePreviousBets();

    const deck = GameState.get('deck');
    const outcome = dealRound(deck);
    renderCards(outcome.player, document.getElementById('player-cards'));
    renderCards(outcome.banker, document.getElementById('banker-cards'));

    settleRound(outcome, GameState.get('roundId'), bets);
}

function attachEvents() {
    controls.chips.forEach(chip => {
        chip.addEventListener('click', () => {
            selectChip(Number(chip.dataset.value));
            playSFX('chip');
        });
    });

    controls.betZones.forEach(zone => {
        zone.addEventListener('click', () => {
            const side = zone.dataset.bet;
            const amount = GameState.get('selectedChip');
            const placed = GameState.placeBet(side, amount);
            if (!placed) {
                toast('Insufficient balance or betting closed.');
            } else {
                updateBets(GameState.get('bets'));
                updateBalance(GameState.get('balance'));
                playSFX('chip');
            }
        });
    });

    controls.clear.addEventListener('click', () => {
        GameState.clearBets();
        updateBets(GameState.get('bets'));
        updateBalance(GameState.get('balance'));
    });

    controls.rebet.addEventListener('click', () => {
        const success = GameState.rebet();
        if (!success) toast('No previous bet to repeat.');
        updateBets(GameState.get('bets'));
        updateBalance(GameState.get('balance'));
    });

    controls.double.addEventListener('click', () => {
        const success = GameState.doubleBet();
        if (!success) toast('Cannot double bet.');
        updateBets(GameState.get('bets'));
        updateBalance(GameState.get('balance'));
    });

    controls.deal.addEventListener('click', handleDeal);

    controls.toggleMusic.addEventListener('click', () => {
        const enabled = toggleMusic();
        controls.toggleMusic.classList.toggle('active', enabled);
    });

    controls.toggleSfx.addEventListener('click', () => {
        const enabled = toggleSFX();
        controls.toggleSfx.classList.toggle('active', enabled);
    });

    controls.tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            controls.tabs.forEach(btn => btn.classList.toggle('active', btn === tab));
            currentBoard = tab.dataset.board;
            renderBoard();
        });
    });
}

function registerStateListeners() {
    GameState.on('balance', updateBalance);
    GameState.on('bets', updateBets);
    GameState.on('timer', updateTimer);
    GameState.on('roundId', updateRound);
}

function init() {
    initRenderer();
    registerStateListeners();
    selectChip(CONFIG.tableLimits.defaultChip);
    attachEvents();
    updateSummaryCounters();
    renderBoard();
    startTimer();
}

document.addEventListener('DOMContentLoaded', init);
