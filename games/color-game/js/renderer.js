/**
 * RENDERER MODULE
 * Handles THREE.js scene, camera, lights, and rendering
 * 
 * WORLD CONVENTION (LOCKED):
 * - Up = +Y
 * - Front (toward player/camera) = -Z
 * - Right = +X
 * - Gravity = -Y (down)
 * - Ramp slopes downhill toward -Z (front/platform side)
 */

import { CAMERA, LIGHTS, PHYSICS } from './config.js';

export class Renderer {
    constructor(container) {
        this.container = container;
        this.scene = null;
        this.camera = null;
        this.renderer = null;
        this.table = null;
        this.isSideView = false;
        this.frontViewPos = { x: 0, y: 8, z: 12 };
        this.frontViewLook = { x: 0, y: 3, z: 0 };
        this.sideViewPos = { x: 15, y: 6, z: 0 };
        this.sideViewLook = { x: 0, y: 3, z: 0 };
    }
    
    /**
     * Initialize THREE.js scene
     */
    init() {
        // Create scene
        this.scene = new THREE.Scene();
        this.scene.background = new THREE.Color('#f7e7c5'); // Beige/cream background
        
        // STEP 1: Add world axes helper at origin to visualize world convention
        // Red = X (right), Green = Y (up), Blue = Z (back = +Z, front = -Z)
        const worldAxes = new THREE.AxesHelper(3);
        this.scene.add(worldAxes);
        console.log('World axes helper added at origin. Red=X, Green=Y, Blue=Z');
        this.scene.fog = new THREE.Fog('#f7e7c5', 15, 35); // Match fog to background
        
        // Create camera
        this.createCamera();
        
        // Create renderer
        this.createRenderer();
        
        // Create lights
        this.createLights();
        
        // Create table visual
        this.createTable();
        
        // Handle window resize (handled in main.js with debouncing)
        // Note: Resize is handled globally in main.js to avoid duplicate handlers
        
        return {
            scene: this.scene,
            camera: this.camera,
            renderer: this.renderer
        };
    }
    
    /**
     * Create camera
     */
    createCamera() {
        // Detect mobile and adjust FOV for better view
        const isMobile = window.innerWidth < 768;
        const width = this.container.clientWidth || window.innerWidth;
        const height = this.container.clientHeight || window.innerHeight;
        const aspect = width / height;
        
        // Wider FOV on mobile, especially for portrait mode
        let fov = CAMERA.fov;
        if (isMobile) {
            // Portrait mode (tall screen) needs even wider FOV
            if (aspect < 1) {
                fov = 85; // Very wide for portrait
            } else {
                fov = 75; // Wider for landscape
            }
        }
        
        this.camera = new THREE.PerspectiveCamera(
            fov,
            aspect,
            CAMERA.near,
            CAMERA.far
        );
        
        // Adjust camera position for mobile - much further back and higher
        if (isMobile) {
            if (aspect < 1) {
                // Portrait mode - camera further back and higher
                this.camera.position.set(0, 10, 16);
            } else {
                // Landscape mode
                this.camera.position.set(0, 8, 14);
            }
        } else {
        this.camera.position.set(
            CAMERA.defaultPosition.x,
            CAMERA.defaultPosition.y,
            CAMERA.defaultPosition.z
        );
        }
        
        // Store initial positions for view toggle
        this.frontViewPos = { 
            x: this.camera.position.x, 
            y: this.camera.position.y, 
            z: this.camera.position.z 
        };
        this.frontViewLook = { 
            x: CAMERA.lookAt.x, 
            y: CAMERA.lookAt.y, 
            z: CAMERA.lookAt.z 
        };
        this.sideViewPos = { x: 15, y: 6, z: 0 };
        this.sideViewLook = { x: 0, y: 3, z: 0 };
        
        this.camera.lookAt(
            CAMERA.lookAt.x,
            CAMERA.lookAt.y,
            CAMERA.lookAt.z
        );
    }
    
