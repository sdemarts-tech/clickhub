/**
 * MAIN ENTRY POINT
 * Connects all modules and runs the game loop
 */

import { Renderer } from './renderer.js';
import { PhysicsWorld } from './physics.js';
import { Game } from './game.js';

// ====================================
// INITIALIZATION
// ====================================

let renderer, physics, game;
let clock, lastTime = 0;
let isRunning = false;

/**
 * Initialize the game
 */
async function init() {
    console.log('🎲 Initializing Color Game...');
    
    try {
        // Get container
        const container = document.getElementById('canvas-container');
        
        // Initialize renderer (THREE.js)
        console.log('📦 Setting up renderer...');
        renderer = new Renderer(container);
        renderer.init();
        
        // Initialize physics (Cannon-es)
        console.log('⚙️ Setting up physics...');
        physics = new PhysicsWorld();
        physics.init();
        
        // Initialize game
        console.log('🎮 Setting up game logic...');
        game = new Game(renderer, physics);
        
        // Hide loading screen
        const loadingScreen = document.getElementById('loading-screen');
        loadingScreen.classList.add('hidden');
        setTimeout(() => {
            loadingScreen.style.display = 'none';
        }, 500);
        
        // Start game loop
        isRunning = true;
        clock = new THREE.Clock();
        animate();
        
        console.log('✅ Game initialized successfully!');
        console.log('🎲 Ready to play!');
        
    } catch (error) {
        console.error('❌ Failed to initialize game:', error);
        alert('Failed to load game. Please refresh the page.');
    }
}

/**
 * Main game loop
 */
function animate() {
    if (!isRunning) return;
    
    requestAnimationFrame(animate);
    
    // Get delta time
    const currentTime = performance.now() / 1000;
    const deltaTime = currentTime - lastTime;
    lastTime = currentTime;
    
    // Limit delta time to prevent physics explosions
    const safeDelta = Math.min(deltaTime, 0.033); // Max 33ms (30fps minimum)
    
    // Update physics
    physics.step(safeDelta);
    
    // Update game
    game.update(safeDelta);
    
    // Render scene
    renderer.render();
}

/**
 * Handle visibility change (pause when tab hidden)
 */
document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
        console.log('⏸️ Game paused (tab hidden)');
        isRunning = false;
    } else {
        console.log('▶️ Game resumed');
        isRunning = true;
        lastTime = performance.now() / 1000;
        animate();
    }
});

/**
 * Handle errors
 */
window.addEventListener('error', (event) => {
    console.error('💥 Error:', event.error);
});

window.addEventListener('unhandledrejection', (event) => {
    console.error('💥 Unhandled promise rejection:', event.reason);
});

// ====================================
// START THE GAME
// ====================================

// Wait for DOM and libraries to load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    // DOM already loaded
    init();
}

// Export for debugging
window.ColorGame = {
    renderer,
    physics,
    game,
    get state() {
        return game ? game.state : null;
    },
    get balance() {
        return game ? game.balance : null;
    }
};

console.log('🎲 Color Game loading...');
