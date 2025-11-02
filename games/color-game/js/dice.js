/**
 * DICE CLASS
 * Handles both visual (THREE.js) and physics (Cannon-es) components
 */

import { COLORS, COLOR_NAMES, FACE_COLORS, DICE, PHYSICS } from './config.js';

export class Dice {
    constructor(scene, world, position, index) {
        this.scene = scene;
        this.world = world;
        this.position = position;
        this.index = index;
        
        // Will be set after roll
        this.finalColor = null;
        this.finalColorName = null;
        
        // State tracking
        this.isSettled = false;
        this.settledTime = 0;
        
        // Create visual and physics
        this.createVisual();
        this.createPhysics();
        this.createGlow();
    }
    
    /**
     * Create THREE.js visual mesh with rounded corners and borders
     */
    createVisual() {
        // Create rounded box geometry
        const geometry = this.createRoundedBoxGeometry(
            DICE.size,
            DICE.size,
            DICE.size,
            DICE.cornerRadius,
            DICE.segments
        );
        
        // Create materials for each face (6 colors)
        const materials = COLOR_NAMES.map(colorName => {
            return new THREE.MeshStandardMaterial({
                color: COLORS[colorName],
                metalness: DICE.metalness,
                roughness: DICE.roughness,
                emissive: COLORS[colorName],
                emissiveIntensity: DICE.emissiveIntensity
            });
        });
        
        // Create mesh
        this.mesh = new THREE.Mesh(geometry, materials);
        this.mesh.position.set(this.position.x, this.position.y, this.position.z);
        this.mesh.castShadow = true;
        this.mesh.receiveShadow = true;
        
        this.scene.add(this.mesh);
        
        // Add white borders
        this.createBorders();
    }
    
    /**
     * Create rounded box geometry (custom)
     */
    createRoundedBoxGeometry(width, height, depth, radius, segments) {
        // Use BoxGeometry as base (we'll add edge rounding via shader/edges)
        // For now, using standard box - can be enhanced with proper rounded geometry
        const geometry = new THREE.BoxGeometry(width, height, depth);
        return geometry;
    }
    
    /**
     * Create white borders around edges
     */
    createBorders() {
        const edges = new THREE.EdgesGeometry(this.mesh.geometry, 15);
        const lineMaterial = new THREE.LineBasicMaterial({
            color: 0xFFFFFF,
            linewidth: 3,
            transparent: true,
            opacity: 0.9
        });
        
        this.borderLines = new THREE.LineSegments(edges, lineMaterial);
        this.mesh.add(this.borderLines);
    }
    
    /**
     * Create glow light for dice
     */
    createGlow() {
        this.glowLight = new THREE.PointLight(0xFFFFFF, 0, 3);
        this.glowLight.position.copy(this.mesh.position);
        this.scene.add(this.glowLight);
    }
    
    /**
     * Create Cannon-es physics body
     */
    createPhysics() {
        const halfSize = DICE.size / 2;
        
        // Create box shape
        const shape = new CANNON.Box(new CANNON.Vec3(halfSize, halfSize, halfSize));
        
        // Create body
        this.body = new CANNON.Body({
            mass: PHYSICS.diceMass,
            shape: shape,
            position: new CANNON.Vec3(this.position.x, this.position.y, this.position.z),
            linearDamping: PHYSICS.diceLinearDamping,
            angularDamping: PHYSICS.diceAngularDamping,
            material: this.world.diceMaterial
        });
        
        // Set initial orientation (random)
        this.body.quaternion.setFromEuler(
            Math.random() * Math.PI,
            Math.random() * Math.PI,
            Math.random() * Math.PI
        );
        
        this.world.addBody(this.body);
    }
    
    /**
     * Apply rolling forces
     */
    roll() {
        // Reset settled state
        this.isSettled = false;
        this.settledTime = 0;
        
        // Apply downward impulse
        this.body.applyImpulse(
            new CANNON.Vec3(0, -PHYSICS.impulseStrength, 0),
            new CANNON.Vec3(0, 0, 0)
        );
        
        // Apply random lateral force
        const lateralX = (Math.random() - 0.5) * PHYSICS.lateralForce;
        const lateralZ = (Math.random() - 0.5) * PHYSICS.lateralForce;
        this.body.applyImpulse(
            new CANNON.Vec3(lateralX, 0, lateralZ),
            new CANNON.Vec3(0, 0, 0)
        );
        
        // Apply random torque (spin)
        this.body.applyTorque(new CANNON.Vec3(
            (Math.random() - 0.5) * PHYSICS.torqueStrength,
            (Math.random() - 0.5) * PHYSICS.torqueStrength,
            (Math.random() - 0.5) * PHYSICS.torqueStrength
        ));
    }
    
