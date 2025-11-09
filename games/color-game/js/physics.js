/**
 * PHYSICS MODULE
 * Handles Cannon-es physics world setup
 * 
 * WORLD CONVENTION (LOCKED):
 * - Up = +Y
 * - Front (toward player/camera) = -Z
 * - Right = +X
 * - Gravity = -Y (down)
 * - Ramp slopes downhill toward -Z (front/platform side)
 */

import { PHYSICS } from './config.js';

export class PhysicsWorld {
    constructor() {
        this.world = null;
        this.tableBody = null;
        this.frameBody = null;
        this.slideBody = null;
        this.stopperBody = null;
        this.walls = [];
        this.tableMaterial = null;
        this.slideMaterial = null;
        this.stopperMaterial = null;
        this.diceMaterial = null;
        this.contactMaterial = null;
    }
    
    /**
     * Initialize Cannon-es physics world
     */
    init() {
        // Create world
        this.world = new CANNON.World();
        this.world.gravity.set(0, PHYSICS.gravity, 0);
        
        // Better collision detection
        this.world.broadphase = new CANNON.NaiveBroadphase();
        this.world.solver.iterations = 10;
        this.world.solver.tolerance = 0.001;
        
        // Allow bodies to sleep when stationary
        this.world.allowSleep = true;
        
        // Create materials
        this.createMaterials();
        
        // Create table
        this.createTable();
        
        return this.world;
    }
    
    /**
     * Create physics materials for perya mechanism and dice
     */
    createMaterials() {
        // Safety check with fallback values
        let perya, wooden;
        
        if (!PHYSICS || !PHYSICS.perya || !PHYSICS.woodenCube) {
            console.error('PHYSICS.perya or PHYSICS.woodenCube is not defined');
            console.error('PHYSICS object:', PHYSICS);
            // Use fallback values
            perya = {
                platform: { friction: 0.45, restitution: 0.2 },
                slide: { friction: 0.25, restitution: 0.1 },
                stopper: { restitution: 0.6 }
            };
            wooden = {
                friction: 0.35,
                restitution: 0.35
            };
        } else {
            perya = PHYSICS.perya;
            wooden = PHYSICS.woodenCube;
        }
        
        // Platform material
        this.tableMaterial = new CANNON.Material('platform');
        
        // Slide material (low friction)
        this.slideMaterial = new CANNON.Material('slide');
        
        // Stopper material (bouncy)
        this.stopperMaterial = new CANNON.Material('stopper');
        
        // Dice material (wooden cubes)
        this.diceMaterial = new CANNON.Material('dice');
        
        // Wall material (high restitution for bouncing)
        this.wallMaterial = new CANNON.Material('wall');
        
        // Platform to dice contact
        this.contactMaterial = new CANNON.ContactMaterial(
            this.tableMaterial,
            this.diceMaterial,
            {
                friction: perya.platform.friction,
                restitution: perya.platform.restitution,
                contactEquationStiffness: 1e8,
                contactEquationRelaxation: 3
            }
        );
        
        // Slide to dice contact (low friction for sliding)
        const slideToDice = new CANNON.ContactMaterial(
            this.slideMaterial,
            this.diceMaterial,
            {
                friction: perya.slide.friction,
                restitution: perya.slide.restitution,
                contactEquationStiffness: 1e8,
                contactEquationRelaxation: 3
            }
        );
        
        // Stopper to dice contact (VERY bouncy for scatter effect)
        const stopperToDice = new CANNON.ContactMaterial(
            this.stopperMaterial,
            this.diceMaterial,
            {
                friction: 0.2,              // REDUCED from 0.4 - less grip
                restitution: 1.0,            // INCREASED to maximum - matches config
                contactEquationStiffness: 1e9,   // INCREASED - stiffer spring
                contactEquationRelaxation: 2     // REDUCED - more responsive
            }
        );
        
        // Dice to dice contact
        const diceToDice = new CANNON.ContactMaterial(
            this.diceMaterial,
            this.diceMaterial,
            {
                friction: wooden.friction,
                restitution: wooden.restitution,
                contactEquationStiffness: 1e8,
                contactEquationRelaxation: 3
            }
        );
        
        this.world.addContactMaterial(this.contactMaterial);
        this.world.addContactMaterial(slideToDice);
        this.world.addContactMaterial(stopperToDice);
        this.world.addContactMaterial(diceToDice);
    }
    
