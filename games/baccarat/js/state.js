import { CONFIG } from './config.js';
import { shuffleDeck } from './utils.js';

const listeners = new Map();

const initialState = () => ({
    roundId: 1,
    timer: CONFIG.round.initialTimer,
    phase: 'betting', // betting | dealing | settling
    chips: CONFIG.tableLimits.chips,
    selectedChip: CONFIG.tableLimits.defaultChip,
    bets: {
        banker: 0,
        player: 0,
        tie: 0
    },
    totalBet: 0,
    deck: shuffleDeck(CONFIG.deck),
    hands: {
        banker: [],
        player: []
    },
    result: null,
    balance: window.BACCARAT_USER?.balance ?? 0,
    history: [],
    roadmap: {
        bead: [],
        big: [],
        bigEye: [],
        small: [],
        roach: [],
        summary: {
            banker: 0,
            player: 0,
            tie: 0
        }
    },
    previousBets: null,
    audio: {
        music: false,
        sfx: true
    }
});

let state = initialState();

function notify(key) {
    if (!listeners.has(key)) return;
    listeners.get(key).forEach(cb => cb(state[key], state));
}

export const GameState = {
    on(key, callback) {
        const arr = listeners.get(key) ?? [];
        arr.push(callback);
        listeners.set(key, arr);
    },
    get(key) {
        return key ? state[key] : state;
    },
    set(key, value) {
        state[key] = value;
        notify(key);
    },
    merge(partial) {
        state = { ...state, ...partial };
        Object.keys(partial).forEach(notify);
    },
    resetRound(nextRoundId) {
        state.phase = 'betting';
        state.timer = CONFIG.round.initialTimer;
        state.roundId = nextRoundId ?? state.roundId + 1;
        state.hands = { banker: [], player: [] };
        state.result = null;
        state.bets = { banker: 0, player: 0, tie: 0 };
        state.totalBet = 0;
        state.previousBets = null;
        notify('phase');
        notify('timer');
        notify('bets');
        notify('totalBet');
    },
    placeBet(side, amount) {
        if (state.phase !== 'betting') return false;
        const { minBet, maxBet } = CONFIG.tableLimits;
        amount = Math.max(minBet, Math.min(amount, maxBet));

        if (state.balance - amount < 0) return false;

        state.bets[side] += amount;
        state.totalBet += amount;
        state.balance -= amount;
        notify('bets');
        notify('totalBet');
        notify('balance');
        return true;
    },
    clearBets() {
        if (state.phase !== 'betting') return;
        state.balance += state.totalBet;
        state.bets = { banker: 0, player: 0, tie: 0 };
        state.totalBet = 0;
        notify('bets');
        notify('totalBet');
        notify('balance');
    },
    savePreviousBets() {
        state.previousBets = { ...state.bets };
    },
    rebet() {
        if (!state.previousBets) return false;
        this.clearBets();
        let success = true;
        Object.entries(state.previousBets).forEach(([side, amount]) => {
            if (amount > 0) {
                const placed = this.placeBet(side, amount);
                if (!placed) success = false;
            }
        });
        return success;
    },
    doubleBet() {
        if (state.phase !== 'betting') return false;
        const doubled = {};
        let required = 0;
        Object.entries(state.bets).forEach(([side, amount]) => {
            doubled[side] = amount * 2;
            required += amount;
        });
        if (required > state.balance) return false;
        Object.entries(state.bets).forEach(([side, amount]) => {
            if (amount > 0) {
                this.placeBet(side, amount);
            }
        });
        return true;
    },
    updateBalance(delta) {
        state.balance += delta;
        notify('balance');
    },
    pushHistoryItem(entry) {
        state.history.unshift(entry);
        if (state.history.length > CONFIG.history.maxClientRecords) {
            state.history.pop();
        }
        notify('history');
        return entry;
    }
};
