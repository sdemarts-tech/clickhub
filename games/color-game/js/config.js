/**
 * GAME CONFIGURATION
 * All constants and settings in one place
 */

// ====================================
// COLOR DEFINITIONS
// ====================================
export const COLORS = {
    red: 0xFF0000,
    yellow: 0xFFD700,
    blue: 0x0080FF,
    green: 0x00FF00,
    white: 0xFFFFFF,
    pink: 0xFF1493
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
export const FACE_COLORS = {
    0: 'red',     // +Y (top in default orientation)
    1: 'blue',    // -Y (bottom)
    2: 'green',   // +X (right)
    3: 'yellow',  // -X (left)
    4: 'white',   // +Z (front)
    5: 'pink'     // -Z (back)
};

// ====================================
// DICE SETTINGS
// ====================================
export const DICE = {
    size: 1.5,              // Cube size
    cornerRadius: 0.12,     // Border rounding
    borderWidth: 0.05,      // White border thickness
    segments: 12,           // Smoothness of rounded corners
    
    // Visual properties
    metalness: 0.3,
    roughness: 0.4,
    emissiveIntensity: 0.3,
    
    // Initial spawn
    spawnHeight: 8,
    spawnSpread: 2.5
};

// ====================================
// PHYSICS SETTINGS
// ====================================
export const PHYSICS = {
    // World
    gravity: -15,           // Stronger gravity for faster fall
    timeStep: 1/60,
    
    // Table
    tableY: -2,
    tableWidth: 12,
    tableDepth: 8,
    tableHeight: 0.3,
    
    // Materials
    tableFriction: 0.4,
    tableRestitution: 0.3,  // Table bounce
    
    diceFriction: 0.3,
    diceRestitution: 0.4,   // Dice bounce
    
    // Dice body
    diceMass: 1.0,
    diceLinearDamping: 0.3,
    diceAngularDamping: 0.3,
    
    // Roll forces
    impulseStrength: 3,     // Initial downward force
    lateralForce: 2,        // Random sideways push
    torqueStrength: 8,      // Spin force
    
    // Settling detection
    sleepSpeedLimit: 0.1,
    sleepTimeLimit: 0.3,    // Seconds to confirm settled
    angularThreshold: 0.5   // Angular velocity threshold
};

// ====================================
// CAMERA SETTINGS
// ====================================
export const CAMERA = {
    fov: 60,
    near: 0.1,
    far: 1000,
    
    // Positions
    defaultPosition: { x: 0, y: 6, z: 10 },
    followPosition: { x: 0, y: 4, z: 8 },
    topPosition: { x: 0, y: 15, z: 0 },
    
    lookAt: { x: 0, y: 0, z: 0 }
};

// ====================================
// LIGHTING SETTINGS
// ====================================
export const LIGHTS = {
    ambient: {
        color: 0x404060,
        intensity: 0.5
    },
    
    directional: {
        color: 0xffffff,
        intensity: 1.0,
        position: { x: 5, y: 10, z: 5 },
        castShadow: true
    },
    
    neon1: {
        color: 0x667eea,
        intensity: 1.5,
        distance: 15,
        position: { x: -5, y: 3, z: 0 }
    },
    
    neon2: {
        color: 0x764ba2,
        intensity: 1.5,
        distance: 15,
        position: { x: 5, y: 3, z: 0 }
    },
    
    neon3: {
        color: 0x00CED1,
        intensity: 1.2,
        distance: 15,
        position: { x: 0, y: 3, z: -5 }
    }
};

// ====================================
// GAME RULES
// ====================================
export const GAME_RULES = {
    // Payouts
    payouts: {
        0: 0,   // No match
        1: 2,   // 1 match = 2×
        2: 5,   // 2 matches = 5×
        3: 10   // 3 matches = 10× (jackpot!)
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
    PHYSICS,
    CAMERA,
    LIGHTS,
    GAME_RULES,
    GAME_STATES,
    MESSAGES,
    ANIMATIONS
};
