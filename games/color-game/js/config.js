/**
 * GAME CONFIGURATION
 * All constants and settings in one place
 * Version: 2024-12-19 (Position updates)
 */

// ====================================
// COLOR DEFINITIONS
// ====================================
export const COLORS = {
    // Darker, richer colors for better saturation
    red: 0xDD0000,      // #DD0000 - Darker red (was 0xFF0000)
    yellow: 0xFFBB00,   // #FFBB00 - Richer yellow (was 0xFFD700)
    blue: 0x0066FF,     // #0066FF - Deeper blue (was 0x0080FF)
    green: 0x00DD00,    // #00DD00 - Richer green (was 0x00FF00)
    white: 0xFFFFFF,    // #FFFFFF - Pure white
    pink: 0xFF0088      // #FF0088 - More saturated pink (was 0xFF1493)
};

export const COLOR_NAMES = ['red', 'yellow', 'blue', 'green', 'white', 'pink'];

export const COLOR_EMOJIS = {
    red: '🔴',
    yellow: '🟡',
    blue: '🔵',
    green: '🟢',
    white: '⚪',
    pink: '🟣'
};

// Face index to color mapping (for reading results)
// Must match THREE.js BoxGeometry material order: [right, left, top, bottom, front, back]
export const FACE_COLORS = {
    0: 'green',   // +X (right face) - matches material index 0
    1: 'yellow',  // -X (left face) - matches material index 1
    2: 'red',     // +Y (top face) - matches material index 2
    3: 'blue',    // -Y (bottom face) - matches material index 3
    4: 'white',   // +Z (front face) - matches material index 4
    5: 'pink'     // -Z (back face) - matches material index 5
};

// ====================================
// DICE SETTINGS
// ====================================
export const DICE = {
    size: 1.5,              // Cube size
    cornerRadius: 0.25,     // Border rounding (increased for more visible roundness)
    segments: 20,           // Smoothness of rounded corners (increased for smoother curves)
    
    // Visual properties - direct color matching, no processing
    metalness: 0.0,         // No metalness
    roughness: 0.4,         // More matte to preserve color saturation
    emissiveIntensity: 0.0, // NO emissive - use direct colors only
    
    // Border settings - MUCH THICKER
    borderWidth: 0.25,      // Much thicker borders (as percentage of cube size)
    borderColor: 0xFFFFFF,  // Pure white
    borderOpacity: 1.0,
    borderEdgeThreshold: 1.0, // Edge detection threshold for smooth curves
    
    // Initial spawn
    spawnHeight: 8,
    spawnSpread: 2.5
};

// ====================================
// PHYSICS SETTINGS
// ====================================
// Version: 2.0 - Perya Mechanism (2024-11-04)
// Updated: 2024-12-19 - Position fixes
export const PHYSICS = {
    // World
    gravity: -35,           // INCREASED from -25 - much stronger pull!
    timeStep: 1/60,
    
    // PERYA MECHANISM STRUCTURE
    perya: {
        // Frame (U-shaped holding platform at top of slide)
        frame: {
            baseWidth: 5.5,   // Base platform width (more room for cubes)
            baseDepth: 2.5,   // Base platform depth (slightly deeper)
            baseHeight: 0.2,  // Base platform thickness (thin)
            railWidth: 0.3,   // Side rail thickness
            railDepth: 2.5,   // Side rail depth (same as base)
            railHeight: 1.8,  // Side rail height (taller than cube height 1.5)
            // Position is calculated dynamically to attach to slide top
            // Slide start: y=6, z=-7 (frame attaches here)
            fadeTime: 0.5     // Fade out duration
        },
        
        // Slide ramp (60° angle)
        slide: {
            angle: 60,       // Degrees downward
            length: 20,      // Slide length
            width: 10,       // Slide width (X-axis)
            friction: 0.12,   // REDUCED - super slippery!
            restitution: 0.05  // REDUCED - no energy loss
        },
        
        // Stopper wedge (at bottom of slide)
        stopper: {
            angle: 35,       // INCREASED to 35° - steeper angle = harder hit
            height: 1.0,     // INCREASED to 1.0 - taller obstacle
            width: 5,        // Match slide width
            depth: 1.0,      // Thicker for more contact
            restitution: 1.0  // MAXIMUM bounce!
        },
        
        // Platform (landing area)
        platform: {
            width: 12,       // Platform width (X-axis) - wider
            depth: 12,        // Platform depth (Z-axis)
            height: 0.3,     // Platform thickness
            wallHeight: 1.5, // Front/back wall height (same as cube height)
            sideWallHeight: 1.5, // Left/right wall height
            wallThickness: 0.4,
            friction: 0.2,   // Low friction for sliding
            restitution: 0.5  // Moderate bounce - will settle
        }
    },
    
    // Wooden cube properties (for perya mechanism)
    woodenCube: {
        mass: 4.0,          // Heavier = more momentum
        friction: 0.30,     
        restitution: 0.65,   // Reduced from 0.90 - still bouncy but will settle
        linearDamping: 0.02,   // Very low damping for natural movement
        angularDamping: 0.05   // Low damping - spins freely
    },
    
    // Settling detection
    sleepSpeedLimit: 0.01,     // Lower = stricter (was 0.03)
    sleepTimeLimit: 0.8,       // Longer = more reliable (was 0.4)
    angularThreshold: 0.05,    // Lower = stricter (was 0.15)
    
    // Auto-correction settings
    autoCorrect: true,
    correctThreshold: 12
};

