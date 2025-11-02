/**
 * PHYSICS MODULE
 * Handles Cannon-es physics world setup
 */

import { PHYSICS } from './config.js';

export class PhysicsWorld {
    constructor() {
        this.world = null;
        this.tableBody = null;
        this.tableMaterial = null;
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
     * Create physics materials for table and dice
     */
    createMaterials() {
        // Table material
        this.tableMaterial = new CANNON.Material('table');
        
        // Dice material
        this.diceMaterial = new CANNON.Material('dice');
        
        // Contact material (how they interact)
        this.contactMaterial = new CANNON.ContactMaterial(
            this.tableMaterial,
            this.diceMaterial,
            {
                friction: PHYSICS.tableFriction,
                restitution: PHYSICS.tableRestitution,
                contactEquationStiffness: 1e8,
                contactEquationRelaxation: 3
            }
        );
        
        // Dice to dice contact
        const diceToDice = new CANNON.ContactMaterial(
            this.diceMaterial,
            this.diceMaterial,
            {
                friction: PHYSICS.diceFriction,
                restitution: PHYSICS.diceRestitution,
                contactEquationStiffness: 1e8,
                contactEquationRelaxation: 3
            }
        );
        
        this.world.addContactMaterial(this.contactMaterial);
        this.world.addContactMaterial(diceToDice);
    }
    
    /**
     * Create table physics body
     */
    createTable() {
        const shape = new CANNON.Box(new CANNON.Vec3(
            PHYSICS.tableWidth / 2,
            PHYSICS.tableHeight / 2,
            PHYSICS.tableDepth / 2
        ));
        
        this.tableBody = new CANNON.Body({
            mass: 0, // Static body (infinite mass)
            shape: shape,
            position: new CANNON.Vec3(0, PHYSICS.tableY, 0),
            material: this.tableMaterial
        });
        
        this.world.addBody(this.tableBody);
    }
    
    /**
     * Step physics simulation
     */
    step(deltaTime) {
        this.world.step(PHYSICS.timeStep, deltaTime, 3);
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
        // Remove all bodies except table
        const bodiesToRemove = [];
        this.world.bodies.forEach(body => {
            if (body !== this.tableBody) {
                bodiesToRemove.push(body);
            }
        });
        
        bodiesToRemove.forEach(body => {
            this.world.removeBody(body);
        });
    }
}
