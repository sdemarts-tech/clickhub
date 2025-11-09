import { CONFIG } from './config.js';

export function shuffleDeck(deckConfig = CONFIG.deck) {
    const cards = [];
    for (let d = 0; d < deckConfig.decks; d += 1) {
        deckConfig.suits.forEach(suit => {
            deckConfig.ranks.forEach(rank => {
                cards.push({
                    suit,
                    label: rank.label,
                    value: rank.value
                });
            });
        });
    }
    for (let i = cards.length - 1; i > 0; i -= 1) {
        const j = Math.floor(Math.random() * (i + 1));
        [cards[i], cards[j]] = [cards[j], cards[i]];
    }
    return cards;
}

export function drawCard(deck) {
    if (!deck.length) {
        deck.push(...shuffleDeck());
    }
    return deck.pop();
}

export function formatPoints(amount) {
    return Number(amount).toLocaleString();
}

export function toast(message = '', duration = 2500) {
    const el = document.getElementById('toast');
    if (!el) return;
    el.textContent = message;
    el.classList.add('show');
    setTimeout(() => {
        el.classList.remove('show');
    }, duration);
}

export function playAudio(id) {
    const audio = document.getElementById(id);
    if (!audio) return;
    audio.currentTime = 0;
    audio.play().catch(() => {});
}
