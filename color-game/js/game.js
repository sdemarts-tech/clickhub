/**
 * GAME MODULE
 * Handles game state machine, betting, rolling, and payouts
 */

import { Dice } from './dice.js';
import { GAME_STATES, GAME_RULES, MESSAGES, COLOR_EMOJIS, DICE as DICE_CONFIG, PHYSICS } from './config.js';

export class Game {
    constructor(renderer, physicsWorld) {
        this.renderer = renderer;
        this.physics = physicsWorld;
        
        this.scene = renderer.getScene();
        this.world = physicsWorld.getWorld();
        
        // Game state
        this.state = GAME_STATES.IDLE;
        this.settleStartTime = null; // Track when all dice first settle
        // Use real user points from PHP, fallback to default
        this.balance = window.USER_DATA?.points || GAME_RULES.startingBalance;
        this.userId = window.USER_DATA?.userId || null;
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
        
        // Create initial dice on frame
        this.createDice();
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
            btnClear: document.getElementById('btn-clear')
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
        
        // Prevent negative balance
        if (this.selectedChip > this.balance) {
            this.showMessage('Insufficient balance!');
            return;
        }
        
        if (this.balance <= 0) {
            this.showMessage('No points left! Returning to dashboard...');
            this.ui.btnRoll.disabled = true;
            setTimeout(() => {
                window.location.href = '../../dashboard.php';
            }, 2000);
            return;
        }
        
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
        
        // Save balance to database immediately after bet placed
        this.updatePointsInDatabase(this.balance);
        
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
        
        // Save balance after clearing bets
        this.updatePointsInDatabase(this.balance);
    }
    