    /**
     * Create WebGL renderer
     */
    createRenderer() {
        this.renderer = new THREE.WebGLRenderer({
            antialias: true,
            alpha: false
        });
        
        // Use container dimensions instead of window for proper mobile fit
        const width = this.container.clientWidth || window.innerWidth;
        const height = this.container.clientHeight || window.innerHeight;
        this.renderer.setSize(width, height);
        this.renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
        this.renderer.shadowMap.enabled = true;
        this.renderer.shadowMap.type = THREE.PCFSoftShadowMap;
        this.renderer.outputEncoding = THREE.sRGBEncoding;
        this.renderer.toneMapping = THREE.ACESFilmicToneMapping;
        this.renderer.toneMappingExposure = 0.6; // Lower exposure = richer, darker colors
        
        this.container.appendChild(this.renderer.domElement);
    }
    
    /**
     * Create all lights
     */
    createLights() {
        // Ambient light
        const ambient = new THREE.AmbientLight(
            LIGHTS.ambient.color,
            LIGHTS.ambient.intensity
        );
        this.scene.add(ambient);
        
        // Main directional light (sun)
        const directional = new THREE.DirectionalLight(
            LIGHTS.directional.color,
            LIGHTS.directional.intensity
        );
        directional.position.set(
            LIGHTS.directional.position.x,
            LIGHTS.directional.position.y,
            LIGHTS.directional.position.z
        );
        directional.castShadow = true;
        
        // Shadow settings
        directional.shadow.mapSize.width = 2048;
        directional.shadow.mapSize.height = 2048;
        directional.shadow.camera.near = 0.5;
        directional.shadow.camera.far = 50;
        directional.shadow.camera.left = -10;
        directional.shadow.camera.right = 10;
        directional.shadow.camera.top = 10;
        directional.shadow.camera.bottom = -10;
        directional.shadow.bias = -0.0001;
        
        this.scene.add(directional);
        
        // Neon carnival lights
        const neon1 = new THREE.PointLight(
            LIGHTS.neon1.color,
            LIGHTS.neon1.intensity,
            LIGHTS.neon1.distance
        );
        neon1.position.set(
            LIGHTS.neon1.position.x,
            LIGHTS.neon1.position.y,
            LIGHTS.neon1.position.z
        );
        this.scene.add(neon1);
        
        const neon2 = new THREE.PointLight(
            LIGHTS.neon2.color,
            LIGHTS.neon2.intensity,
            LIGHTS.neon2.distance
        );
        neon2.position.set(
            LIGHTS.neon2.position.x,
            LIGHTS.neon2.position.y,
            LIGHTS.neon2.position.z
        );
        this.scene.add(neon2);
        
        const neon3 = new THREE.PointLight(
            LIGHTS.neon3.color,
            LIGHTS.neon3.intensity,
            LIGHTS.neon3.distance
        );
        neon3.position.set(
            LIGHTS.neon3.position.x,
            LIGHTS.neon3.position.y,
            LIGHTS.neon3.position.z
        );
        this.scene.add(neon3);
        
        // Store lights for potential animation
        this.lights = {
            ambient,
            directional,
            neon1,
            neon2,
            neon3
        };
    }
    