// ====================================
// CAMERA SETTINGS
// ====================================
export const CAMERA = {
    fov: 60,
    near: 0.1,
    far: 1000,
    
    // Positions - Camera positioned to see slide mechanism and platform (front view)
    // Camera pulled back and up to see longer platform
    defaultPosition: { x: 0, y: 10, z: 16 },  // Was: y:8, z:12
    followPosition: { x: 0, y: 6, z: 12 },    // Was: y:4, z:8
    topPosition: { x: 0, y: 20, z: 0 },       // Was: y:15
    
    lookAt: { x: 0, y: 2, z: -2 }  // Look slightly forward to see platform
};

// ====================================
// LIGHTING SETTINGS
// ====================================
export const LIGHTS = {
    ambient: {
        color: 0xFFFFFF,    // White ambient for true color reproduction
        intensity: 0.4      // Lower to preserve rich colors
    },
    
    directional: {
        color: 0xffffff,
        intensity: 1.0,     // Normal brightness
        position: { x: 5, y: 10, z: 5 },
        castShadow: true
    },
    
    neon1: {
        color: 0x667eea,
        intensity: 0.5,     // Reduced from 1.5
        distance: 15,
        position: { x: -5, y: 3, z: 0 }
    },
    
    neon2: {
        color: 0x764ba2,
        intensity: 0.5,     // Reduced from 1.5
        distance: 15,
        position: { x: 5, y: 3, z: 0 }
    },
    
    neon3: {
        color: 0x00CED1,
        intensity: 0.4,     // Reduced from 1.2
        distance: 15,
        position: { x: 0, y: 3, z: -5 }
    }
};

// ====================================
// GAME RULES
// ====================================
export const GAME_RULES = {
    // Payouts
    // Formula: payout = bet × (number_of_matches + 1)
    payouts: {
        0: 0,   // No match = 0×
        1: 2,   // 1 match = 2× (double your bet)
        2: 3,   // 2 matches = 3× (triple your bet)
        3: 4    // 3 matches = 4× (quadruple your bet)
    },
    
    // Betting limits
    minBet: 5,
    maxBetPerColor: 500,
    maxTotalBet: 1000,
    
    // Starting balance
    startingBalance: 500,
    
    // Timing
    bettingTime: 15,        // Seconds for betting phase
    rollingTime: 3,         // Expected roll duration
    revealDelay: 1          // Delay before showing result
};

// ====================================
// GAME STATES
// ====================================
export const GAME_STATES = {
    IDLE: 'idle',
    BETTING: 'betting',
    ROLLING: 'rolling',
    SETTLING: 'settling',
    READING: 'reading',
    REVEALING: 'revealing',
    COMPLETE: 'complete'
};

// ====================================
// UI MESSAGES
// ====================================
export const MESSAGES = {
    ready: 'Ready to Roll!',
    betting: 'Place Your Bets!',
    bettingLast: 'Last Chance!',
    rolling: '🎲 Dice Rolling...',
    settling: '⏳ Dice Settling...',
    reading: '📊 Reading Results...',
    revealing: '✨ Result Revealed!',
    
    winBig: '🎉 BIG WIN!',
    winSmall: '✅ You Won!',
    loss: '💔 Better Luck Next Time',
    nobet: 'Place a bet to play!'
};

// ====================================
// ANIMATION TIMINGS
// ====================================
export const ANIMATIONS = {
    coinDrop: 300,          // ms for coin animation
    panelFlash: 500,        // ms for winning panel flash
    glowPulse: 1000,        // ms for glow cycle
    resultDelay: 800        // ms before showing result overlay
};

// ====================================
// EXPORT ALL
// ====================================
export default {
    COLORS,
    COLOR_NAMES,
    COLOR_EMOJIS,
    FACE_COLORS,
    DICE,
    PHYSICS, // Explicitly included in default export
    CAMERA,
    LIGHTS,
    GAME_RULES,
    GAME_STATES,
    MESSAGES,
    ANIMATIONS
};

// Debug: Verify PHYSICS.perya is exported
if (typeof window !== 'undefined') {
    window.PHYSICS_DEBUG = PHYSICS;
    console.log('📦 Config.js loaded - PHYSICS.perya:', PHYSICS.perya ? '✅ EXISTS' : '❌ MISSING');
    if (PHYSICS.perya) {
        console.log('📦 Perya config:', PHYSICS.perya);
    }
}
