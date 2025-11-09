import { drawCard } from './utils.js';

const naturalTotal = (cards) => cards.reduce((sum, card) => (sum + card.value) % 10, 0);

function needsThirdCardPlayer(playerTotal) {
    return playerTotal <= 5;
}

function needsThirdCardBanker(bankerTotal, playerThird) {
    if (bankerTotal <= 2) return true;
    if (bankerTotal >= 7) return false;
    if (playerThird === null) return bankerTotal <= 5;

    switch (bankerTotal) {
        case 3: return playerThird.value !== 8;
        case 4: return [2, 3, 4, 5, 6, 7].includes(playerThird.value);
        case 5: return [4, 5, 6, 7].includes(playerThird.value);
        case 6: return [6, 7].includes(playerThird.value);
        default: return false;
    }
}

export function dealRound(deck) {
    const player = [drawCard(deck), drawCard(deck)];
    const banker = [drawCard(deck), drawCard(deck)];

    const playerTotal = naturalTotal(player);
    const bankerTotal = naturalTotal(banker);

    const naturals = playerTotal >= 8 || bankerTotal >= 8;
    let playerThird = null;
    let bankerThird = null;

    if (!naturals) {
        if (needsThirdCardPlayer(playerTotal)) {
            playerThird = drawCard(deck);
            player.push(playerThird);
        }
        const bankerNeeds = needsThirdCardBanker(naturalTotal(banker), playerThird);
        if (bankerNeeds) {
            bankerThird = drawCard(deck);
            banker.push(bankerThird);
        }
    }

    const finalPlayer = naturalTotal(player);
    const finalBanker = naturalTotal(banker);

    let winner = 'tie';
    if (finalPlayer > finalBanker) winner = 'player';
    else if (finalBanker > finalPlayer) winner = 'banker';

    return {
        player,
        banker,
        totals: {
            player: finalPlayer,
            banker: finalBanker
        },
        winner
    };
}

export function calculatePayouts(bets, winner, payouts) {
    let win = 0;
    if (winner === 'banker') win += bets.banker * (1 + payouts.banker);
    if (winner === 'player') win += bets.player * (1 + payouts.player);
    if (winner === 'tie') win += bets.tie * (1 + payouts.tie);

    // Push bets on tie
    if (winner === 'tie') {
        win += bets.banker + bets.player;
    }

    const totalStake = bets.banker + bets.player + bets.tie;
    const net = win - totalStake;
    return { win, net };
}