    /**
     * Create perya mechanism physics bodies (frame, slide, stopper, platform, walls)
     */
    createTable() {
        // Safety check
        if (!PHYSICS || !PHYSICS.perya) {
            console.error('PHYSICS.perya is not defined in createTable');
            // Create simple platform as fallback
            const platformShape = new CANNON.Box(new CANNON.Vec3(4, 0.15, 4));
            this.tableBody = new CANNON.Body({
                mass: 0,
                shape: platformShape,
                position: new CANNON.Vec3(0, 0.15, 0),
                material: this.tableMaterial
            });
            this.world.addBody(this.tableBody);
            return;
        }
        
        const perya = PHYSICS.perya;
        
        // Calculate slide start position (top of slide)
        const slideStartY = 6;
        const slideStartZ = -7;
        
        // No frame - dice are frozen at slide top
        this.frameBodies = [];
        this.frameBody = null;
        
        // 2. Create Slide (angled ramp)
        const slideWidth = perya.slide.width;
        
        // STEP 3: Define ramp edges FIRST (same as visual)
        // Top edge = original slide start position (back, high)
        // Bottom edge = at platform edge (low)
        // World convention: Front = -Z (toward camera), Back = +Z (away from camera)
        // Platform is centered at z=0, size=8, so extends from z=-4 (front) to z=+4 (back)
        // Slide starts at original position (z=2), frame is at z=-7
        const slideOriginalStartZ = 2; // Original slide start position
        const slideEndY = perya.platform.height / 2; // 0.15 (low - bottom edge)
        // Platform is centered at z=0, size=8
        // Platform extends from z=-4 (front, toward camera) to z=+4 (back, away from camera)
        // Slide should end at z=-12 (further toward camera)
        const slideEndZ = -12; // Further toward camera
        
        // Calculate actual slide length needed (distance from start to end)
        // Slide starts at z=2 (original) and ends at z=-12
        const deltaY = slideStartY - slideEndY; // 6 - 0.15 = 5.85
        const deltaZ = slideEndZ - slideOriginalStartZ; // -12 - 2 = -14
        const slideLength = Math.sqrt(deltaY * deltaY + deltaZ * deltaZ); // Actual length needed
        
        const slideShape = new CANNON.Box(new CANNON.Vec3(
            slideWidth / 2,
            0.1, // Thin plane
            slideLength / 2
        ));
        
        const slideMidY = (slideStartY + slideEndY) / 2;
        // Position slide so it goes from original start (z=2) to end (z=-12)
        const slideMidZ = -3; // User-specified position
        
        // STEP 5: Use Box collider instead of Plane (no normal confusion)
        this.slideBody = new CANNON.Body({
            mass: 0, // Static
            shape: slideShape, // Box shape (already defined above)
            position: new CANNON.Vec3(0, slideMidY, slideMidZ),
            material: this.slideMaterial
        });
        
        // STEP 2 & 4: Sync EXACTLY with mesh rotation
        // Simple X rotation - slide faces front (toward -Z)
        this.slideBody.quaternion.setFromAxisAngle(
            new CANNON.Vec3(1, 0, 0),
            Math.PI / 3  // +60° around X (tilt down toward -Z/front) - matches mesh
        );
        
        this.world.addBody(this.slideBody);
        
        // 3. Create Stopper Wedge (very bouncy hump at bottom of slide)
        const stopperHeight = perya.stopper.height; // 0.6
        const stopperWidth = perya.stopper.width; // 8
        const stopperDepth = perya.stopper.depth; // 0.8
        
        const stopperShape = new CANNON.Box(new CANNON.Vec3(
            stopperWidth / 2,
            stopperHeight / 2,
            stopperDepth / 2
        ));
        
        // Position stopper at the bottom of slide where it meets platform
        // Slide ends at z=-12, y=0.15
        // Platform is at y=0, stopper sits on platform
        const stopperY = 2; // User-specified position
        const stopperZ = -2; // User-specified position
        
        console.log('=== PHYSICS SLIDE POSITION ===');
        console.log('Slide physics body position:', this.slideBody.position);
        console.log('Slide end Z:', slideEndZ);
        console.log('Stopper position:', 'y =', stopperY, 'z =', stopperZ);
        console.log('Stopper restitution:', perya.stopper.restitution);
        console.log('==============================');
        
        this.stopperBody = new CANNON.Body({
            mass: 0, // Static
            shape: stopperShape,
            position: new CANNON.Vec3(0, stopperY, stopperZ),
            material: this.stopperMaterial
        });
        
        // Rotate 35° upward (steeper angle for harder hit)
        this.stopperBody.quaternion.setFromAxisAngle(
            new CANNON.Vec3(1, 0, 0),
            (35 * Math.PI / 180)  // 35° upward
        );
        
        this.world.addBody(this.stopperBody);
        
        // 4. Create Platform (landing area) - Rectangular 12×12
        const platformShape = new CANNON.Box(new CANNON.Vec3(
            perya.platform.width / 2,  // 6
            perya.platform.height / 2, // 0.15
            perya.platform.depth / 2   // 6
        ));
        
        this.tableBody = new CANNON.Body({
            mass: 0, // Static
            shape: platformShape,
            position: new CANNON.Vec3(0, perya.platform.height / 2, 5),  // Platform position
            material: this.tableMaterial
        });
        
        this.world.addBody(this.tableBody);
        
        // 5. Create Platform Walls - U-shaped barrier: LEFT, RIGHT, and FRONT walls
        // No back wall - back stays open
        const wallThickness = 0.15; // Half of 0.3 (for CANNON.Box dimensions)
        const wallHeight = 3.0; // Half of 6 units (for CANNON.Box dimensions)
        const platformHalfWidth = perya.platform.width / 2; // 6
        const platformHalfDepth = perya.platform.depth / 2; // 6
        const platformZOffset = 5; // Platform Z position
        const tableY = perya.platform.height / 2; // 0.15 (table surface Y)
        const wallCenterY = tableY + 3.0; // 0.15 + 3.0 = 3.15 (center of 6-unit wall)
        
        this.walls = [];
        
        // LEFT wall (negative X) - Prevents dice from flying off left side
        // Position X: -(tableWidth/2 + 0.15) = -(6 + 0.15) = -6.15
        // Position Z: 0 (centered) - but we use platformZOffset which is 5
        const leftWallShape = new CANNON.Box(new CANNON.Vec3(wallThickness, wallHeight, platformHalfDepth));
        const leftWall = new CANNON.Body({
            mass: 0, // Static
            shape: leftWallShape,
            position: new CANNON.Vec3(-(platformHalfWidth + 0.15), wallCenterY, platformZOffset), // x = -6.15, z = 5 (centered)
            material: this.wallMaterial
        });
        this.world.addBody(leftWall);
        this.walls.push(leftWall);
        
        // RIGHT wall (positive X) - Prevents dice from flying off right side
        // Position X: tableWidth/2 + 0.15 = 6 + 0.15 = 6.15
        // Position Z: 0 (centered) - but we use platformZOffset which is 5
        const rightWallShape = new CANNON.Box(new CANNON.Vec3(wallThickness, wallHeight, platformHalfDepth));
        const rightWall = new CANNON.Body({
            mass: 0, // Static
            shape: rightWallShape,
            position: new CANNON.Vec3(platformHalfWidth + 0.15, wallCenterY, platformZOffset), // x = 6.15, z = 5 (centered)
            material: this.wallMaterial
        });
        this.world.addBody(rightWall);
        this.walls.push(rightWall);
        
        // FRONT wall (positive Z - player-facing side) - Catches dice bouncing forward
        // Position X: 0 (centered)
        // Position Z: tableDepth/2 + 0.15 = 6 + 0.15 = 6.15 (relative to platform center)
        // Platform is at z = 5, so absolute position: 5 + 6.15 = 11.15
        const frontWallShape = new CANNON.Box(new CANNON.Vec3(platformHalfWidth, wallHeight, wallThickness));
        const frontWall = new CANNON.Body({
            mass: 0, // Static
            shape: frontWallShape,
            position: new CANNON.Vec3(0, wallCenterY, platformZOffset + (platformHalfDepth + 0.15)), // x = 0, z = 11.15
            material: this.wallMaterial
        });
        this.world.addBody(frontWall);
        this.walls.push(frontWall);
        
        // NO BACK WALL - Back stays open
        
        // Create contact material for walls with high restitution (bouncy)
        const wallToDice = new CANNON.ContactMaterial(
            this.wallMaterial,
            this.diceMaterial,
            {
                friction: 0.3,
                restitution: 0.6, // High bounce when dice hit walls
                contactEquationStiffness: 1e8,
                contactEquationRelaxation: 3
            }
        );
        this.world.addContactMaterial(wallToDice);
    }
    
