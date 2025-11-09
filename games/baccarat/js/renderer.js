import { GameState } from './state.js';
import { CONFIG, COLORS } from './config.js';
import { formatPoints } from './utils.js';

const elements = {
    name: document.getElementById('player-name'),
    balance: document.getElementById('player-balance'),
    currentBet: document.getElementById('current-bet'),
    betBanker: document.getElementById('bet-banker'),
    betPlayer: document.getElementById('bet-player'),
    betTie: document.getElementById('bet-tie'),
    timer: document.getElementById('round-timer'),
    timerProgress: document.getElementById('timer-progress'),
    roundNumber: document.getElementById('round-number'),
    playerCards: document.getElementById('player-cards'),
    bankerCards: document.getElementById('banker-cards'),
    playerTotal: document.getElementById('player-total'),
    bankerTotal: document.getElementById('banker-total'),
    resultBanner: document.getElementById('result-banner'),
    resultText: document.getElementById('result-text'),
    resultPayout: document.getElementById('result-payout'),
    historyBody: document.getElementById('history-body')
};

export function initRenderer() {
    elements.name.textContent = window.BACCARAT_USER?.name || 'Player';
    updateBalance(GameState.get('balance'));
    updateBets(GameState.get('bets'));
    updateTimer(GameState.get('timer'));
    updateRound(GameState.get('roundId'));
}

export function updateBalance(value) {
    elements.balance.textContent = formatPoints(value);
}

export function updateBets(bets) {
    elements.betBanker.textContent = formatPoints(bets.banker);
    elements.betPlayer.textContent = formatPoints(bets.player);
    elements.betTie.textContent = formatPoints(bets.tie);
    elements.currentBet.textContent = formatPoints(bets.banker + bets.player + bets.tie);
}

export function updateTimer(seconds) {
    elements.timer.textContent = seconds.toString().padStart(2, '0');
    const percent = Math.max(0, Math.min(1, seconds / CONFIG.round.initialTimer));
    elements.timerProgress.style.width = `${percent * 100}%`;
}

export function updateRound(roundId) {
    elements.roundNumber.textContent = roundId.toString().padStart(4, '0');
}

export function renderCards(hand, container) {
    container.innerHTML = '';
    hand.forEach(card => {
        const cardEl = document.createElement('div');
        cardEl.className = 'card';
        const isRed = card.suit === '♥' || card.suit === '♦';
        cardEl.innerHTML = `
            <div class="corner" style="color:${isRed ? '#ef4444' : '#0f172a'}">${card.label}<br>${card.suit}</div>
            <div class="corner bottom" style="color:${isRed ? '#ef4444' : '#0f172a'}">${card.label}<br>${card.suit}</div>
            <div class="suit" style="color:${isRed ? '#ef4444' : '#0f172a'}">${card.suit}</div>
        `;
        container.appendChild(cardEl);
    });
}

export function showResult({ winner, payout, totals }) {
    const colors = { banker: COLORS.banker, player: COLORS.player, tie: COLORS.tie };
    elements.resultBanner.style.borderColor = colors[winner];
    elements.resultText.textContent = `${winner.charAt(0).toUpperCase() + winner.slice(1)} Wins!`;
    elements.resultPayout.textContent = payout ? `+${formatPoints(payout)}` : '+0';
    elements.resultBanner.classList.add('active');
    elements.playerTotal.textContent = totals.player;
    elements.bankerTotal.textContent = totals.banker;
}

export function hideResult() {
    elements.resultBanner.classList.remove('active');
}

export function pushHistoryItem(item) {
    const row = document.createElement('tr');
    row.innerHTML = `
        <td>#${item.roundId.toString().padStart(4, '0')}</td>
        <td>${item.bet}</td>
        <td style="color:${item.resultColor}">${item.result}</td>
        <td style="color:${item.net >= 0 ? '#22c55e' : '#ef4444'}">${item.net >= 0 ? '+' : ''}${formatPoints(item.net)}</td>
    `;
    elements.historyBody.prepend(row);
    while (elements.historyBody.children.length > CONFIG.history.maxClientRecords) {
        elements.historyBody.removeChild(elements.historyBody.lastChild);
    }
}