    /**
     * Create perya mechanism structure (frame, slide, stopper, platform)
     */
    createTable() {
        // Debug: Check PHYSICS import
        console.log('PHYSICS object:', PHYSICS);
        console.log('PHYSICS.perya:', PHYSICS?.perya);
        
        // Safety check
        if (!PHYSICS || !PHYSICS.perya) {
            console.error('PHYSICS.perya is not defined. Check config.js exports.');
            console.error('Available PHYSICS keys:', PHYSICS ? Object.keys(PHYSICS) : 'PHYSICS is undefined');
            // Fallback: create simple platform
            const platformGeom = new THREE.BoxGeometry(8, 0.3, 8);
            const platformMaterial = new THREE.MeshStandardMaterial({
                color: 0xFFD700,
                metalness: 0.2,
                roughness: 0.6
            });
            this.table = new THREE.Mesh(platformGeom, platformMaterial);
            this.table.position.set(0, 0.15, 0);
            this.scene.add(this.table);
            return;
        }
        
        const perya = PHYSICS.perya;
        
        // Calculate slide start position (top of slide where dice will be)
        const slideStartY = 6;
        const slideStartZ = -7;
        
        // No frame - dice are frozen at slide top
        this.frameGroup = null;
        this.frameParts = [];
        this.frame = null;
        
        // 2. Create Slide Ramp (45° angled ramp - use BoxGeometry for visibility)
        const slideWidth = perya.slide.width;
        const slideThickness = 0.3; // Make slide visible with thickness
        
        // STEP 3: Define ramp edges FIRST to calculate correct length
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
        
        // Create slide as a thick box that will be rotated
        const slideGeom = new THREE.BoxGeometry(slideWidth, slideThickness, slideLength);
        
        // Dark brown wood material
        const slideMaterial = new THREE.MeshStandardMaterial({
            color: 0x4E342E, // Slightly darker brown
            metalness: 0.05,
            roughness: 0.4, // Smooth polished wood
            side: THREE.DoubleSide
        });
        
        this.slide = new THREE.Mesh(slideGeom, slideMaterial);
        
        // Position slide so it goes from original start (z=2) to end (z=-12)
        // After 45° X rotation: local +Z points toward world -Z (front) and down
        const slideMidY = (slideStartY + slideEndY) / 2;
        const slideMidZ = -3; // User-specified position
        
        // STEP 2 & 4: Set ramp transform
        // Neutralize first, then apply correct rotation
        this.slide.position.set(0, slideMidY, slideMidZ);
        this.slide.rotation.set(0, 0, 0); // Start with zero rotation
        this.slide.scale.set(1, 1, 1); // Ensure no scale
        
        // Rotate to tilt slide down (facing front, toward camera)
        this.slide.rotation.x = Math.PI / 3; // +60° around X (tilt down toward -Z/front)
        
        this.slide.receiveShadow = true;
        this.slide.castShadow = true;
        this.scene.add(this.slide);
        
        console.log('=== SLIDE POSITION DEBUG ===');
        console.log('Slide start position:', 'z =', slideStartZ, 'y =', slideStartY);
        console.log('Slide end target:', 'z =', slideEndZ, 'y =', slideEndY);
        console.log('Slide midpoint:', 'z =', slideMidZ, 'y =', slideMidY);
        console.log('Platform center: z = 0');
        console.log('Platform back edge: z =', perya.platform.depth / 2, '(should be +6)');
        console.log('Platform front edge: z =', -perya.platform.depth / 2, '(should be -6)');
        console.log('Platform left edge: x =', -perya.platform.width / 2, '(should be -6)');
        console.log('Platform right edge: x =', perya.platform.width / 2, '(should be +6)');
        console.log('Slide end Z:', slideEndZ, '(should be +4 for back edge)');
        console.log('Slide length:', slideLength.toFixed(2));
        console.log('Slide rotation:', (this.slide.rotation.x * 180 / Math.PI).toFixed(1), 'degrees');
        console.log('===========================');
        
        // 3. Create Stopper Wedge (at bottom of slide) - Very bouncy hump
        const stopperHeight = perya.stopper.height; // 1.0 (taller obstacle)
        const stopperWidth = perya.stopper.width; // 5
        const stopperDepth = perya.stopper.depth; // 1.0 (thicker for more contact)
        
        const stopperGeom = new THREE.BoxGeometry(
            stopperWidth,
            stopperHeight,
            stopperDepth
        );
        
        const stopperMaterial = new THREE.MeshStandardMaterial({
            color: 0x4E342E, // Slightly darker wood for contrast
            metalness: 0.1,
            roughness: 0.3
        });
        
        this.stopper = new THREE.Mesh(stopperGeom, stopperMaterial);
        // Position stopper at the bottom of slide where it meets platform
        // Slide ends at z=-12, y=0.15
        // Platform is at y=0, extends from z=-5 to z=+5
        // Position stopper at slide end, sitting on platform
        const stopperY = 2; // User-specified position
        const stopperZ = -2; // User-specified position
        
        this.stopper.position.set(0, stopperY, stopperZ);
        this.stopper.rotation.x = 35 * Math.PI / 180; // 35° upward (steeper angle for harder hit)
        this.stopper.receiveShadow = true;
        this.stopper.castShadow = true;
        this.scene.add(this.stopper);
        
        console.log('Stopper created at:', this.stopper.position, 'rotation:', (this.stopper.rotation.x * 180 / Math.PI).toFixed(1), 'degrees');
        
        // 4. Create Platform (landing area) - Bright yellow
        const platformGeom = new THREE.BoxGeometry(
            perya.platform.width,  // 12 units wide
            perya.platform.height, // 0.3 units thick
            perya.platform.depth   // 12 units deep
        );
        
        // Bright yellow with radial gradient effect
        const platformMaterial = new THREE.MeshStandardMaterial({
            color: 0xFFD700, // Bright yellow
            metalness: 0.2,
            roughness: 0.6,
            side: THREE.DoubleSide
        });
        
        this.table = new THREE.Mesh(platformGeom, platformMaterial);
        // Platform at ground level (y = 0), centered at origin
        const platformZOffset = 5; // Platform position
        this.table.position.set(0, perya.platform.height / 2, platformZOffset);
        this.table.receiveShadow = true;
        this.table.castShadow = false;
        this.scene.add(this.table);
        
        console.log('Platform created at:', this.table.position);
        console.log('Platform dimensions:', perya.platform.width, '×', perya.platform.depth);
        
        // 5. Create Platform Walls (cyan/turquoise) - Adjusted for new platform size
        const wallHeight = perya.platform.wallHeight; // 1.5 (front/back walls)
        const sideWallHeight = perya.platform.sideWallHeight; // 2.0 (left/right walls)
        const wallThickness = perya.platform.wallThickness; // 0.4
        const platformHalfWidth = perya.platform.width / 2; // 6 (12/2)
        const platformHalfDepth = perya.platform.depth / 2; // 6 (12/2)
        
        const wallMaterial = new THREE.MeshStandardMaterial({
            color: 0x00CED1, // Cyan/turquoise
            metalness: 0.3,
            roughness: 0.4,
            side: THREE.DoubleSide
        });
        
        this.walls = [];
        
        // Front wall (positive Z - back edge)
        const frontWallGeom = new THREE.BoxGeometry(
            perya.platform.width + wallThickness * 2, // 12 + 0.8 = 12.8
            wallHeight, // 1.5
            wallThickness // 0.4
        );
        const frontWall = new THREE.Mesh(frontWallGeom, wallMaterial);
        frontWall.position.set(0, wallHeight / 2, platformZOffset + platformHalfDepth + wallThickness / 2); // z = 5 + 6 + 0.2 = 11.2
        frontWall.receiveShadow = true;
        frontWall.castShadow = true;
        this.scene.add(frontWall);
        this.walls.push(frontWall);
        
        // Back wall (negative Z - front edge)
        const backWall = new THREE.Mesh(frontWallGeom, wallMaterial);
        backWall.position.set(0, wallHeight / 2, platformZOffset - (platformHalfDepth + wallThickness / 2)); // z = 5 - 6.2 = -1.2
        backWall.receiveShadow = true;
        backWall.castShadow = true;
        this.scene.add(backWall);
        this.walls.push(backWall);
        
        // Left wall (negative X)
        const sideWallGeom = new THREE.BoxGeometry(
            wallThickness, // 0.4
            sideWallHeight, // 2.0 (taller than front/back walls)
            perya.platform.depth // 12
        );
        const leftWall = new THREE.Mesh(sideWallGeom, wallMaterial);
        leftWall.position.set(-(platformHalfWidth + wallThickness / 2), sideWallHeight / 2, platformZOffset); // x = -6.2, z = 5
        leftWall.receiveShadow = true;
        leftWall.castShadow = true;
        this.scene.add(leftWall);
        this.walls.push(leftWall);
        
        // Right wall (positive X)
        const rightWall = new THREE.Mesh(sideWallGeom, wallMaterial);
        rightWall.position.set(platformHalfWidth + wallThickness / 2, sideWallHeight / 2, platformZOffset); // x = 6.2, z = 5
        rightWall.receiveShadow = true;
        rightWall.castShadow = true;
        this.scene.add(rightWall);
        this.walls.push(rightWall);
    }
    