    /**
     * Update visual to match physics
     */
    update(deltaTime) {
        // Sync mesh position and rotation with physics body
        this.mesh.position.copy(this.body.position);
        this.mesh.quaternion.copy(this.body.quaternion);
        
        // Update glow light position
        this.glowLight.position.copy(this.body.position);
        this.glowLight.position.y += 1;
        
        // Check if settled
        this.checkSettled(deltaTime);
    }
    
    /**
     * Check if dice has settled
     */
    checkSettled(deltaTime) {
        const linearSpeed = this.body.velocity.length();
        const angularSpeed = this.body.angularVelocity.length();
        
        if (linearSpeed < PHYSICS.sleepSpeedLimit && 
            angularSpeed < PHYSICS.angularThreshold) {
            this.settledTime += deltaTime;
            
            if (this.settledTime >= PHYSICS.sleepTimeLimit) {
                if (!this.isSettled) {
                    this.isSettled = true;
                    this.onSettle();
                }
            }
        } else {
            this.settledTime = 0;
            this.isSettled = false;
        }
    }
    
    /**
     * Called when dice settles
     */
    onSettle() {
        // Stop all movement
        this.body.velocity.set(0, 0, 0);
        this.body.angularVelocity.set(0, 0, 0);
        this.body.sleep();
        
        // Read the top face
        this.readTopFace();
    }
    
    /**
     * Read which face is on top using physics
     */
    readTopFace() {
        const upVector = new CANNON.Vec3(0, 1, 0);
        let maxDot = -Infinity;
        let topFaceIndex = 0;
        
        // Check all 6 faces
        const faceNormals = [
            new CANNON.Vec3(0, 1, 0),   // +Y (top)
            new CANNON.Vec3(0, -1, 0),  // -Y (bottom)
            new CANNON.Vec3(1, 0, 0),   // +X (right)
            new CANNON.Vec3(-1, 0, 0),  // -X (left)
            new CANNON.Vec3(0, 0, 1),   // +Z (front)
            new CANNON.Vec3(0, 0, -1)   // -Z (back)
        ];
        
        faceNormals.forEach((normal, index) => {
            // Transform normal to world space
            const worldNormal = this.body.quaternion.vmult(normal);
            
            // Calculate dot product with up vector
            const dot = worldNormal.dot(upVector);
            
            if (dot > maxDot) {
                maxDot = dot;
                topFaceIndex = index;
            }
        });
        
        // Get color name from face index
        this.finalColorName = FACE_COLORS[topFaceIndex];
        this.finalColor = COLORS[this.finalColorName];
        
        // Update visual to show final color more prominently
        this.highlightFinalColor();
    }
    
    /**
     * Highlight the final color
     */
    highlightFinalColor() {
        // Set all faces to final color for clarity
        this.mesh.material.forEach(mat => {
            mat.color.setHex(this.finalColor);
            mat.emissive.setHex(this.finalColor);
            mat.emissiveIntensity = 0.5;
        });
        
        // Update glow light
        this.glowLight.color.setHex(this.finalColor);
        this.glowLight.intensity = 2;
    }
    
    /**
     * Pulse glow effect
     */
    pulseGlow(time) {
        const pulse = Math.sin(time * Math.PI * 2) * 0.5 + 1.5;
        this.glowLight.intensity = pulse;
        
        this.mesh.material.forEach(mat => {
            mat.emissiveIntensity = pulse * 0.3;
        });
    }
    
    /**
     * Get result
     */
    getResult() {
        return {
            color: this.finalColorName,
            colorHex: this.finalColor,
            isSettled: this.isSettled
        };
    }
    
    /**
     * Cleanup
     */
    dispose() {
        this.scene.remove(this.mesh);
        this.scene.remove(this.glowLight);
        this.world.removeBody(this.body);
        
        this.mesh.geometry.dispose();
        this.mesh.material.forEach(mat => mat.dispose());
        this.borderLines.geometry.dispose();
        this.borderLines.material.dispose();
    }
}