    /**
     * Update points in database
     */
    updatePointsInDatabase(balance) {
        if (!this.userId) return; // Skip if no user ID
        
        fetch('update-points.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ balance: balance })
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                console.error('Failed to save points:', data.error);
            }
        })
        .catch(error => console.error('Error updating points:', error));
    }
    
    /**
     * Start dice roll (perya mechanism)
     */
    startRoll() {
        // Reset settle timer for this roll
        this.settleStartTime = null;
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
        
        // Create dice on frame (if not already created)
        if (this.dice.length === 0) {
            this.createDice();
        }
        
        // Fade out frame and disable physics
        this.renderer.fadeOutFrame();
        setTimeout(() => {
            this.physics.disableFrame();
            // Release cubes (gravity takes over)
            this.rollDice();
        }, PHYSICS.perya.frame.fadeTime * 1000 * 0.5); // Disable collision halfway through fade
        
        // Camera shake when cubes hit stopper (delayed)
        setTimeout(() => {
            this.renderer.shakeCamera(0.2, 200);
        }, 1500); // Approximate time when cubes hit stopper
    }
    
    /**
     * Create 3 dice positioned on the holding frame
     */
    createDice() {
        // Clear old dice
        this.dice.forEach(die => die.dispose());
        this.dice = [];
        
        // Safety check
        if (!PHYSICS || !PHYSICS.perya) {
            console.error('PHYSICS.perya not found in createDice, using fallback positions');
            // Fallback: create dice at old spawn positions
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
            return;
        }
        
        const perya = PHYSICS.perya;
        const diceSize = DICE_CONFIG.size; // 1.5
        
        // Calculate proper spacing for 3 dice on slide
        const gapBetweenDice = 0.5; // Space between each die
        const spacing = diceSize + gapBetweenDice; // 2.0 total spacing
        
        // Slide parameters (from physics.js and config.js)
        // Slide starts at: z=2, y=6 (top edge)
        // Slide is rotated 60° around X axis (tilted down)
        const slideTopZ = 2;      // Slide start Z position
        const slideTopY = 6;      // Slide top Y position (at z=2)
        const slideAngle = 60 * (Math.PI / 180);  // 60° in radians
        
        // Dice positions (user-specified)
        const diceSittingY = 10;
        const diceSittingZ = -6;
        
        console.log('Creating dice with spacing:', spacing, '(dice size:', diceSize, '+ gap:', gapBetweenDice, ')');
        console.log('Dice will sit at Y:', diceSittingY, ', Z:', diceSittingZ);
        
        // Create 3 dice evenly spaced: [-2, 0, +2]
        for (let i = 0; i < 3; i++) {
            const xPosition = (i - 1) * spacing; // -2.0, 0, +2.0
            
            const spawnPos = {
                x: xPosition,
                y: diceSittingY,          // Y = 10
                z: diceSittingZ           // Z = -6
            };
            
            console.log(`Dice ${i+1} spawning at X=${xPosition.toFixed(2)}, Y=${diceSittingY}, Z=${diceSittingZ}`);
            
            const die = new Dice(
                this.scene,
                this.physics,
                spawnPos,
                i
            );
            
            // Verify position after creation
            console.log(`Dice ${i} physics position:`, die.body.position);
            console.log(`Dice ${i} initial velocity:`, die.body.velocity);
            console.log(`Dice ${i} body type:`, die.body.type === CANNON.Body.KINEMATIC ? 'KINEMATIC' : 'DYNAMIC');
            console.log(`Dice ${i} mesh visible:`, die.mesh.visible);
            console.log(`Dice ${i} mesh in scene:`, die.scene.children.includes(die.mesh));
            console.log(`Dice ${i} body in world:`, this.world.bodies.includes(die.body));
            
            this.dice.push(die);
        }
        
        console.log(`✅ All ${this.dice.length} dice created with proper spacing`);
        console.log(`   Physics world has ${this.world.bodies.length} total bodies`);
        console.log(`   Scene has ${this.scene.children.length} total children`);
    }
    
    /**
     * Roll all dice
     */
    rollDice() {
        console.log(`🎲 rollDice() called - Total dice in array: ${this.dice.length}`);
        
        if (this.dice.length === 0) {
            console.error('❌ ERROR: No dice in array when rollDice() called!');
            return;
        }
        
        this.dice.forEach((die, index) => {
            try {
                console.log(`🎲 Rolling dice ${index} (actual index: ${die.index})`);
                console.log(`   Position before roll: (${die.body.position.x.toFixed(2)}, ${die.body.position.y.toFixed(2)}, ${die.body.position.z.toFixed(2)})`);
                console.log(`   Body type before roll: ${die.body.type === CANNON.Body.KINEMATIC ? 'KINEMATIC' : 'DYNAMIC'}`);
                console.log(`   Mesh exists: ${die.mesh ? 'YES' : 'NO'}`);
                console.log(`   Body exists: ${die.body ? 'YES' : 'NO'}`);
                
                // Call roll() which handles the KINEMATIC -> DYNAMIC transition
                die.roll();
                
                console.log(`   Body type after roll: ${die.body.type === CANNON.Body.KINEMATIC ? 'KINEMATIC' : 'DYNAMIC'}`);
                console.log(`   Position after roll: (${die.body.position.x.toFixed(2)}, ${die.body.position.y.toFixed(2)}, ${die.body.position.z.toFixed(2)})`);
                console.log(`   Velocity after roll: (${die.body.velocity.x.toFixed(2)}, ${die.body.velocity.y.toFixed(2)}, ${die.body.velocity.z.toFixed(2)})`);
            } catch (error) {
                console.error(`❌ ERROR rolling dice ${index}:`, error);
                console.error('   Stack:', error.stack);
            }
        });
        
        console.log(`✅ Finished rolling all ${this.dice.length} dice`);
    }
    
    /**
     * Update game (called every frame)
     */
    update(deltaTime) {
        // Update dice
        this.dice.forEach((die, index) => {
            try {
                die.update(deltaTime);
            } catch (error) {
                console.error(`❌ ERROR updating dice ${index}:`, error);
            }
        });
        
        // Check state transitions
        if (this.state === GAME_STATES.ROLLING) {
            const allSettled = this.allDiceSettled();
            
            if (allSettled && !this.settleStartTime) {
                // First time all dice are settled - start timer
                this.settleStartTime = Date.now();
            }
            
            if (allSettled && this.settleStartTime && 
                (Date.now() - this.settleStartTime > 1000)) {
                // All dice settled AND 1 second has passed
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
        // Check both that dice are settled AND that their results have been read
        return this.dice.every(die => die.isSettled && die.finalColorName !== null);
    }
    
    /**
     * Read results from dice
     */
    readResults() {
        // Double-check all dice have results before proceeding
        const allHaveResults = this.dice.every(die => die.finalColorName !== null);
        if (!allHaveResults) {
            console.warn('Not all dice have results yet, waiting...');
            setTimeout(() => this.readResults(), 200);
            return;
        }
        
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
        
        // Save new balance to database immediately after roll
        this.updatePointsInDatabase(this.balance);
        
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
        
        // Show outcome - compare total payout vs total bet to determine win/loss
        const totalBet = outcome.totalBet;
        const totalWon = outcome.totalWon;
        const netResult = outcome.netResult;
        
        if (totalWon > totalBet) {
            // Net positive - show total winnings
            this.ui.resultOutcome.textContent = `You win ${totalWon} points`;
            this.ui.resultOutcome.className = 'result-outcome win';
        } else if (totalWon < totalBet) {
            // Net negative - show loss amount
            const lossAmount = totalBet - totalWon;
            this.ui.resultOutcome.textContent = `You lost ${lossAmount} points`;
            this.ui.resultOutcome.className = 'result-outcome loss';
        } else {
            // Break even - totalWon === totalBet
            this.ui.resultOutcome.textContent = `Break even (no change)`;
            this.ui.resultOutcome.className = 'result-outcome';
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
        // Reset settle timer for next round
        this.settleStartTime = null;
        // Clear bets
        Object.keys(this.bets).forEach(color => {
            this.bets[color] = 0;
            this.updateBetDisplay(color);
        });
        
        this.updateTotalBetDisplay();
        
        // Re-enable frame for next round
        this.renderer.resetFrame();
        this.physics.enableFrame();
        
        // Create new dice on frame
        this.createDice();
        
        // Re-enable controls
        this.ui.btnRoll.disabled = false; // Enable roll button
        this.ui.btnClear.disabled = false;
        document.querySelectorAll('.color-panel').forEach(p => p.style.pointerEvents = 'auto');
        
        // Reset state
        this.state = GAME_STATES.IDLE;
        this.updatePhase(MESSAGES.ready);
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