    /**
     * Fade out frame when rolling starts (no frame - do nothing)
     */
    fadeOutFrame() {
        // No frame to fade - do nothing
    }
    
    /**
     * Reset frame visibility for next round (no frame - do nothing)
     */
    resetFrame() {
        // No frame to reset - do nothing
    }
    
    /**
     * Render scene
     */
    render() {
        this.renderer.render(this.scene, this.camera);
    }
    
    /**
     * Handle window resize
     */
    onWindowResize() {
        // Use container dimensions for proper mobile fit
        const width = this.container.clientWidth || window.innerWidth;
        const height = this.container.clientHeight || window.innerHeight;
        const aspect = width / height;
        
        // Adjust camera FOV and position for mobile based on aspect ratio
        const isMobile = width < 768;
        if (isMobile) {
            // Portrait mode (tall screen) needs even wider FOV
            if (aspect < 1) {
                this.camera.fov = 85;
                this.camera.position.set(0, 10, 16);
            } else {
                this.camera.fov = 75;
                this.camera.position.set(0, 8, 14);
            }
        } else {
            this.camera.fov = CAMERA.fov;
            this.camera.position.set(
                CAMERA.defaultPosition.x,
                CAMERA.defaultPosition.y,
                CAMERA.defaultPosition.z
            );
        }
        
        this.camera.aspect = aspect;
        this.camera.updateProjectionMatrix();
        this.renderer.setSize(width, height);
    }
    
