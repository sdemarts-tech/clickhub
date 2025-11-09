export const CONFIG = {
    version: '1.0.0',
    tableLimits: {
        minBet: 10,
        maxBet: 10000,
        defaultChip: 100,
        chips: [10, 50, 100, 500, 1000, 5000, 10000]
    },
    payouts: {
        banker: 1,
        player: 1,
        tie: 8
    },
    round: {
        initialTimer: 15,
        preDealDelay: 600,
        cardFlipDelay: 400,
        revealDelay: 900
    },
    deck: {
        decks: 6,
        suits: ['♠', '♥', '♦', '♣'],
        ranks: [
            { label: 'A', value: 1 },
            { label: '2', value: 2 },
            { label: '3', value: 3 },
            { label: '4', value: 4 },
            { label: '5', value: 5 },
            { label: '6', value: 6 },
            { label: '7', value: 7 },
            { label: '8', value: 8 },
            { label: '9', value: 9 },
            { label: '10', value: 0 },
            { label: 'J', value: 0 },
            { label: 'Q', value: 0 },
            { label: 'K', value: 0 }
        ]
    },
    history: {
        maxClientRecords: 100
    },
    api: {
        updatePoints: 'update-points.php'
    }
};

export const COLORS = {
    banker: '#ef4444',
    player: '#3b82f6',
    tie: '#22c55e'
};
