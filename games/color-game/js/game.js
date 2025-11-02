/**
 * GAME MODULE
 * Handles game state machine, betting, rolling, and payouts
 */

import { Dice } from './dice.js';
import { GAME_STATES, GAME_RULES, MESSAGES, COLOR_EMOJIS, DICE as DICE_CONFIG } from './config.js';

export class Game {
    constructor(renderer, physicsWorld) {
        this.renderer = renderer;
        this.physics = physicsWorld;
        
        this.scene = renderer.getScene();
        this.world = physicsWorld.getWorld();
        
        // Game state
        this.state = GAME_STATES.IDLE;
        this.balance = GAME_RULES.startingBalance;
        this.bets = {
            red: 0,
            yellow: 0,
            blue: 0,
            green: 0,
            white: 0,
            pink: 0
        };
        this.selectedChip = 50;
        
        // Dice
        this.dice = [];
        
        // UI elements
        this.initUI();
        
        // Bind events
        this.bindEvents();
    }
    
    /**
     * Initialize UI references
     */
    initUI() {
        this.ui = {
            balance: document.getElementById('balance'),
            phase: document.getElementById('phase'),
            result: document.getElementById('result'),
            resultDice: document.getElementById('result-dice'),
            resultOutcome: document.getElementById('result-outcome'),
            totalBet: document.getElementById('total-bet'),
            btnRoll: document.getElementById('btn-roll'),
            btnClear: document.getElementById('btn-clear'),
            btnCashout: document.getElementById('btn-cashout')
        };
        
        this.updateBalanceDisplay();
        this.updateTotalBetDisplay();
    }
    
    /**
     * Bind UI events
     */
    bindEvents() {
        // Chip selection
        document.querySelectorAll('.chip').forEach(chip => {
            chip.addEventListener('click', (e) => {
                this.selectChip(parseInt(e.target.dataset.amount));
            });
        });
        
        // Color panel betting
        document.querySelectorAll('.color-panel').forEach(panel => {
            panel.addEventListener('click', (e) => {
                const color = e.currentTarget.dataset.color;
                this.placeBet(color);
            });
        });
        
        // Control buttons
        this.ui.btnRoll.addEventListener('click', () => this.startRoll());
        this.ui.btnClear.addEventListener('click', () => this.clearAllBets());
        this.ui.btnCashout.addEventListener('click', () => this.cashOut());
    }
    
    /**
     * Select betting chip
     */
    selectChip(amount) {
        if (amount > this.balance) return;
        
        this.selectedChip = amount;
        
        // Update UI
        document.querySelectorAll('.chip').forEach(chip => {
            chip.classList.remove('selected');
            if (parseInt(chip.dataset.amount) === amount) {
                chip.classList.add('selected');
            }
        });
    }
    
    /**
     * Place bet on a color
     */
    placeBet(color) {
        if (this.state !== GAME_STATES.IDLE) return;
        if (this.selectedChip > this.balance) return;
        
        // Check limits
        const newBet = this.bets[color] + this.selectedChip;
        if (newBet > GAME_RULES.maxBetPerColor) {
            this.showMessage(`Max bet per color is ${GAME_RULES.maxBetPerColor}!`);
            return;
        }
        
        const newTotal = this.getTotalBet() + this.selectedChip;
        if (newTotal > GAME_RULES.maxTotalBet) {
            this.showMessage(`Max total bet is ${GAME_RULES.maxTotalBet}!`);
            return;
        }
        
        // Place bet
        this.bets[color] += this.selectedChip;
        this.balance -= this.selectedChip;
        
        // Update UI
        this.updateBetDisplay(color);
        this.updateBalanceDisplay();
        this.updateTotalBetDisplay();
        
        // Enable roll button
        this.ui.btnRoll.disabled = false;
    }
    
    /**
     * Clear all bets
     */
    clearAllBets() {
        if (this.state !== GAME_STATES.IDLE) return;
        
        // Refund bets
        const totalBet = this.getTotalBet();
        this.balance += totalBet;
        
        // Reset bets
        Object.keys(this.bets).forEach(color => {
            this.bets[color] = 0;
            this.updateBetDisplay(color);
        });
        
        this.updateBalanceDisplay();
        this.updateTotalBetDisplay();
        this.ui.btnRoll.disabled = true;
    }
    
    /**
     * Start dice roll
     */
    startRoll() {
        if (this.state !== GAME_STATES.IDLE) return;
        if (this.getTotalBet() === 0) {
            this.showMessage(MESSAGES.nobet);
            return;
        }
        
        // Change state
        this.state = GAME_STATES.ROLLING;
        this.updatePhase(MESSAGES.rolling);
        
        // Disable controls
        this.ui.btnRoll.disabled = true;
        this.ui.btnClear.disabled = true;
        document.querySelectorAll('.color-panel').forEach(p => p.style.pointerEvents = 'none');
        
        // Hide previous result
        this.ui.result.style.display = 'none';
        
        // Create and roll dice
        this.createDice();
        this.rollDice();
        
        // Camera shake
        this.renderer.shakeCamera(0.15, 400);
    }
    
    /**
     * Create 3 dice
     */
    createDice() {
        // Clear old dice
        this.dice.forEach(die => die.dispose());
        this.dice = [];
        
        // Create 3 new dice
        const spacing = DICE_CONFIG.spawnSpread;
        for (let i = 0; i < 3; i++) {
            const die = new Dice(
                this.scene,
                this.physics,
                {
                    x: (i - 1) * spacing,
                    y: DICE_CONFIG.spawnHeight,
                    z: 0
                },
                i
            );
            this.dice.push(die);
        }
    }
    
    /**
     * Roll all dice
     */
    rollDice() {
        this.dice.forEach(die => die.roll());
    }
    