    /**
     * Disable frame collision when rolling starts (no frame - do nothing)
     */
    disableFrame() {
        // No frame to disable - do nothing
    }
    
    /**
     * Re-enable frame collision for next round (no frame - do nothing)
     */
    enableFrame() {
        // No frame to enable - do nothing
    }
    
    /**
     * Step physics simulation
     */
    step(deltaTime) {
        if (this.world) {
        this.world.step(PHYSICS.timeStep, deltaTime, 3);
        }
    }
    
    /**
     * Add a physics body
     */
    addBody(body) {
        this.world.addBody(body);
    }
    
    /**
     * Remove a physics body
     */
    removeBody(body) {
        this.world.removeBody(body);
    }
    
    /**
     * Get world reference
     */
    getWorld() {
        return this.world;
    }
    
    /**
     * Reset world
     */
    reset() {
        // Remove all bodies except static structures
        const bodiesToRemove = [];
        this.world.bodies.forEach(body => {
            if (body !== this.tableBody && 
                body !== this.slideBody && 
                body !== this.stopperBody && 
                !this.frameBodies.includes(body) &&
                !this.walls.includes(body)) {
                bodiesToRemove.push(body);
            }
        });
        
        bodiesToRemove.forEach(body => {
            this.world.removeBody(body);
        });

        // Re-enable frame for next round (all U-shaped frame parts)
        this.enableFrame();
    }
}
