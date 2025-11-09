/**
 * MAIN ENTRY POINT
 * Connects all modules and runs the game loop
 */

import { Renderer } from './renderer.js';
import { PhysicsWorld } from './physics.js';
import { Game } from './game.js';
import { PHYSICS } from './config.js';

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
    
    // Wait for CANNON to be available (with max retries)
    let retryCount = 0;
    const maxRetries = 100; // 10 seconds max wait (increased for slow connections)
    
    while (retryCount < maxRetries) {
        // Check multiple ways CANNON might be available
        if (typeof CANNON !== 'undefined') {
            console.log('✅ CANNON library found!');
            break;
        }
        if (typeof window !== 'undefined' && typeof window.CANNON !== 'undefined') {
            window.CANNON = window.CANNON; // Ensure it's available globally
            console.log('✅ CANNON library found on window!');
            break;
        }
        if (window.CANNON_LOADED === true && typeof CANNON !== 'undefined') {
            console.log('✅ CANNON library loaded flag detected!');
            break;
        }
        
        // Only log every 2 seconds to reduce console spam
        if (retryCount % 20 === 0 && retryCount > 0) {
            console.warn('⏳ CANNON library not loaded yet. Waiting... (' + Math.floor(retryCount / 10) + 's)');
        }
        
        await new Promise(resolve => setTimeout(resolve, 100));
        retryCount++;
    }
    
    // Final check
    const CANNON_REF = typeof CANNON !== 'undefined' ? CANNON : 
                       (typeof window !== 'undefined' && window.CANNON ? window.CANNON : null);
    
    if (!CANNON_REF) {
        console.error('❌ CANNON library failed to load after ' + (maxRetries / 10) + ' seconds!');
        console.error('Debug info:');
        console.error('  - typeof CANNON:', typeof CANNON);
        console.error('  - typeof window.CANNON:', typeof window?.CANNON);
        console.error('  - window.CANNON_LOADED:', window?.CANNON_LOADED);
        alert('Failed to load physics library (CANNON.js).\n\nPlease:\n1. Check your internet connection\n2. Disable any ad blockers\n3. Try refreshing the page\n\nIf the problem persists, the CDN servers may be temporarily unavailable.');
        return;
    }
    
    // Make CANNON available globally if it wasn't already
    if (typeof CANNON === 'undefined' && CANNON_REF) {
        window.CANNON = CANNON_REF;
        // Also set it in global scope for ES modules
        if (typeof globalThis !== 'undefined') {
            globalThis.CANNON = CANNON_REF;
        }
    }
    
    console.log('✅ CANNON library is loaded and ready');
    
    try {
        // Verify PHYSICS config is loaded correctly (check for old cached version)
        if (!PHYSICS || !PHYSICS.perya) {
            console.error('⚠️ WARNING: Old config.js is cached! PHYSICS.perya is missing.');
            console.error('Current PHYSICS keys:', PHYSICS ? Object.keys(PHYSICS) : 'PHYSICS is undefined');
            console.error('Please do a HARD REFRESH to load the new config:');
            console.error('  Windows/Linux: Ctrl+Shift+R');
            console.error('  Mac: Cmd+Shift+R');
            console.error('Or clear browser cache (Ctrl+Shift+Delete) and reload.');
            alert('⚠️ Old game files detected!\n\nPlease do a HARD REFRESH:\n• Windows/Linux: Ctrl+Shift+R\n• Mac: Cmd+Shift+R\n\nThis will load the new perya mechanism.');
        } else {
            console.log('✅ Config.js loaded correctly - PHYSICS.perya exists');
        }
        
        // Get container
        const container = document.getElementById('canvas-container');
        
        // Initialize renderer (THREE.js)
        console.log('📦 Setting up renderer...');
        renderer = new Renderer(container);
        renderer.init();
        
        // Initialize physics (Cannon.js)
        console.log('⚙️ Setting up physics...');
        if (typeof CANNON === 'undefined') {
            throw new Error('CANNON library not loaded. Please check the script includes.');
        }
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
        
        // View toggle button
        const viewToggleBtn = document.getElementById('btn-view-toggle');
        if (viewToggleBtn) {
            viewToggleBtn.addEventListener('click', () => {
                const isSideView = renderer.toggleView();
                viewToggleBtn.textContent = isSideView ? '🔄 Front View' : '🔄 Side View';
            });
        }
        
        // Keyboard shortcut for view toggle (V key)
        document.addEventListener('keydown', (e) => {
            if (e.code === 'KeyV' && !e.target.matches('input, textarea')) {
                const isSideView = renderer.toggleView();
                if (viewToggleBtn) {
                    viewToggleBtn.textContent = isSideView ? '🔄 Front View' : '🔄 Side View';
                }
            }
        });
        
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
    if (!isRunning || !physics || !game || !renderer) return;
    
    requestAnimationFrame(animate);
    
    // Get delta time
    const currentTime = performance.now() / 1000;
    const deltaTime = currentTime - lastTime;
    lastTime = currentTime;
    
    // Limit delta time to prevent physics explosions
    const safeDelta = Math.min(deltaTime, 0.033); // Max 33ms (30fps minimum)
    
    // Update physics (if initialized)
    if (physics && physics.world) {
        physics.step(safeDelta);
    }
    
    // Update game (if initialized)
    if (game) {
        game.update(safeDelta);
    }
    
    // Render scene (if initialized)
    if (renderer) {
        renderer.render();
    }
}

/**
 * Handle visibility change (pause when tab hidden)
 */
document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
        console.log('⏸️ Game paused (tab hidden)');
        isRunning = false;
        
        // Auto-save points when tab is hidden
        if (game && game.balance !== undefined && game.userId) {
            game.updatePointsInDatabase(game.balance);
        }
    } else {
        console.log('▶️ Game resumed');
        isRunning = true;
        lastTime = performance.now() / 1000;
        animate();
    }
});

/**
 * Auto-save points when user closes tab/navigates away
 */
window.addEventListener('beforeunload', (event) => {
    if (game && game.balance !== undefined && game.userId) {
        // Use sendBeacon with FormData for reliable sending during page unload
        const formData = new FormData();
        formData.append('balance', game.balance);
        navigator.sendBeacon('update-points.php', formData);
    }
});

/**
 * Dynamic viewport height update for mobile browser address bar
 */
function updateHeight() {
    const vh = window.innerHeight * 0.01;
    document.documentElement.style.setProperty('--vh', `${vh}px`);
}

updateHeight();
window.addEventListener('resize', updateHeight);

/**
 * Handle orientation change with debouncing
 */
let resizeTimeout;
let orientationChangeTimeout;

window.addEventListener('orientationchange', () => {
    clearTimeout(orientationChangeTimeout);
    orientationChangeTimeout = setTimeout(() => {
        updateHeight();
        if (renderer) {
            renderer.onWindowResize();
        }
    }, 100);
});

// Debounced resize handler
window.addEventListener('resize', () => {
    clearTimeout(resizeTimeout);
    resizeTimeout = setTimeout(() => {
        updateHeight();
        if (renderer) {
            renderer.onWindowResize();
        }
    }, 100);
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
