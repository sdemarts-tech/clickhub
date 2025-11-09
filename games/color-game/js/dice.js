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
     * Uses 6 separate rounded square faces for clean material mapping
     */
    createVisual() {
        // Create a container group for all 6 faces
        const diceGroup = new THREE.Group();
        
        // Face order: [green (right), yellow (left), red (top), blue (bottom), white (front), pink (back)]
        const faceConfigs = [
            { color: 'green', position: [DICE.size / 2, 0, 0], rotation: [0, Math.PI / 2, 0], name: 'right' },
            { color: 'yellow', position: [-DICE.size / 2, 0, 0], rotation: [0, -Math.PI / 2, 0], name: 'left' },
            { color: 'red', position: [0, DICE.size / 2, 0], rotation: [-Math.PI / 2, 0, 0], name: 'top' },
            { color: 'blue', position: [0, -DICE.size / 2, 0], rotation: [Math.PI / 2, 0, 0], name: 'bottom' },
            { color: 'white', position: [0, 0, DICE.size / 2], rotation: [0, 0, 0], name: 'front' },
            { color: 'pink', position: [0, 0, -DICE.size / 2], rotation: [0, Math.PI, 0], name: 'back' }
        ];
        
        // Create each face as a separate rounded square
        faceConfigs.forEach(faceConfig => {
            const faceGeometry = this.createRoundedSquareGeometry(DICE.size, DICE.size, DICE.cornerRadius, DICE.segments);
            const exactColor = new THREE.Color(COLORS[faceConfig.color]);
            const faceMaterial = new THREE.MeshBasicMaterial({
                color: exactColor,
                side: THREE.FrontSide
            });
            
            const faceMesh = new THREE.Mesh(faceGeometry, faceMaterial);
            faceMesh.position.set(...faceConfig.position);
            faceMesh.rotation.set(...faceConfig.rotation);
            faceMesh.castShadow = true;
            faceMesh.receiveShadow = true;
            
            diceGroup.add(faceMesh);
        });
        
        // Set the group as the main mesh
        this.mesh = diceGroup;
        this.mesh.position.set(this.position.x, this.position.y, this.position.z);
        
        this.scene.add(this.mesh);
        
        // Add white borders
        this.createBorders();
    }
    
    /**
     * Create rounded square geometry for a single face
     * Creates a thin rounded square with iOS app icon style rounded corners
     */
    createRoundedSquareGeometry(width, height, radius, segments) {
        const shape = new THREE.Shape();
        const w = width / 2;
        const h = height / 2;
        const r = Math.min(radius, w * 0.8, h * 0.8);
        
        // Start from top-left (after rounding)
        shape.moveTo(-w + r, h);
        
        // Top edge
        shape.lineTo(w - r, h);
        
        // Top-right corner (rounded)
        shape.quadraticCurveTo(w, h, w, h - r);
        
        // Right edge
        shape.lineTo(w, -h + r);
        
        // Bottom-right corner (rounded)
        shape.quadraticCurveTo(w, -h, w - r, -h);
        
        // Bottom edge
        shape.lineTo(-w + r, -h);
        
        // Bottom-left corner (rounded)
        shape.quadraticCurveTo(-w, -h, -w, -h + r);
        
        // Left edge
        shape.lineTo(-w, h - r);
        
        // Top-left corner (rounded)
        shape.quadraticCurveTo(-w, h, -w + r, h);
        
        shape.closePath();
        
        // Extrude to create thin face (0.01 depth for each face)
        const extrudeSettings = {
            depth: 0.01,
            bevelEnabled: false,
            curveSegments: segments,
            steps: 1
        };
        
        const geometry = new THREE.ExtrudeGeometry(shape, extrudeSettings);
        geometry.center();
        
        return geometry;
    }
    
    /**
     * Create rounded box geometry (legacy method - kept for compatibility)
     */
    createRoundedBoxGeometry(width, height, depth, radius, segments) {
        // This method is no longer used, but kept for compatibility
        // The new implementation uses 6 separate rounded squares
        const geometry = new THREE.BoxGeometry(width, height, depth);
        return geometry;
    }
    
    /**
     * Create thick white borders with 6 layers for maximum visibility
     * Updated to work with Group structure (6 separate face meshes)
     */
    createBorders() {
        // Since mesh is now a Group, create borders for each face
        const borderLayers = [
            { scale: 1.00, opacity: 1.0, renderOrder: 999, name: 'borderLines' },
            { scale: 1.02, opacity: 1.0, renderOrder: 998, name: 'thickBorderLines' },
            { scale: 1.04, opacity: 0.95, renderOrder: 997, name: 'glowBorderLines' },
            { scale: 1.06, opacity: 0.9, renderOrder: 996, name: 'outerBorderLines' },
            { scale: 1.08, opacity: 0.85, renderOrder: 995, name: 'borderLayer5' },
            { scale: 1.10, opacity: 0.8, renderOrder: 994, name: 'borderLayer6' }
        ];
        
        borderLayers.forEach((layer, index) => {
            const borderGroup = new THREE.Group();
            
            // Create borders for each face in the dice group
            this.mesh.children.forEach((faceMesh, faceIndex) => {
                if (faceMesh instanceof THREE.Mesh) {
                    const edges = new THREE.EdgesGeometry(faceMesh.geometry, DICE.borderEdgeThreshold || 1.0);
                    const lineMaterial = new THREE.LineBasicMaterial({
                        color: 0xFFFFFF,
                        transparent: true,
                        opacity: layer.opacity,
                        depthTest: true,
                        depthWrite: false
                    });
                    const borderLines = new THREE.LineSegments(edges, lineMaterial);
                    borderLines.scale.setScalar(layer.scale);
                    borderLines.renderOrder = layer.renderOrder;
                    
                    // Match the face's position and rotation
                    borderLines.position.copy(faceMesh.position);
                    borderLines.rotation.copy(faceMesh.rotation);
                    
                    borderGroup.add(borderLines);
                }
            });
            
            // Store reference to border group
            this[layer.name] = borderGroup;
            this.mesh.add(borderGroup);
        });
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
     * Create Cannon-es physics body (wooden cube)
     */
    createPhysics() {
        const halfSize = DICE.size / 2;
        
        // Safety check with fallback
        let wooden;
        if (!PHYSICS || !PHYSICS.woodenCube) {
            console.warn('PHYSICS.woodenCube not found, using defaults');
            wooden = {
                mass: 1.8,
                friction: 0.35,
                restitution: 0.35,
                linearDamping: 0.2,
                angularDamping: 0.25
            };
        } else {
            wooden = PHYSICS.woodenCube;
        }
        
        // Create box shape
        const shape = new CANNON.Box(new CANNON.Vec3(halfSize, halfSize, halfSize));
        
        // Create body with wooden cube properties
        this.body = new CANNON.Body({
            mass: wooden.mass,
            shape: shape,
            position: new CANNON.Vec3(this.position.x, this.position.y, this.position.z),
            linearDamping: wooden.linearDamping,
            angularDamping: wooden.angularDamping,
            material: this.world.diceMaterial,
            type: CANNON.Body.KINEMATIC  // Frozen until released
        });
        
        // Set orientation to match 60° slide angle
        // Slide is rotated 60° around X axis, so dice should match that rotation
        // This makes dice sit flat on the angled slide surface
        this.body.quaternion.setFromEuler(-Math.PI / 3, 0, 0); // -60° X rotation to match slide
        
        // Start with zero velocity (frozen)
        this.body.velocity.set(0, 0, 0);
        this.body.angularVelocity.set(0, 0, 0);
        
        // Initialize final color to prevent undefined results
        this.finalColorName = null;
        this.finalColor = null;
        
        this.world.addBody(this.body);
    }
    
    /**
     * Release cube (frame disappears, gravity takes over)
     * No manual forces needed - physics handles slide, bounce, settle
     */
    roll() {
        // Change from kinematic (frozen) to dynamic (physics-enabled)
        this.body.type = CANNON.Body.DYNAMIC;
        this.body.updateMassProperties();
        
        // Reset settled state
        this.isSettled = false;
        this.settledTime = 0;
        
        // Wake up dice
        this.body.wakeUp();
        
        // Reset velocities to zero first (clean start)
        this.body.velocity.set(0, 0, 0);
        this.body.angularVelocity.set(0, 0, 0);
        
        // Reduced random force variation multipliers (less extreme)
        const forceMultiplier = 0.7 + Math.random() * 0.6; // 0.7 to 1.3x (reduced range)
        const spinMultiplier = 0.8 + Math.random() * 0.4; // 0.8 to 1.2x (reduced range)
        
        // Reduced base force strengths
        const impulseStrength = 2 * forceMultiplier; // REDUCED from 4
        const lateralForce = 1.5 * forceMultiplier; // REDUCED from 3
        const torqueStrength = 6 * spinMultiplier; // REDUCED from 12
        
        // Small downward impulse (helps start fall, but not too strong)
        const downwardForce = -impulseStrength * (0.5 + Math.random() * 0.3); // Reduced strength
        this.body.applyImpulse(
            new CANNON.Vec3(0, downwardForce, 0),
            new CANNON.Vec3(0, 0, 0)
        );
        
        // Small lateral forces (NO upward component - dice should fall down!)
        const lateralX = (Math.random() - 0.5) * lateralForce * 0.5; // REDUCED - less sideways push
        const lateralZ = (Math.random() - 0.5) * lateralForce * 0.5; // REDUCED - less forward/back push
        // REMOVED lateralY upward component - dice should fall naturally
        
        this.body.applyImpulse(
            new CANNON.Vec3(lateralX, 0, lateralZ), // No upward force!
            new CANNON.Vec3(0, 0, 0)
        );
        
        // Set random initial angular velocity for rotation (replaces applyTorque)
        // This creates the spinning effect - CANNON.js doesn't have applyTorque method
        this.body.angularVelocity.set(
            (Math.random() - 0.5) * torqueStrength, // Random spin on X axis
            (Math.random() - 0.5) * torqueStrength, // Random spin on Y axis
            (Math.random() - 0.5) * torqueStrength  // Random spin on Z axis
        );
        
        // Keep initial rotation matching slide angle (don't randomize completely)
        // Small random variation from the slide angle
        const slideAngleVariation = (Math.random() - 0.5) * 0.3; // ±0.15 radians (~±9°)
        this.body.quaternion.setFromEuler(-Math.PI / 3 + slideAngleVariation, 0, Math.random() * Math.PI * 2);
        
        console.log(`Dice ${this.index} rolled - type: ${this.body.type}, position: (${this.body.position.x.toFixed(2)}, ${this.body.position.y.toFixed(2)}, ${this.body.position.z.toFixed(2)})`);
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
        
        // Auto-correct orientation to flat if enabled and settling
        // Delayed to 0.5s to let dice bounce naturally first, then snap flat
        if (PHYSICS.autoCorrect && this.settledTime > 0.5) {
            this.autoCorrectOrientation();
        }
        
        // Check if settled
        this.checkSettled(deltaTime);
    }
    
    /**
     * Auto-correct dice orientation to flat position
     * Checks which face is closest to pointing up and smoothly rotates to flat
     */
    autoCorrectOrientation() {
        if (!PHYSICS.autoCorrect) return;
        
        // Get current rotation
        const quaternion = this.body.quaternion;
        
        // Calculate which face is closest to pointing up (+Y)
        // Cube faces in order: right(+X), left(-X), top(+Y), bottom(-Y), front(+Z), back(-Z)
        const up = new CANNON.Vec3(0, 1, 0);
        const faces = [
            new CANNON.Vec3(1, 0, 0),   // Right
            new CANNON.Vec3(-1, 0, 0),  // Left
            new CANNON.Vec3(0, 1, 0),   // Top
            new CANNON.Vec3(0, -1, 0),  // Bottom
            new CANNON.Vec3(0, 0, 1),   // Front
            new CANNON.Vec3(0, 0, -1)   // Back
        ];
        
        // Transform face directions by current rotation
        let maxDot = -1;
        let bestFaceIndex = 2; // Default to top
        
        faces.forEach((face, index) => {
            const rotatedFace = quaternion.vmult(face);
            const dot = up.dot(rotatedFace);
            if (dot > maxDot) {
                maxDot = dot;
                bestFaceIndex = index;
            }
        });
        
        // Calculate angle from vertical (0 = perfectly flat, 90 = vertical)
        const angleFromVertical = Math.acos(Math.max(-1, Math.min(1, maxDot))) * (180 / Math.PI);
        
        // Only correct if within threshold and not already flat
        if (angleFromVertical <= PHYSICS.correctThreshold && angleFromVertical > 0.5) {
            // Calculate target quaternion to align the best face with up
            let targetQuaternion;
            
            // Map face indices to rotations that make that face point up
            const rotations = [
                new CANNON.Quaternion().setFromAxisAngle(new CANNON.Vec3(0, 0, 1), -Math.PI / 2), // Right -> Top
                new CANNON.Quaternion().setFromAxisAngle(new CANNON.Vec3(0, 0, 1), Math.PI / 2),  // Left -> Top
                new CANNON.Quaternion(0, 0, 0, 1),  // Top (already up)
                new CANNON.Quaternion().setFromAxisAngle(new CANNON.Vec3(1, 0, 0), Math.PI),     // Bottom -> Top
                new CANNON.Quaternion().setFromAxisAngle(new CANNON.Vec3(1, 0, 0), -Math.PI / 2), // Front -> Top
                new CANNON.Quaternion().setFromAxisAngle(new CANNON.Vec3(1, 0, 0), Math.PI / 2)   // Back -> Top
            ];
            
            targetQuaternion = rotations[bestFaceIndex];
            
            // Smoothly interpolate towards target
            const correctionSpeed = 5.0; // Speed of correction
            const currentQuat = this.body.quaternion.clone();
            const targetQuat = targetQuaternion;
            
            // Manual quaternion slerp (CANNON.js quaternions don't have slerp method)
            const slerpFactor = Math.min(1.0, correctionSpeed * (1.0 / 60.0)); // Assuming 60fps
            
            // Calculate dot product to check if quaternions are in same direction
            let dot = currentQuat.x * targetQuat.x + currentQuat.y * targetQuat.y + 
                      currentQuat.z * targetQuat.z + currentQuat.w * targetQuat.w;
            
            // If dot < 0, negate one quaternion for shortest path
            if (dot < 0) {
                targetQuat.x = -targetQuat.x;
                targetQuat.y = -targetQuat.y;
                targetQuat.z = -targetQuat.z;
                targetQuat.w = -targetQuat.w;
                dot = -dot;
            }
            
            // Perform slerp
            let result = new CANNON.Quaternion();
            if (Math.abs(dot) > 0.9995) {
                // Quaternions are very close, use linear interpolation
                result.x = currentQuat.x + (targetQuat.x - currentQuat.x) * slerpFactor;
                result.y = currentQuat.y + (targetQuat.y - currentQuat.y) * slerpFactor;
                result.z = currentQuat.z + (targetQuat.z - currentQuat.z) * slerpFactor;
                result.w = currentQuat.w + (targetQuat.w - currentQuat.w) * slerpFactor;
                result.normalize();
            } else {
                // Spherical interpolation
                const theta = Math.acos(Math.abs(dot));
                const sinTheta = Math.sin(theta);
                const w1 = Math.sin((1 - slerpFactor) * theta) / sinTheta;
                const w2 = Math.sin(slerpFactor * theta) / sinTheta;
                
                result.x = w1 * currentQuat.x + w2 * targetQuat.x;
                result.y = w1 * currentQuat.y + w2 * targetQuat.y;
                result.z = w1 * currentQuat.z + w2 * targetQuat.z;
                result.w = w1 * currentQuat.w + w2 * targetQuat.w;
            }
            
            this.body.quaternion.copy(result);
            
            // Reduce angular velocity to stop spinning
            this.body.angularVelocity.scale(0.7);
        }
    }
    
    /**
     * Check if dice has settled
     */
    checkSettled(deltaTime) {
        const linearSpeed = this.body.velocity.length();
        const angularSpeed = this.body.angularVelocity.length();
        
        // Use config values for stricter thresholds
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
            // Reset immediately if any movement detected
            this.settledTime = 0;
            this.isSettled = false;
        }
    }
    
    /**
     * Called when dice settles
     */
    onSettle() {
        // Stop all movement completely
        this.body.velocity.set(0, 0, 0);
        this.body.angularVelocity.set(0, 0, 0);
        this.body.sleep();
        
        // Wait a moment to ensure physics is fully synced before reading
        // This ensures the quaternion is stable
        setTimeout(() => {
            this.readTopFace();
        }, 100); // 100ms delay to ensure physics is stable
    }
    
    /**
     * Read which face is on top using physics
     */
    readTopFace() {
        const upVector = new CANNON.Vec3(0, 1, 0);
        let maxDot = -Infinity;
        let topFaceIndex = 0;
        
        // Check all 6 faces
        // THREE.js BoxGeometry face order: right, left, top, bottom, front, back
        // Materials order: ['green', 'yellow', 'red', 'blue', 'white', 'pink']
        // FACE_COLORS now matches material indices directly: 0=green, 1=yellow, 2=red, 3=blue, 4=white, 5=pink
        const faceNormals = [
            new CANNON.Vec3(1, 0, 0),   // Face 0: +X (right) - GREEN
            new CANNON.Vec3(-1, 0, 0),  // Face 1: -X (left) - YELLOW
            new CANNON.Vec3(0, 1, 0),   // Face 2: +Y (top) - RED
            new CANNON.Vec3(0, -1, 0),  // Face 3: -Y (bottom) - BLUE
            new CANNON.Vec3(0, 0, 1),   // Face 4: +Z (front) - WHITE
            new CANNON.Vec3(0, 0, -1)   // Face 5: -Z (back) - PINK
        ];
        
        faceNormals.forEach((normal, geometryIndex) => {
            // Transform normal to world space using quaternion
            const worldNormal = this.body.quaternion.vmult(normal);
            
            // Calculate dot product with up vector (higher = more upward facing)
            const dot = worldNormal.dot(upVector);
            
            if (dot > maxDot) {
                maxDot = dot;
                topFaceIndex = geometryIndex;
            }
        });
        
        // Get color name directly from FACE_COLORS (now matches geometry indices)
        this.finalColorName = FACE_COLORS[topFaceIndex];
        this.finalColor = COLORS[this.finalColorName];
        
        // Debug logging
        console.log(`Dice ${this.index}:`);
        console.log('  Top face index:', topFaceIndex);
        console.log('  Color name:', this.finalColorName);
        console.log('  Face normals checked:', faceNormals.map((n, i) => 
            `${i}: (${n.x},${n.y},${n.z})`
        ));
        console.log('  Max dot product:', maxDot.toFixed(3));
        
        // Update visual to show final color more prominently
        this.highlightFinalColor();
    }
    
    /**
     * Highlight the final color
     */
    highlightFinalColor() {
        // Keep original face colors; only add a subtle glow to indicate result
        // Increase emissive slightly to make the cube pop without recoloring faces
        // Since mesh is now a Group, iterate through children (face meshes)
        if (this.mesh && this.mesh.children) {
            this.mesh.children.forEach(child => {
                if (child instanceof THREE.Mesh && child.material) {
                    // For MeshBasicMaterial, emissiveIntensity might not exist
                    if (child.material.emissiveIntensity !== undefined) {
                        child.material.emissiveIntensity = 0.5; // Slightly brighter when settled
                    }
                }
            });
        }

        // Update glow light to the winning face color for subtle emphasis
        this.glowLight.color.setHex(this.finalColor);
        this.glowLight.intensity = 2.0;
    }
    
    /**
     * Pulse glow effect
     */
    pulseGlow(time) {
        const pulse = Math.sin(time * Math.PI * 2) * 0.5 + 1.5;
        this.glowLight.intensity = pulse;
        
        // Since mesh is now a Group, iterate through children (face meshes)
        if (this.mesh && this.mesh.children) {
            this.mesh.children.forEach(child => {
                if (child instanceof THREE.Mesh && child.material) {
                    // For MeshBasicMaterial, emissiveIntensity might not exist
                    // But we can still update if it's a MeshStandardMaterial
                    if (child.material.emissiveIntensity !== undefined) {
                        child.material.emissiveIntensity = pulse * 0.3;
                    }
                }
            });
        }
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
        
        // Since mesh is now a Group, dispose each child mesh
        if (this.mesh && this.mesh.children) {
            this.mesh.children.forEach(child => {
                if (child instanceof THREE.Mesh) {
                    if (child.geometry) child.geometry.dispose();
                    if (child.material) {
                        if (Array.isArray(child.material)) {
                            child.material.forEach(mat => mat.dispose());
                        } else {
                            child.material.dispose();
                        }
                    }
                }
            });
        }
        // Dispose border groups (they're now Groups containing LineSegments)
        const borderGroups = [this.borderLines, this.thickBorderLines, this.glowBorderLines, 
                             this.outerBorderLines, this.borderLayer5, this.borderLayer6];
        
        borderGroups.forEach(borderGroup => {
            if (borderGroup && borderGroup.children) {
                borderGroup.children.forEach(child => {
                    if (child instanceof THREE.LineSegments) {
                        if (child.geometry) child.geometry.dispose();
                        if (child.material) child.material.dispose();
                    }
                });
            }
        });
    }
}