    /**
     * Update game (called every frame)
     */
    update(deltaTime) {
        // Update dice
        this.dice.forEach(die => die.update(deltaTime));
        
        // Check state transitions
        if (this.state === GAME_STATES.ROLLING) {
            // Check if all dice settled
            if (this.allDiceSettled()) {
                this.state = GAME_STATES.READING;
                this.updatePhase(MESSAGES.reading);
                setTimeout(() => this.readResults(), 500);
            }
        } else if (this.state === GAME_STATES.REVEALING) {
            // Pulse glow on dice
            this.dice.forEach(die => {
                die.pulseGlow(Date.now() / 1000);
            });
        }
    }
    
    /**
     * Check if all dice have settled
     */
    allDiceSettled() {
        return this.dice.every(die => die.isSettled);
    }
    
    /**
     * Read results from dice
     */
    readResults() {
        const results = this.dice.map(die => die.getResult().color);
        
        // Calculate payout
        const outcome = this.calculatePayout(results);
        
        // Show results
        this.showResults(results, outcome);
        
        // Update state
        this.state = GAME_STATES.REVEALING;
        this.updatePhase(MESSAGES.revealing);
    }
    
    /**
     * Calculate payout based on results
     */
    calculatePayout(results) {
        let totalWon = 0;
        let totalBet = this.getTotalBet();
        let breakdown = {};
        
        // Check each bet color
        Object.keys(this.bets).forEach(color => {
            if (this.bets[color] > 0) {
                // Count matches
                const matches = results.filter(r => r === color).length;
                const multiplier = GAME_RULES.payouts[matches];
                const won = this.bets[color] * multiplier;
                
                totalWon += won;
                breakdown[color] = {
                    bet: this.bets[color],
                    matches,
                    won,
                    net: won - this.bets[color]
                };
            }
        });
        
        const netResult = totalWon - totalBet;
        
        // Update balance
        this.balance += totalWon;
        this.updateBalanceDisplay();
        
        return {
            totalBet,
            totalWon,
            netResult,
            breakdown
        };
    }
    
    /**
     * Show results UI
     */
    showResults(results, outcome) {
        // Show dice emojis
        const emojis = results.map(color => COLOR_EMOJIS[color]).join(' ');
        this.ui.resultDice.textContent = emojis;
        
        // Show outcome
        const netResult = outcome.netResult;
        if (netResult > 0) {
            this.ui.resultOutcome.textContent = `+${netResult} points`;
            this.ui.resultOutcome.className = 'result-outcome win';
        } else {
            this.ui.resultOutcome.textContent = `${netResult} points`;
            this.ui.resultOutcome.className = 'result-outcome loss';
        }
        
        // Flash winning panels
        this.flashWinningPanels(results);
        
        // Show result display
        setTimeout(() => {
            this.ui.result.style.display = 'block';
        }, 800);
        
        // Reset for next round
        setTimeout(() => {
            this.resetForNextRound();
        }, 4000);
    }
    
    /**
     * Flash winning color panels
     */
    flashWinningPanels(results) {
        const uniqueColors = [...new Set(results)];
        
        uniqueColors.forEach(color => {
            const panel = document.querySelector(`.color-panel[data-color="${color}"]`);
            if (panel) {
                panel.classList.add('winning-panel');
                setTimeout(() => {
                    panel.classList.remove('winning-panel');
                }, 2000);
            }
        });
    }
    
    /**
     * Reset for next round
     */
    resetForNextRound() {
        // Clear bets
        Object.keys(this.bets).forEach(color => {
            this.bets[color] = 0;
            this.updateBetDisplay(color);
        });
        
        this.updateTotalBetDisplay();
        
        // Re-enable controls
        this.ui.btnRoll.disabled = true;
        this.ui.btnClear.disabled = false;
        document.querySelectorAll('.color-panel').forEach(p => p.style.pointerEvents = 'auto');
        
        // Reset state
        this.state = GAME_STATES.IDLE;
        this.updatePhase(MESSAGES.ready);
    }
    
    /**
     * Cash out
     */
    cashOut() {
        if (this.state !== GAME_STATES.IDLE) return;
        
        const finalBalance = this.balance + this.getTotalBet();
        alert(`Cashing out ${finalBalance} points!\n\nThanks for playing!`);
        
        // In real implementation, this would call PHP backend
        // to transfer points back to main account
    }
    
    /**
     * Get total bet amount
     */
    getTotalBet() {
        return Object.values(this.bets).reduce((sum, bet) => sum + bet, 0);
    }
    
    /**
     * Update bet display for a color
     */
    updateBetDisplay(color) {
        const badge = document.querySelector(`.bet-badge[data-color="${color}"]`);
        const panel = document.querySelector(`.color-panel[data-color="${color}"]`);
        
        if (this.bets[color] > 0) {
            badge.textContent = this.bets[color];
            panel.classList.add('has-bet');
        } else {
            badge.textContent = '0';
            panel.classList.remove('has-bet');
        }
    }
    
    /**
     * Update balance display
     */
    updateBalanceDisplay() {
        this.ui.balance.textContent = this.balance;
    }
    
    /**
     * Update total bet display
     */
    updateTotalBetDisplay() {
        this.ui.totalBet.textContent = this.getTotalBet();
    }
    
    /**
     * Update phase indicator
     */
    updatePhase(message) {
        this.ui.phase.textContent = message;
    }
    
    /**
     * Show temporary message
     */
    showMessage(message) {
        this.updatePhase(message);
        setTimeout(() => {
            if (this.state === GAME_STATES.IDLE) {
                this.updatePhase(MESSAGES.ready);
            }
        }, 2000);
    }
}
