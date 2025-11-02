/**
 * RENDERER MODULE
 * Handles THREE.js scene, camera, lights, and rendering
 */

import { CAMERA, LIGHTS, PHYSICS } from './config.js';

export class Renderer {
    constructor(container) {
        this.container = container;
        this.scene = null;
        this.camera = null;
        this.renderer = null;
        this.table = null;
    }
    
    /**
     * Initialize THREE.js scene
     */
    init() {
        // Create scene
        this.scene = new THREE.Scene();
        this.scene.background = new THREE.Color(0x0a0a1a);
        this.scene.fog = new THREE.Fog(0x0a0a1a, 15, 35);
        
        // Create camera
        this.createCamera();
        
        // Create renderer
        this.createRenderer();
        
        // Create lights
        this.createLights();
        
        // Create table visual
        this.createTable();
        
        // Handle window resize
        window.addEventListener('resize', () => this.onWindowResize());
        
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
        this.camera = new THREE.PerspectiveCamera(
            CAMERA.fov,
            window.innerWidth / window.innerHeight,
            CAMERA.near,
            CAMERA.far
        );
        
        this.camera.position.set(
            CAMERA.defaultPosition.x,
            CAMERA.defaultPosition.y,
            CAMERA.defaultPosition.z
        );
        
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
        
        this.renderer.setSize(window.innerWidth, window.innerHeight);
        this.renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
        this.renderer.shadowMap.enabled = true;
        this.renderer.shadowMap.type = THREE.PCFSoftShadowMap;
        this.renderer.outputEncoding = THREE.sRGBEncoding;
        this.renderer.toneMapping = THREE.ACESFilmicToneMapping;
        this.renderer.toneMappingExposure = 1.2;
        
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
     * Create table visual
     */
    createTable() {
        const geometry = new THREE.BoxGeometry(
            PHYSICS.tableWidth,
            PHYSICS.tableHeight,
            PHYSICS.tableDepth
        );
        
        const material = new THREE.MeshStandardMaterial({
            color: 0xFFD700,
            metalness: 0.2,
            roughness: 0.8,
            side: THREE.DoubleSide
        });
        
        this.table = new THREE.Mesh(geometry, material);
        this.table.position.y = PHYSICS.tableY;
        this.table.receiveShadow = true;
        this.table.castShadow = false;
        
        this.scene.add(this.table);
        
        // Add table border/frame
        const edgesGeometry = new THREE.EdgesGeometry(geometry);
        const edgesMaterial = new THREE.LineBasicMaterial({
            color: 0x00CED1,
            linewidth: 2
        });
        const edges = new THREE.LineSegments(edgesGeometry, edgesMaterial);
        this.table.add(edges);
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
        this.camera.aspect = window.innerWidth / window.innerHeight;
        this.camera.updateProjectionMatrix();
        this.renderer.setSize(window.innerWidth, window.innerHeight);
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