    /**
     * Toggle between front and side view
     */
    toggleView() {
        this.isSideView = !this.isSideView;
        this.updateCameraView();
        return this.isSideView;
    }
    
    /**
     * Update camera position based on current view mode
     */
    updateCameraView() {
        if (this.isSideView) {
            this.camera.position.set(
                this.sideViewPos.x,
                this.sideViewPos.y,
                this.sideViewPos.z
            );
            this.camera.lookAt(
                this.sideViewLook.x,
                this.sideViewLook.y,
                this.sideViewLook.z
            );
        } else {
            this.camera.position.set(
                this.frontViewPos.x,
                this.frontViewPos.y,
                this.frontViewPos.z
            );
            this.camera.lookAt(
                this.frontViewLook.x,
                this.frontViewLook.y,
                this.frontViewLook.z
            );
        }
    }
    
    /**
     * Camera shake effect
     */
    shakeCamera(intensity, duration = 300) {
        const originalPosition = this.camera.position.clone();
        let elapsed = 0;
        
        const shake = () => {
            if (elapsed < duration) {
                this.camera.position.x = originalPosition.x + (Math.random() - 0.5) * intensity;
                this.camera.position.y = originalPosition.y + (Math.random() - 0.5) * intensity;
                this.camera.position.z = originalPosition.z + (Math.random() - 0.5) * intensity;
                
                elapsed += 16;
                requestAnimationFrame(shake);
            } else {
                this.camera.position.copy(originalPosition);
            }
        };
        
        shake();
    }
    
    /**
     * Get scene reference
     */
    getScene() {
        return this.scene;
    }
    
    /**
     * Get camera reference
     */
    getCamera() {
        return this.camera;
    }
    
    /**
     * Get renderer reference
     */
    getRenderer() {
        return this.renderer;
    }
}
