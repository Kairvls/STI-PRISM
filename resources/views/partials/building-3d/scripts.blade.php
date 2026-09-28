    {{-- ===================================================== --}}
    {{-- THREE.JS --}}
    {{-- PHASE 1: 3D BUILDING VIEWER --}}
    {{-- ===================================================== --}}

    <script type="importmap">
        {
            "imports": {
                "three": "https://cdn.jsdelivr.net/npm/three@0.180.0/build/three.module.js",
                "three/addons/": "https://cdn.jsdelivr.net/npm/three@0.180.0/examples/jsm/"
            }
        }
    </script>

    @php
        // =====================================================
        // PREPARE PHASE 2.1 BUILDING DATA
        // =====================================================

        $building3DRoomIds = collect($roomsByFloor)
            ->flatMap(fn ($rooms) => collect($rooms)->pluck('room_id'))
            ->all();
        $building3DProperty = \App\Support\PropertyAssignments::roomSummaries($building3DRoomIds);
        $building3DCustodians = \App\Support\PropertyAssignments::custodianDirectory($building3DRoomIds);

        $building3DData = $floors
            ->map(function ($floor) use ($roomsByFloor, $building3DProperty) {
                return [
                    "id" => $floor->floor_id,

                    "name" => $floor->floor_level,

                    "rooms" => collect($roomsByFloor->get($floor->floor_id, collect()))
                        ->filter(function ($room) {
                            return !$room->room_is_archived;
                        })
                        ->map(function ($room) use ($building3DProperty) {
                            return [
                                "id" => $room->room_id,
                                "name" => $room->room_name,
                                "type" => $room->room_type,
                                "status" => $room->dashboard_status,

                                "property" => $building3DProperty[(int) $room->room_id] ?? null,

                                "activeReportCount" => $room->active_report_count,

                                "urgentReportCount" => $room->urgent_report_count,

                                "maintenanceEquipmentCount" =>
                                    $room->maintenance_equipment_count,

                                "x" => (float) $room->room_x,
                                "y" => (float) $room->room_y,

                                "width" => (float) $room->room_width,
                                "height" => (float) $room->room_height,

                                "color" => $room->room_color ?: "#60A5FA",

                                "rotation" => (float) data_get(
                                    $room->room_metadata,
                                    "rotation",
                                    0,
                                ),
                            ];
                        })
                        ->values(),
                ];
            })
            ->values();
    @endphp

    <script type="module">
        // =====================================================
        // IMPORT THREE.JS DIRECTLY
        // =====================================================

        import * as THREE from "three";

        import { OrbitControls } from "three/addons/controls/OrbitControls.js";

        // =====================================================
        // BLOOM POST PROCESSING
        // ADD THESE IMPORTS HERE
        // =====================================================

        import { EffectComposer } from "three/addons/postprocessing/EffectComposer.js";

        import { RenderPass } from "three/addons/postprocessing/RenderPass.js";

        import { UnrealBloomPass } from "three/addons/postprocessing/UnrealBloomPass.js";

        // =====================================================
        // PHASE 2.1
        // REAL FLOOR AND ROOM DATA FROM LARAVEL
        // =====================================================
        const building3DData = @json ($building3DData);

        const building3DCustodians = @json ($building3DCustodians);

        // "__ROOM__" is replaced with the room id; null opens the maintenance room layout.
        const building3DRoomViewUrl = @json ($building3DRoomViewUrl ?? null);

        console.log("REAL 3D BUILDING DATA:", building3DData);

        // =====================================================
        // GET VIEWPORT
        // =====================================================

        const container = document.getElementById("building3DViewport");

        console.log("3D Building Viewport:", container);

        if (!container) {
            console.error("building3DViewport was not found.");
        } else {
            console.log("Starting Three.js building viewer...");

            // =====================================================
            // SCENE
            // Exterior: soft cool sky + quiet clouds
            // Goal: white clay shell is the hero; sky supports, doesn't compete
            // =====================================================

            const scene = new THREE.Scene();

            function createSoftCloudSkyTexture() {
                const size = 1024;
                const canvas = document.createElement("canvas");
                canvas.width = size;
                canvas.height = size;
                const ctx = canvas.getContext("2d");

                // Cool soft blue sky → near-white horizon
                // Mid blue gives contrast so pure-white shell stands out
                const sky = ctx.createLinearGradient(0, 0, 0, size);
                sky.addColorStop(0, "#8fb4d4");
                sky.addColorStop(0.22, "#b5cee4");
                sky.addColorStop(0.48, "#d2e2f0");
                sky.addColorStop(0.72, "#e6eef6");
                sky.addColorStop(1, "#f2f6fa");
                ctx.fillStyle = sky;
                ctx.fillRect(0, 0, size, size);

                // Quiet cloud layer — soft, readable, not busy
                const clouds = [
                    [180, 130, 340, 0.5],
                    [480, 95, 380, 0.46],
                    [780, 140, 320, 0.48],
                    [320, 220, 300, 0.34],
                    [640, 240, 340, 0.36],
                    [120, 280, 240, 0.28],
                    [900, 260, 220, 0.3],
                ];

                clouds.forEach(([x, y, r, alpha]) => {
                    const drawPuff = (cx, cy, rx, ry, a) => {
                        const g = ctx.createRadialGradient(
                            cx,
                            cy,
                            Math.min(rx, ry) * 0.08,
                            cx,
                            cy,
                            Math.max(rx, ry),
                        );
                        g.addColorStop(0, `rgba(255,255,255,${a})`);
                        g.addColorStop(0.4, `rgba(255,255,255,${a * 0.55})`);
                        g.addColorStop(1, "rgba(255,255,255,0)");
                        ctx.fillStyle = g;
                        ctx.beginPath();
                        ctx.ellipse(cx, cy, rx, ry, 0, 0, Math.PI * 2);
                        ctx.fill();
                    };

                    drawPuff(x, y, r, r * 0.42, alpha);
                    drawPuff(x - r * 0.4, y + r * 0.05, r * 0.65, r * 0.36, alpha * 0.75);
                    drawPuff(x + r * 0.38, y + r * 0.04, r * 0.7, r * 0.38, alpha * 0.8);
                    drawPuff(x + r * 0.05, y - r * 0.12, r * 0.5, r * 0.32, alpha * 0.65);
                });

                const texture = new THREE.CanvasTexture(canvas);
                texture.colorSpace = THREE.SRGBColorSpace;
                texture.needsUpdate = true;
                return texture;
            }

            const exteriorCloudSkyTexture = createSoftCloudSkyTexture();

            function createIceBlueBackdropTexture() {
                const size = 1024;
                const canvas = document.createElement("canvas");
                canvas.width = size;
                canvas.height = size;
                const ctx = canvas.getContext("2d");

                ctx.fillStyle = "#020d18";
                ctx.fillRect(0, 0, size, size);

                const glow = ctx.createRadialGradient(
                    size * 0.5,
                    size * 0.48,
                    size * 0.04,
                    size * 0.5,
                    size * 0.5,
                    size * 0.72,
                );
                glow.addColorStop(0, "#12506a");
                glow.addColorStop(0.22, "#0c3d55");
                glow.addColorStop(0.52, "#072536");
                glow.addColorStop(1, "#020d18");
                ctx.fillStyle = glow;
                ctx.fillRect(0, 0, size, size);

                const texture = new THREE.CanvasTexture(canvas);
                texture.colorSpace = THREE.SRGBColorSpace;
                texture.needsUpdate = true;
                return texture;
            }

            const iceBlueBackdropTexture = createIceBlueBackdropTexture();
            const ICE_BLUE_BACKDROP_FOG = 0x020d18;

            scene.background = iceBlueBackdropTexture;

            const exteriorSkyDome = new THREE.Mesh(
                new THREE.SphereGeometry(220, 48, 28),
                new THREE.MeshBasicMaterial({
                    map: exteriorCloudSkyTexture,
                    side: THREE.BackSide,
                    depthWrite: false,
                    fog: false,
                }),
            );
            exteriorSkyDome.renderOrder = -2000;
            exteriorSkyDome.frustumCulled = false;
            exteriorSkyDome.visible = false; // holographic dark studio
            scene.add(exteriorSkyDome);

            // Soft falloff into the same dark ice-blue
            scene.fog = new THREE.Fog(ICE_BLUE_BACKDROP_FOG, 32, 78);

            // =====================================================
            // CAMERA
            // =====================================================

            const camera = new THREE.PerspectiveCamera(
                45,
                container.clientWidth / container.clientHeight,
                0.1,
                1000,
            );

            camera.position.set(18, 14, 20);

            // =====================================================
            // RENDERER
            // =====================================================

            // Scene renders through EffectComposer's render targets, so canvas MSAA
            // (antialias) only costs GPU memory without smoothing anything.
            const renderer = new THREE.WebGLRenderer({
                antialias: false,
                alpha: false,
            });

            renderer.setPixelRatio(Math.min(window.devicePixelRatio, 1.5));

            renderer.setSize(container.clientWidth, container.clientHeight);

            // Better color rendering
            renderer.outputColorSpace = THREE.SRGBColorSpace;

            // Better lighting calculation
            renderer.toneMapping = THREE.ACESFilmicToneMapping;

            renderer.toneMappingExposure = 1.15;

            // Shadows
            renderer.shadowMap.enabled = true;

            renderer.shadowMap.type = THREE.PCFSoftShadowMap;

            container.appendChild(renderer.domElement);

            // =====================================================
            // BLOOM POST PROCESSING
            // ADD DIRECTLY AFTER RENDERER SETUP
            // =====================================================

            const composer = new EffectComposer(renderer);

            const renderPass = new RenderPass(scene, camera);

            composer.addPass(renderPass);

            // =====================================================
            // HOLOGRAPHIC CYAN BLOOM
            // =====================================================

            const bloomPass = new UnrealBloomPass(
                new THREE.Vector2(container.clientWidth, container.clientHeight),

                0.22,
                0.28,
                0.48,
            );

            composer.addPass(bloomPass);

            // =====================================================
            // SET INITIAL COMPOSER SIZE
            // PUT composer.setSize() HERE
            // =====================================================

            composer.setSize(container.clientWidth, container.clientHeight);

            // =====================================================
            // CAMERA CONTROLS
            // =====================================================

            const controls = new OrbitControls(camera, renderer.domElement);

            controls.enableDamping = true;
            controls.dampingFactor = 0.06;

            controls.enableZoom = true;
            controls.enablePan = true;

            controls.minDistance = 4;
            controls.maxDistance = 42;

            controls.target.set(0, 2.5, 0);

            controls.addEventListener("start", () => {
                cameraTransition = null;
            });

            // =====================================================
            // LIGHTING
            // Cyan holographic fill — the shell is the light source
            // =====================================================

            const ambientLight = new THREE.HemisphereLight(0x38bdf8, 0x020617, 0.28);

            scene.add(ambientLight);

            const topLight = new THREE.PointLight(0x22d3ee, 0.45, 120);

            topLight.position.set(0, 24, 0);

            scene.add(topLight);

            const groundLight = new THREE.PointLight(0x67e8f9, 0.7, 90);

            groundLight.position.set(-4, 1.2, 4);

            scene.add(groundLight);

            const directionalLight = new THREE.DirectionalLight(0x7dd3fc, 0.35);

            directionalLight.position.set(16, 20, 8);

            directionalLight.castShadow = true;

            directionalLight.shadow.mapSize.set(1024, 1024);
            directionalLight.shadow.bias = -0.00025;
            directionalLight.shadow.normalBias = 0.035;
            directionalLight.shadow.radius = 4;
            directionalLight.shadow.camera.near = 1;
            directionalLight.shadow.camera.far = 80;
            directionalLight.shadow.camera.left = -30;
            directionalLight.shadow.camera.right = 30;
            directionalLight.shadow.camera.top = 30;
            directionalLight.shadow.camera.bottom = -30;

            scene.add(directionalLight);

            // =====================================================
            // BLUEPRINT GROUND (like the reference under the model)
            // =====================================================

            function createBlueprintTexture() {
                const size = 1024;
                const canvas = document.createElement("canvas");
                canvas.width = size;
                canvas.height = size;
                const ctx = canvas.getContext("2d");

                ctx.fillStyle = "#000000";
                ctx.fillRect(0, 0, size, size);

                ctx.strokeStyle = "rgba(34, 211, 238, 0.16)";
                ctx.lineWidth = 1;
                for (let i = 0; i <= size; i += 24) {
                    ctx.beginPath();
                    ctx.moveTo(i, 0);
                    ctx.lineTo(i, size);
                    ctx.stroke();
                    ctx.beginPath();
                    ctx.moveTo(0, i);
                    ctx.lineTo(size, i);
                    ctx.stroke();
                }

                ctx.strokeStyle = "rgba(103, 232, 249, 0.28)";
                ctx.lineWidth = 1.4;
                for (let i = 0; i <= size; i += 96) {
                    ctx.beginPath();
                    ctx.moveTo(i, 0);
                    ctx.lineTo(i, size);
                    ctx.stroke();
                    ctx.beginPath();
                    ctx.moveTo(0, i);
                    ctx.lineTo(size, i);
                    ctx.stroke();
                }

                const rooms = [
                    [70, 80, 170, 120], [270, 80, 130, 90], [430, 80, 150, 70],
                    [70, 230, 110, 150], [200, 230, 190, 90], [420, 180, 130, 170],
                    [580, 110, 150, 100], [580, 240, 150, 120], [760, 110, 160, 250],
                    [80, 520, 200, 130], [310, 520, 160, 130], [500, 510, 220, 90],
                    [740, 500, 180, 160], [120, 700, 240, 140], [400, 680, 180, 160],
                    [620, 700, 220, 120],
                ];

                rooms.forEach(([x, y, w, h]) => {
                    ctx.strokeStyle = "rgba(125, 249, 255, 0.38)";
                    ctx.lineWidth = 1.6;
                    ctx.strokeRect(x, y, w, h);

                    ctx.strokeStyle = "rgba(34, 211, 238, 0.18)";
                    ctx.lineWidth = 1;
                    ctx.beginPath();
                    ctx.moveTo(x, y - 7);
                    ctx.lineTo(x + w, y - 7);
                    ctx.stroke();
                });

                ctx.fillStyle = "rgba(165, 243, 252, 0.55)";
                for (let i = 0; i < 90; i++) {
                    const x = (i * 97) % size;
                    const y = (i * 173) % size;
                    ctx.beginPath();
                    ctx.arc(x, y, i % 7 === 0 ? 2.1 : 1.1, 0, Math.PI * 2);
                    ctx.fill();
                }

                const texture = new THREE.CanvasTexture(canvas);
                texture.wrapS = THREE.RepeatWrapping;
                texture.wrapT = THREE.RepeatWrapping;
                texture.repeat.set(2.2, 2.2);
                texture.anisotropy = 8;
                texture.colorSpace = THREE.SRGBColorSpace;
                return texture;
            }

            const blueprintTexture = createBlueprintTexture();

            const darkGround = new THREE.Mesh(
                new THREE.PlaneGeometry(120, 120),
                new THREE.MeshBasicMaterial({
                    color: 0x03060c,
                    side: THREE.DoubleSide,
                }),
            );
            darkGround.rotation.x = -Math.PI / 2;
            darkGround.position.y = -0.045;
            scene.add(darkGround);

            const floor = new THREE.Mesh(
                new THREE.PlaneGeometry(100, 100),
                new THREE.MeshBasicMaterial({
                    map: blueprintTexture,
                    color: 0xffffff,
                    transparent: true,
                    opacity: 0.9,
                    blending: THREE.AdditiveBlending,
                    depthWrite: false,
                    toneMapped: false,
                    side: THREE.DoubleSide,
                }),
            );

            floor.rotation.x = -Math.PI / 2;
            floor.position.y = -0.02;
            floor.receiveShadow = true;
            scene.add(floor);

            const grid = new THREE.GridHelper(
                100,
                50,
                0x22d3ee,
                0x0e3a48,
            );

            grid.position.y = 0.014;
            grid.material.transparent = true;
            grid.material.opacity = 0.18;
            grid.material.blending = THREE.AdditiveBlending;
            grid.material.depthWrite = false;
            scene.add(grid);

            // =====================================================
            // BUILDING GROUP
            // =====================================================

            // =====================================================
            // PHASE 2: 3D BUILDING WITH INDIVIDUAL ROOMS
            // REPLACES THE OLD PLACEHOLDER BUILDING BLOCKS
            // =====================================================

            // =====================================================
            // BUILDING GROUP
            // ALL FLOORS AND ROOMS WILL BE ADDED HERE
            // =====================================================

            const building = new THREE.Group();

            scene.add(building);

            // =====================================================
            // PHASE 8.1
            // EXTERIOR BUILDING SHELL
            // =====================================================

            // This group is separate from the existing interior building.
            // Later, Phase 8.2 will switch between:
            // exteriorBuilding = outside view
            // building = interior room view

            const exteriorBuilding = new THREE.Group();

            exteriorBuilding.userData.isBuildingExterior = true;

            scene.add(exteriorBuilding);

            // =====================================================
            // PHASE 8.2
            // BUILDING VIEW MODE
            //
            // exterior = user is viewing the outside building shell
            // interior = user is viewing floors and rooms
            // =====================================================

            // Default after refresh/load: exterior shell (screenshot angle).
            // Enter Building opens floors; Return comes back to this shell.
            let currentBuildingView = "exterior";

            let isBuildingViewTransitioning = false;

            // =====================================================
            // SCENE THEME
            // Ground, fog, and background stay the same.
            // Only the exterior shell type changes.
            // =====================================================

            function applyBuildingSceneTheme(mode) {
                const isExterior = mode === "exterior";

                container.classList.toggle("is-interior-view", !isExterior);

                scene.background = iceBlueBackdropTexture;
                scene.fog = new THREE.Fog(ICE_BLUE_BACKDROP_FOG, 32, 78);

                if (typeof exteriorSkyDome !== "undefined" && exteriorSkyDome) {
                    exteriorSkyDome.visible = false;
                }

                bloomPass.strength = isExterior ? 0.22 : 0.12;
                bloomPass.radius = 0.28;
                bloomPass.threshold = isExterior ? 0.48 : 0.55;

                ambientLight.color.set(0x38bdf8);
                if (ambientLight.groundColor) {
                    ambientLight.groundColor.set(0x020617);
                }
                ambientLight.intensity = isExterior ? 0.28 : 0.55;

                topLight.color.set(0x22d3ee);
                topLight.intensity = isExterior ? 0.45 : 1.2;

                groundLight.color.set(0x67e8f9);
                groundLight.intensity = isExterior ? 0.7 : 0.9;

                directionalLight.color.set(0x7dd3fc);
                directionalLight.intensity = isExterior ? 0.35 : 1.1;

                if (floor.material) {
                    floor.material.color.set(0xffffff);
                    floor.material.opacity = 0.9;
                    floor.material.transparent = true;
                    if (floor.material.map) {
                        floor.material.map = blueprintTexture;
                        floor.material.needsUpdate = true;
                    }
                }

                if (grid.material) {
                    grid.material.opacity = 0.18;
                    if (grid.material.color) {
                        grid.material.color.set(0x22d3ee);
                    }
                }

                renderer.toneMappingExposure = isExterior ? 0.98 : 1.05;
            }

            applyBuildingSceneTheme("exterior");

            // =====================================================
            // PHASE 8.2
            // INITIAL BUILDING VISIBILITY
            //
            // Dashboard starts in Exterior Mode (shell overview).
            // Hide interior rooms; show the exterior wireframe shell.
            // =====================================================

            building.visible = false;

            exteriorBuilding.visible = true;

            // =====================================================
            // EXTERIOR BLUEPRINT SHELL
            // Transparent cyan volume + glowing layout lines
            // =====================================================

            const EXTERIOR_SHELL_OPACITY = 0.11;
            const EXTERIOR_EDGE_OPACITY = 0.82;
            const EXTERIOR_GRID_OPACITY = 0.28;

            function tagShellMaterial(material, opacity) {
                material.transparent = true;
                material.opacity = opacity;
                material.userData.shellOpacity = opacity;
                return material;
            }

            function shellGridDivisions(size) {
                if (size < 0.35) {
                    return 1;
                }

                return Math.max(1, Math.min(12, Math.round(size * 1.15)));
            }

            function createShellSpeckleTexture() {
                const size = 256;
                const canvas = document.createElement("canvas");
                canvas.width = size;
                canvas.height = size;
                const ctx = canvas.getContext("2d");

                ctx.fillStyle = "rgba(56, 150, 190, 0.12)";
                ctx.fillRect(0, 0, size, size);

                for (let i = 0; i < 700; i++) {
                    const alpha = 0.08 + Math.random() * 0.22;
                    ctx.fillStyle = `rgba(126, 200, 230, ${alpha})`;
                    ctx.fillRect(
                        Math.random() * size,
                        Math.random() * size,
                        Math.random() > 0.85 ? 2 : 1,
                        1,
                    );
                }

                const texture = new THREE.CanvasTexture(canvas);
                texture.wrapS = THREE.RepeatWrapping;
                texture.wrapT = THREE.RepeatWrapping;
                texture.anisotropy = 4;
                texture.colorSpace = THREE.SRGBColorSpace;
                return texture;
            }

            const shellSpeckleTexture = createShellSpeckleTexture();

            const exteriorMaterial = new THREE.MeshBasicMaterial({
                color: 0x4aa8d0,

                map: shellSpeckleTexture,

                transparent: true,

                opacity: EXTERIOR_SHELL_OPACITY,

                side: THREE.DoubleSide,

                depthWrite: false,
            });

            tagShellMaterial(exteriorMaterial, EXTERIOR_SHELL_OPACITY);

            const exteriorEdgeMaterial = new THREE.LineBasicMaterial({
                color: 0x7ec8ea,

                transparent: true,

                opacity: EXTERIOR_EDGE_OPACITY,

                depthWrite: false,
            });

            tagShellMaterial(exteriorEdgeMaterial, EXTERIOR_EDGE_OPACITY);

            const exteriorGridMaterial = new THREE.LineBasicMaterial({
                color: 0x5eb4d6,

                transparent: true,

                opacity: EXTERIOR_GRID_OPACITY,

                depthWrite: false,
            });

            tagShellMaterial(exteriorGridMaterial, EXTERIOR_GRID_OPACITY);

            // =====================================================
            // PHASE 8.1
            // HELPER FUNCTION TO CREATE BUILDING SECTIONS
            // =====================================================

            function createExteriorSection(width, height, depth, x, y, z) {
                const geometry = new THREE.BoxGeometry(width, height, depth);

                const material = tagShellMaterial(
                    exteriorMaterial.clone(),
                    EXTERIOR_SHELL_OPACITY,
                );

                if (material.map) {
                    material.map = shellSpeckleTexture.clone();
                    material.map.wrapS = THREE.RepeatWrapping;
                    material.map.wrapT = THREE.RepeatWrapping;
                    material.map.repeat.set(
                        Math.max(width, depth) * 0.8,
                        Math.max(height, 0.6) * 0.8,
                    );
                    material.map.needsUpdate = true;
                }

                const mesh = new THREE.Mesh(geometry, material);

                mesh.position.set(x, y, z);

                mesh.castShadow = false;
                mesh.receiveShadow = false;

                const outline = new THREE.LineSegments(
                    new THREE.EdgesGeometry(geometry),
                    tagShellMaterial(exteriorEdgeMaterial.clone(), EXTERIOR_EDGE_OPACITY),
                );
                outline.renderOrder = 2;
                mesh.add(outline);

                const dividedGeometry = new THREE.BoxGeometry(
                    width,
                    height,
                    depth,
                    shellGridDivisions(width),
                    shellGridDivisions(height),
                    shellGridDivisions(depth),
                );
                const innerGrid = new THREE.LineSegments(
                    new THREE.WireframeGeometry(dividedGeometry),
                    tagShellMaterial(exteriorGridMaterial.clone(), EXTERIOR_GRID_OPACITY),
                );
                innerGrid.renderOrder = 1;
                innerGrid.userData.isBuildingExterior = true;
                mesh.add(innerGrid);
                dividedGeometry.dispose();

                exteriorBuilding.add(mesh);

                return mesh;
            }

            // =====================================================
            // PHASE 8.3 PART 2
            // CREATE CURVED FRONT ARCH
            // =====================================================

            function createExteriorFrontArch(width, height, depth, x, y, z) {
                // =================================================
                // ARCH GROUP
                // =================================================

                const archGroup = new THREE.Group();

                archGroup.position.set(x, y, z);

                // =================================================
                // ARCH DIMENSIONS
                //
                // The arch is made from:
                //
                // 1. Left vertical column
                // 2. Right vertical column
                // 3. Curved upper section
                //
                // This creates the large rounded feature visible
                // on the Robinsons front facade.
                // =================================================

                const columnWidth = Math.max(width * 0.16, 0.35);

                const curveRadius = width / 2;

                const straightHeight = Math.max(height - curveRadius, height * 0.45);

                // =================================================
                // ARCH MATERIAL — same blueprint shell as the building
                // =================================================

                const archMaterial = tagShellMaterial(
                    exteriorMaterial.clone(),
                    EXTERIOR_SHELL_OPACITY,
                );

                const archEdgeMaterial = tagShellMaterial(
                    exteriorEdgeMaterial.clone(),
                    EXTERIOR_EDGE_OPACITY,
                );

                // =================================================
                // LEFT VERTICAL COLUMN
                // =================================================

                const leftColumnGeometry = new THREE.BoxGeometry(
                    columnWidth,

                    straightHeight,

                    depth,
                );

                const leftColumn = new THREE.Mesh(
                    leftColumnGeometry,

                    archMaterial.clone(),
                );

                leftColumn.position.set(
                    -(width / 2) + columnWidth / 2,

                    -(height / 2) + straightHeight / 2,

                    0,
                );

                leftColumn.castShadow = true;
                leftColumn.receiveShadow = true;

                archGroup.add(leftColumn);

                // =================================================
                // LEFT COLUMN EDGES
                // =================================================

                const leftColumnEdges = new THREE.LineSegments(
                    new THREE.EdgesGeometry(leftColumnGeometry),

                    archEdgeMaterial.clone(),
                );

                leftColumnEdges.position.copy(leftColumn.position);

                archGroup.add(leftColumnEdges);

                const leftColumnGrid = new THREE.LineSegments(
                    new THREE.WireframeGeometry(
                        new THREE.BoxGeometry(
                            columnWidth,
                            straightHeight,
                            depth,
                            shellGridDivisions(columnWidth),
                            shellGridDivisions(straightHeight),
                            shellGridDivisions(depth),
                        ),
                    ),
                    tagShellMaterial(exteriorGridMaterial.clone(), EXTERIOR_GRID_OPACITY),
                );
                leftColumnGrid.position.copy(leftColumn.position);
                archGroup.add(leftColumnGrid);

                // =================================================
                // RIGHT VERTICAL COLUMN
                // =================================================

                const rightColumnGeometry = new THREE.BoxGeometry(
                    columnWidth,

                    straightHeight,

                    depth,
                );

                const rightColumn = new THREE.Mesh(
                    rightColumnGeometry,

                    archMaterial.clone(),
                );

                rightColumn.position.set(
                    width / 2 - columnWidth / 2,

                    -(height / 2) + straightHeight / 2,

                    0,
                );

                archGroup.add(rightColumn);

                // =================================================
                // RIGHT COLUMN EDGES
                // =================================================

                const rightColumnEdges = new THREE.LineSegments(
                    new THREE.EdgesGeometry(rightColumnGeometry),

                    archEdgeMaterial.clone(),
                );

                rightColumnEdges.position.copy(rightColumn.position);

                archGroup.add(rightColumnEdges);

                const rightColumnGrid = new THREE.LineSegments(
                    new THREE.WireframeGeometry(
                        new THREE.BoxGeometry(
                            columnWidth,
                            straightHeight,
                            depth,
                            shellGridDivisions(columnWidth),
                            shellGridDivisions(straightHeight),
                            shellGridDivisions(depth),
                        ),
                    ),
                    tagShellMaterial(exteriorGridMaterial.clone(), EXTERIOR_GRID_OPACITY),
                );
                rightColumnGrid.position.copy(rightColumn.position);
                archGroup.add(rightColumnGrid);

                // =================================================
                // CURVED TOP
                //
                // Creates the rounded arch shape.
                //
                // RingGeometry gives us an outer curve and an
                // inner opening instead of a solid semicircle.
                // =================================================

                const outerRadius = width / 2;

                const innerRadius = Math.max(
                    outerRadius - columnWidth,

                    outerRadius * 0.55,
                );

                const archGeometry = new THREE.RingGeometry(
                    innerRadius,

                    outerRadius,

                    32,

                    1,

                    0,

                    Math.PI,
                );

                const curvedArch = new THREE.Mesh(
                    archGeometry,

                    archMaterial.clone(),
                );

                // =================================================
                // POSITION THE CURVE
                //
                // RingGeometry is created in the XY plane.
                // Since the front facade faces the Z direction,
                // no rotation is needed here.
                // =================================================

                curvedArch.position.set(
                    0,

                    -(height / 2) + straightHeight,

                    depth / 2 + 0.01,
                );

                archGroup.add(curvedArch);

                // =================================================
                // CURVED ARCH OUTLINE
                // =================================================

                const curvedArchEdges = new THREE.LineSegments(
                    new THREE.EdgesGeometry(archGeometry),

                    archEdgeMaterial.clone(),
                );

                curvedArchEdges.position.copy(curvedArch.position);

                archGroup.add(curvedArchEdges);

                // =================================================
                // MARK ALL OBJECTS AS EXTERIOR
                //
                // This is important because Phase 8.2 uses this
                // property for exterior clicking and interaction.
                // =================================================

                archGroup.traverse((child) => {
                    if (child.isMesh || child.isLineSegments) {
                        child.userData.isBuildingExterior = true;
                    }
                });

                // =================================================
                // ADD TO EXTERIOR BUILDING GROUP
                // =================================================

                exteriorBuilding.add(archGroup);

                return archGroup;
            }

            // =====================================================
            // PHASE 8.1
            // MAIN STI BUILDING BODY
            //
            // Inspired by the actual long horizontal shape
            // of the STI College Ormoc building.
            // =====================================================

            // =====================================================
            // PHASE 8.1
            // SAVE IDENTIFICATION
            // USED LATER FOR CLICKING THE BUILDING
            // =====================================================

            exteriorBuilding.userData = {
                type: "exterior-building",

                name: "STI College Ormoc",
            };

            const raycaster = new THREE.Raycaster();

            const mouse = new THREE.Vector2();

            const clickableRooms = [];
            const criticalRoomIndicators = [];
            const maintenanceRoomIndicators = [];

            let hoveredRoom = null;

            let selectedRoom = null;

            // =====================================================
            // PHASE 7.9
            // ROOM VISUAL STATE HELPERS
            // =====================================================

            function restoreRoomVisual(room) {
                if (!room) {
                    return;
                }

                // Restore the room's original material appearance
                if (room.userData.originalEmissive !== undefined) {
                    room.material.emissive.setHex(room.userData.originalEmissive);
                }

                if (room.userData.originalEmissiveIntensity !== undefined) {
                    room.material.emissiveIntensity =
                        room.userData.originalEmissiveIntensity;
                }

                // Restore normal room size
                room.scale.set(1, 1, 1);
            }

            function getRoomReportCounts(room) {
                const data = room?.userData || room || {};

                return {
                    urgent: Number(data.urgentReportCount || 0),
                    active: Number(data.activeReportCount || 0),
                    maintenance: Number(data.maintenanceEquipmentCount || 0),
                };
            }

            function getRoomStatusTone(room) {
                const { urgent, active, maintenance } = getRoomReportCounts(room);

                if (urgent > 0) {
                    return {
                        hex: 0xef4444,
                        css: "#ef4444",
                        glow: "rgba(239, 68, 68, 0.9)",
                        selectedIntensity: 1.2,
                        hoverIntensity: 0.55,
                    };
                }

                if (active > 0) {
                    return {
                        hex: 0xf59e0b,
                        css: "#f59e0b",
                        glow: "rgba(245, 158, 11, 0.9)",
                        selectedIntensity: 1.1,
                        hoverIntensity: 0.5,
                    };
                }

                if (maintenance > 0) {
                    return {
                        hex: 0x38bdf8,
                        css: "#38bdf8",
                        glow: "rgba(56, 189, 248, 0.9)",
                        selectedIntensity: 1.05,
                        hoverIntensity: 0.48,
                    };
                }

                return {
                    hex: 0x4ade80,
                    css: "#4ade80",
                    glow: "rgba(74, 222, 128, 0.85)",
                    selectedIntensity: 0.95,
                    hoverIntensity: 0.4,
                };
            }

            function applyRoomHoverVisual(room) {
                if (!room || room === selectedRoom) {
                    return;
                }

                const layoutHex =
                    room.userData.layoutColorHex ??
                    room.userData.originalEmissive ??
                    0x60a5fa;

                room.material.emissive.setHex(layoutHex);
                room.material.emissiveIntensity = 0.55;
            }

            function applyRoomSelectedVisual(room) {
                if (!room) {
                    return;
                }

                const layoutHex =
                    room.userData.layoutColorHex ??
                    room.userData.originalEmissive ??
                    0x60a5fa;

                room.material.emissive.setHex(layoutHex);
                room.material.emissiveIntensity = 1.0;

                // Slightly enlarge selected room
                room.scale.set(1.05, 1.05, 1.05);
            }

            // =====================================================
            // PHASE 7.8
            // ROOM DETAILS PANEL ELEMENTS
            // =====================================================

            const roomDetailsPanel = document.getElementById(
                "buildingRoomDetailsPanel",
            );

            const roomDetailsName = document.getElementById("buildingRoomDetailsName");

            const roomDetailsFloor = document.getElementById(
                "buildingRoomDetailsFloor",
            );

            const roomDetailsStatus = document.getElementById(
                "buildingRoomDetailsStatus",
            );

            const roomDetailsActiveReports = document.getElementById(
                "buildingRoomDetailsActiveReports",
            );

            const roomDetailsUrgentReports = document.getElementById(
                "buildingRoomDetailsUrgentReports",
            );

            const roomDetailsMaintenance = document.getElementById(
                "buildingRoomDetailsMaintenance",
            );

            const roomDetailsClose = document.getElementById(
                "buildingRoomDetailsClose",
            );

            const roomDetailsView = document.getElementById("buildingRoomDetailsView");

            const roomTooltip = document.getElementById("buildingRoomTooltip");

            const roomTooltipName = document.getElementById("buildingRoomTooltipName");

            const roomTooltipFloor = document.getElementById(
                "buildingRoomTooltipFloor",
            );

            const roomTooltipStatus = document.getElementById(
                "buildingRoomTooltipStatus",
            );

            const roomTooltipDot = document.getElementById("buildingRoomTooltipDot");

            const roomTooltipProperty = document.getElementById("buildingRoomTooltipProperty");

            const roomDetailsPropertyRow = document.getElementById("buildingRoomDetailsPropertyRow");

            const roomDetailsProperty = document.getElementById("buildingRoomDetailsProperty");

            function formatRoomProperty(data) {
                const property = data?.property;
                if (!property) {
                    return "";
                }

                const assigned = Number(property.assigned || 0);
                const custodians = Number(property.custodians || 0);
                const unassigned = Number(property.unassigned || 0);

                if (assigned > 0) {
                    const parts = [
                        `${assigned} assigned`,
                        `${custodians} ${custodians === 1 ? "person" : "people"}`,
                    ];
                    if (unassigned > 0) {
                        parts.push(`${unassigned} unassigned`);
                    }
                    return parts.join(" · ");
                }

                return unassigned > 0 ? `${unassigned} not yet assigned` : "";
            }

            const buildingFloorGroups = new Map();

            let cameraTransition = null;

            // =====================================================
            // PHASE 7.9
            // SAVE CAMERA VIEW BEFORE SELECTING A ROOM
            // =====================================================

            let cameraPositionBeforeRoomSelection = null;
            let cameraTargetBeforeRoomSelection = null;

            // =====================================================
            // PHASE 7.7
            // FORMAT ROOM STATUS FOR TOOLTIP
            // =====================================================

            function formatRoomStatus(room) {
                const { urgent, active, maintenance } = getRoomReportCounts(room);

                if (urgent > 0) {
                    return urgent === 1
                        ? "1 Urgent Report"
                        : `${urgent} Urgent Reports`;
                }

                if (active > 0) {
                    return active === 1
                        ? "1 Open Report"
                        : `${active} Open Reports`;
                }

                if (maintenance > 0) {
                    return maintenance === 1
                        ? "1 Item in Maintenance"
                        : `${maintenance} Items in Maintenance`;
                }

                return "No Active Reports";
            }

            // =====================================================
            // PHASE 7.7
            // UPDATE TOOLTIP CONTENT
            // =====================================================

            function updateRoomTooltip(room, mouseEvent) {
                if (!roomTooltip || !room) {
                    return;
                }

                // =================================================
                // ROOM INFORMATION
                // =================================================

                roomTooltipName.textContent = room.userData.roomName || "Room";

                roomTooltipFloor.textContent = room.userData.floorName || "Floor";

                roomTooltipStatus.textContent = formatRoomStatus(room);

                if (roomTooltipProperty) {
                    const propertyText = formatRoomProperty(room.userData);
                    roomTooltipProperty.textContent = propertyText;
                    roomTooltipProperty.hidden = propertyText === "";
                }

                // =================================================
                // STATUS DOT COLOR
                // =================================================

                const tooltipTone = getRoomStatusTone(room);

                roomTooltipDot.style.background = tooltipTone.css;
                roomTooltipDot.style.boxShadow = `0 0 8px ${tooltipTone.glow}`;

                // =================================================
                // POSITION TOOLTIP
                // =================================================

                const viewRect = renderer.domElement.getBoundingClientRect();

                const mouseX = mouseEvent.clientX - viewRect.left;

                const mouseY = mouseEvent.clientY - viewRect.top;

                roomTooltip.style.left = `${mouseX}px`;

                roomTooltip.style.top = `${mouseY}px`;

                // =================================================
                // SHOW TOOLTIP
                // =================================================

                roomTooltip.classList.add("visible");
            }

            // =====================================================
            // PHASE 7.7
            // HIDE ROOM TOOLTIP
            // =====================================================

            function hideRoomTooltip() {
                if (!roomTooltip) {
                    return;
                }

                roomTooltip.classList.remove("visible");
            }

            // =====================================================
            // PHASE 7.8
            // OPEN COMPACT ROOM DETAILS PANEL
            // =====================================================

            function openRoomDetailsPanel(room) {
                if (!room || !roomDetailsPanel) {
                    return;
                }

                // =============================================
                // UPDATE ROOM INFORMATION
                // =============================================

                roomDetailsName.textContent = room.roomName || "Room";

                roomDetailsFloor.textContent = room.floorName || "Unknown";

                roomDetailsStatus.textContent = formatRoomStatus(room);
                roomDetailsStatus.style.color = getRoomStatusTone(room).css;

                // =============================================
                // UPDATE MAINTENANCE COUNTS
                // =============================================

                roomDetailsActiveReports.textContent = room.activeReportCount || 0;

                roomDetailsUrgentReports.textContent = room.urgentReportCount || 0;

                roomDetailsMaintenance.textContent =
                    room.maintenanceEquipmentCount || 0;

                if (roomDetailsPropertyRow && roomDetailsProperty) {
                    const propertyText = formatRoomProperty(room);
                    roomDetailsProperty.textContent = propertyText;
                    roomDetailsProperty.href = `/maintenance/property-assignments/rooms/${encodeURIComponent(room.roomId)}`;
                    roomDetailsPropertyRow.hidden = propertyText === "";
                }

                // =============================================
                // SAVE SELECTED ROOM ID
                // =============================================

                roomDetailsView.dataset.roomId = room.roomId;
                if (room.floorId) {
                    roomDetailsView.dataset.floorId = room.floorId;
                }

                // =============================================
                // SHOW PANEL
                // =============================================

                roomDetailsPanel.classList.add("visible");
                updateRoomDetailsPanelPosition();
            }

            const roomFocusSize = new THREE.Vector3();
            const roomFocusCenter = new THREE.Vector3();
            const roomPanelLocal = new THREE.Vector3();
            const roomPanelProjected = new THREE.Vector3();
            const roomPanelCorners = [
                new THREE.Vector3(),
                new THREE.Vector3(),
                new THREE.Vector3(),
                new THREE.Vector3(),
                new THREE.Vector3(),
                new THREE.Vector3(),
                new THREE.Vector3(),
                new THREE.Vector3(),
            ];

            function clearRoomDetailsPanelAnchor() {
                if (!roomDetailsPanel) {
                    return;
                }

                roomDetailsPanel.classList.remove("is-anchored");
                roomDetailsPanel.style.left = "";
                roomDetailsPanel.style.top = "";
            }

            function getSelectedRoomScreenRect(canvasRect, hostRect) {
                if (!selectedRoom?.geometry) {
                    return null;
                }

                selectedRoom.updateWorldMatrix(true, false);
                selectedRoom.geometry.computeBoundingBox();

                const min = selectedRoom.geometry.boundingBox.min;
                const max = selectedRoom.geometry.boundingBox.max;

                roomPanelCorners[0].set(min.x, min.y, min.z);
                roomPanelCorners[1].set(min.x, min.y, max.z);
                roomPanelCorners[2].set(min.x, max.y, min.z);
                roomPanelCorners[3].set(min.x, max.y, max.z);
                roomPanelCorners[4].set(max.x, min.y, min.z);
                roomPanelCorners[5].set(max.x, min.y, max.z);
                roomPanelCorners[6].set(max.x, max.y, min.z);
                roomPanelCorners[7].set(max.x, max.y, max.z);

                let left = Infinity;
                let top = Infinity;
                let right = -Infinity;
                let bottom = -Infinity;

                roomPanelCorners.forEach((corner) => {
                    roomPanelLocal.copy(corner).applyMatrix4(selectedRoom.matrixWorld);
                    roomPanelProjected.copy(roomPanelLocal).project(camera);

                    const x =
                        (roomPanelProjected.x * 0.5 + 0.5) * canvasRect.width +
                        (canvasRect.left - hostRect.left);
                    const y =
                        (-roomPanelProjected.y * 0.5 + 0.5) * canvasRect.height +
                        (canvasRect.top - hostRect.top);

                    left = Math.min(left, x);
                    right = Math.max(right, x);
                    top = Math.min(top, y);
                    bottom = Math.max(bottom, y);
                });

                return { left, top, right, bottom };
            }

            function updateRoomDetailsPanelPosition() {
                if (!roomDetailsPanel) {
                    return;
                }

                const isOpen =
                    roomDetailsPanel.classList.contains("visible") && selectedRoom;

                if (!isOpen) {
                    clearRoomDetailsPanelAnchor();
                    return;
                }

                const host = roomDetailsPanel.offsetParent;

                if (!host) {
                    return;
                }

                const hostRect = host.getBoundingClientRect();
                const canvasRect = renderer.domElement.getBoundingClientRect();
                const roomRect = getSelectedRoomScreenRect(canvasRect, hostRect);

                if (!roomRect) {
                    return;
                }

                const panelWidth = roomDetailsPanel.offsetWidth || 280;
                const panelHeight = roomDetailsPanel.offsetHeight || 300;
                const width = host.clientWidth;
                const height = host.clientHeight;
                const margin = 16;
                const topSafe = 64;
                const gap = 20;

                const spaceRight = width - margin - roomRect.right;
                const spaceLeft = roomRect.left - margin;
                const roomMidY = (roomRect.top + roomRect.bottom) / 2;

                let left;
                let top = roomMidY - panelHeight / 2;

                if (spaceRight >= panelWidth + gap) {
                    left = roomRect.right + gap;
                } else if (spaceLeft >= panelWidth + gap) {
                    left = roomRect.left - panelWidth - gap;
                } else if (spaceRight >= spaceLeft) {
                    left = Math.min(
                        roomRect.right + gap,
                        width - panelWidth - margin,
                    );
                } else {
                    left = Math.max(roomRect.left - panelWidth - gap, margin);
                }

                left = Math.min(
                    Math.max(left, margin),
                    Math.max(margin, width - panelWidth - margin),
                );
                top = Math.min(
                    Math.max(top, topSafe),
                    Math.max(topSafe, height - panelHeight - margin),
                );

                const overlapsRoom = (panelLeft, panelTop) => {
                    const panelRight = panelLeft + panelWidth;
                    const panelBottom = panelTop + panelHeight;

                    return !(
                        panelRight < roomRect.left ||
                        panelLeft > roomRect.right ||
                        panelBottom < roomRect.top ||
                        panelTop > roomRect.bottom
                    );
                };

                if (overlapsRoom(left, top)) {
                    if (height - margin - roomRect.bottom >= panelHeight + gap) {
                        top = roomRect.bottom + gap;
                        left = Math.min(
                            Math.max(roomRect.left, margin),
                            width - panelWidth - margin,
                        );
                    } else if (roomRect.top - topSafe >= panelHeight + gap) {
                        top = roomRect.top - panelHeight - gap;
                        left = Math.min(
                            Math.max(roomRect.left, margin),
                            width - panelWidth - margin,
                        );
                    }
                }

                roomDetailsPanel.classList.add("is-anchored");
                roomDetailsPanel.style.left = `${left}px`;
                roomDetailsPanel.style.top = `${top}px`;
            }

            // =====================================================
            // ROOM MATERIALS
            // Uses building-layout room_color (not report status)
            // =====================================================

            function parseRoomLayoutColor(color) {
                const raw = String(color || "").trim();
                const hex = raw.replace(/^#/, "");

                if (/^[0-9a-fA-F]{6}$/.test(hex)) {
                    return parseInt(hex, 16);
                }

                if (/^[0-9a-fA-F]{3}$/.test(hex)) {
                    return parseInt(
                        hex
                            .split("")
                            .map((c) => c + c)
                            .join(""),
                        16,
                    );
                }

                // Match building layout default
                return 0x60a5fa;
            }

            function createRoomLayoutMaterial(layoutColor) {
                const colorHex = parseRoomLayoutColor(layoutColor);

                return new THREE.MeshPhysicalMaterial({
                    color: colorHex,

                    emissive: colorHex,

                    emissiveIntensity: 0.28,

                    transparent: true,

                    opacity: 0.82,

                    roughness: 0.5,

                    metalness: 0.0,

                    clearcoat: 0.08,

                    clearcoatRoughness: 0.55,

                    side: THREE.DoubleSide,

                    depthWrite: true,
                });
            }

            // =====================================================
            // ROOM BORDER MATERIAL
            // Soft clay seams (matches exterior shell edges)
            // =====================================================

            const roomEdgeMaterial = new THREE.LineBasicMaterial({
                color: 0xcbd5e1,

                transparent: true,

                opacity: 0.45,
            });

            // =====================================================
            // CREATE INDIVIDUAL ROOM
            //
            // name        = room name
            // x           = horizontal position
            // y           = floor height
            // z           = depth position
            // width       = room width
            // depth       = room depth
            // layoutColor = building layout room_color
            // =====================================================

            function createRoom(name, x, y, z, width, depth, layoutColor = "#60A5FA") {
                // =================================================
                // ROOM HEIGHT
                // =================================================

                const roomHeight = 1.8;

                // =================================================
                // CREATE ROOM GEOMETRY
                // =================================================

                const geometry = new THREE.BoxGeometry(width, roomHeight, depth);

                // =================================================
                // CREATE ROOM MESH (layout color from Building Layout)
                // =================================================

                const layoutColorHex = parseRoomLayoutColor(layoutColor);

                const room = new THREE.Mesh(
                    geometry,
                    createRoomLayoutMaterial(layoutColor),
                );

                // =================================================
                // ROOM POSITION
                // =================================================

                room.position.set(x, y + 0.06 + roomHeight / 2, z);

                // =================================================
                // ENABLE SHADOWS
                // =================================================

                room.castShadow = true;

                room.receiveShadow = true;

                // =================================================
                // SAVE ROOM INFORMATION
                // THIS WILL BE USED IN PHASE 3 FOR CLICKING ROOMS
                // =================================================

                room.userData = {
                    type: "room",
                    name: name,
                    layoutColor: layoutColor,
                    layoutColorHex: layoutColorHex,
                };

                // =================================================
                // CREATE ROOM OUTLINE
                // MAKES EACH ROOM EASIER TO SEE
                // =================================================

                const edges = new THREE.EdgesGeometry(geometry);

                const outline = new THREE.LineSegments(edges, roomEdgeMaterial);

                room.add(outline);

                return room;
            }

            // =====================================================
            // PHASE 2.3
            // INDEPENDENT FLOOR ALIGNMENT AND STACKING
            // =====================================================

            // =====================================================
            // CONFIGURATION
            // =====================================================

            // Converts your saved 2D blueprint pixels into 3D units.
            const BLUEPRINT_SCALE = 0.02;

            // Vertical distance between each floor.

            // Minimum visible room size.
            const MIN_ROOM_SIZE = 0.5;

            const FLOOR_BASE_HEIGHT = 0.15;

            // Increase this number to create more space between floors
            const FLOOR_VERTICAL_GAP = 4;

            // =====================================================
            // PHASE 7.3
            // CREATE FLOATING FLOOR LABEL
            // =====================================================

            function createFloorLabel(text) {
                // Create canvas for the label
                const canvas = document.createElement("canvas");

                canvas.width = 512;
                canvas.height = 128;

                const context = canvas.getContext("2d");

                // =================================================
                // LABEL BACKGROUND
                // =================================================

                context.fillStyle = "rgba(2, 6, 23, 0.85)";

                context.fillRect(0, 0, canvas.width, canvas.height);

                // =================================================
                // LABEL BORDER
                // =================================================

                context.strokeStyle = "#94a3b8";

                context.lineWidth = 4;

                context.strokeRect(2, 2, canvas.width - 4, canvas.height - 4);

                // =================================================
                // LABEL TEXT
                // =================================================

                context.font = "bold 48px Arial";

                context.fillStyle = "white";

                context.textAlign = "center";

                context.textBaseline = "middle";

                context.fillText(text, canvas.width / 2, canvas.height / 2);

                // =================================================
                // CONVERT CANVAS INTO THREE.JS TEXTURE
                // =================================================

                const texture = new THREE.CanvasTexture(canvas);

                texture.colorSpace = THREE.SRGBColorSpace;

                // =================================================
                // CREATE SPRITE MATERIAL
                // =================================================

                const material = new THREE.SpriteMaterial({
                    map: texture,

                    transparent: true,

                    depthTest: false,
                });

                // =================================================
                // CREATE SPRITE
                // =================================================

                const label = new THREE.Sprite(material);

                label.scale.set(4, 1, 1);

                return label;
            }

            // =====================================================
            // PHASE 7.10
            // CREATE DYNAMIC ROOM NAME LABEL
            // ONLY VISIBLE WHEN A SINGLE FLOOR IS SELECTED
            // =====================================================

            function createRoomLabel(text) {
                // Create canvas for room name
                const canvas = document.createElement("canvas");

                canvas.width = 512;
                canvas.height = 128;

                const context = canvas.getContext("2d");

                // =================================================
                // LABEL BACKGROUND
                // =================================================

                context.fillStyle = "rgba(2, 6, 23, 0.88)";

                context.beginPath();

                context.roundRect(4, 4, canvas.width - 8, canvas.height - 8, 24);

                context.fill();

                // =================================================
                // LABEL BORDER
                // =================================================

                context.strokeStyle = "rgba(148, 163, 184, 0.85)";

                context.lineWidth = 4;

                context.stroke();

                // =================================================
                // ROOM NAME
                // =================================================

                context.font = "bold 42px Arial";

                context.fillStyle = "white";

                context.textAlign = "center";

                context.textBaseline = "middle";

                context.fillText(text || "Room", canvas.width / 2, canvas.height / 2);

                // =================================================
                // CONVERT CANVAS TO THREE.JS TEXTURE
                // =================================================

                const texture = new THREE.CanvasTexture(canvas);

                texture.colorSpace = THREE.SRGBColorSpace;

                // =================================================
                // CREATE SPRITE
                // =================================================

                const material = new THREE.SpriteMaterial({
                    map: texture,

                    transparent: true,

                    depthTest: false,

                    depthWrite: false,
                });

                const label = new THREE.Sprite(material);

                // =================================================
                // PHASE 7.10
                // IDENTIFY AS ROOM LABEL
                // =================================================

                label.userData.type = "room-label";

                // Smaller than floor labels
                label.scale.set(2.8, 0.7, 1);

                // Hidden by default because All Floors is default
                label.visible = false;

                return label;
            }

            // =====================================================
            // CRITICAL ROOM LIGHT-BULB INDICATOR
            // Infinite pop-up above rooms with urgent reports
            // =====================================================

            function createCriticalLightBulb() {
                const canvas = document.createElement("canvas");
                canvas.width = 128;
                canvas.height = 128;

                const ctx = canvas.getContext("2d");

                // Soft red glow (outside the bulb)
                const glow = ctx.createRadialGradient(64, 64, 8, 64, 64, 60);
                glow.addColorStop(0, "rgba(248, 113, 113, 0.95)");
                glow.addColorStop(0.45, "rgba(239, 68, 68, 0.45)");
                glow.addColorStop(1, "rgba(239, 68, 68, 0)");
                ctx.fillStyle = glow;
                ctx.fillRect(0, 0, 128, 128);

                // Bulb glass
                ctx.beginPath();
                ctx.arc(64, 48, 26, Math.PI * 0.15, Math.PI * 0.85, true);
                ctx.lineTo(78, 78);
                ctx.lineTo(50, 78);
                ctx.closePath();
                ctx.fillStyle = "#fef08a";
                ctx.fill();
                ctx.strokeStyle = "#fbbf24";
                ctx.lineWidth = 3;
                ctx.stroke();

                // Inner shine
                ctx.beginPath();
                ctx.arc(56, 42, 8, 0, Math.PI * 2);
                ctx.fillStyle = "rgba(255, 255, 255, 0.55)";
                ctx.fill();

                // Filament
                ctx.beginPath();
                ctx.moveTo(56, 52);
                ctx.quadraticCurveTo(64, 62, 72, 52);
                ctx.strokeStyle = "#ef4444";
                ctx.lineWidth = 2.5;
                ctx.stroke();

                // Base
                ctx.fillStyle = "#94a3b8";
                ctx.fillRect(52, 78, 24, 8);
                ctx.fillStyle = "#64748b";
                ctx.fillRect(54, 86, 20, 6);
                ctx.fillStyle = "#475569";
                ctx.fillRect(56, 92, 16, 5);

                const texture = new THREE.CanvasTexture(canvas);
                texture.colorSpace = THREE.SRGBColorSpace;

                const material = new THREE.SpriteMaterial({
                    map: texture,
                    transparent: true,
                    // Occluded by upper floor slabs when looking from above
                    depthTest: true,
                    depthWrite: false,
                });

                const bulb = new THREE.Sprite(material);
                bulb.scale.set(1.15, 1.15, 1);
                bulb.userData.type = "critical-bulb";
                // Just above the room top (room half-height ≈ 0.9)
                // so it reads as part of that room, not floating away
                bulb.userData.baseY = 1.2;
                bulb.position.set(0, bulb.userData.baseY, 0);

                return bulb;
            }

            // =====================================================
            // MAINTENANCE ROOM TOOL INDICATOR
            // Crossed wrenches icon (matches provided tool graphic)
            // =====================================================

            const maintenanceWrenchUrl = @json(asset('images/maintenance-wrenches.png'));

            function createMaintenanceToolIcon() {
                const material = new THREE.SpriteMaterial({
                    transparent: true,
                    depthTest: true,
                    depthWrite: false,
                    opacity: 0,
                });

                const tool = new THREE.Sprite(material);
                tool.scale.set(1.15, 1.15, 1);
                tool.userData.type = "maintenance-tool";
                tool.userData.baseY = 1.2;
                tool.position.set(0, tool.userData.baseY, 0);

                const img = new Image();
                img.crossOrigin = "anonymous";
                img.onload = () => {
                    const canvas = document.createElement("canvas");
                    canvas.width = 128;
                    canvas.height = 128;
                    const ctx = canvas.getContext("2d");

                    // Fit icon into canvas with padding
                    const pad = 8;
                    ctx.drawImage(img, pad, pad, 128 - pad * 2, 128 - pad * 2);

                    // Make near-white background transparent
                    const imageData = ctx.getImageData(0, 0, 128, 128);
                    const data = imageData.data;
                    for (let i = 0; i < data.length; i += 4) {
                        if (data[i] > 245 && data[i + 1] > 245 && data[i + 2] > 245) {
                            data[i + 3] = 0;
                        }
                    }
                    ctx.putImageData(imageData, 0, 0);

                    const texture = new THREE.CanvasTexture(canvas);
                    texture.colorSpace = THREE.SRGBColorSpace;
                    material.map = texture;
                    material.opacity = 1;
                    material.needsUpdate = true;
                };
                img.src = maintenanceWrenchUrl;

                return tool;
            }

            // =====================================================
            // SHARED FLOOR FOOTPRINT
            // Match Building Layout canvas so rooms on the layout
            // edge also sit on the 3D floor-plate edge.
            // =====================================================

            const BLUEPRINT_CANVAS_WIDTH = 1180;
            const BLUEPRINT_CANVAS_HEIGHT = 720;

            const sharedFloorWidth = Math.max(
                BLUEPRINT_CANVAS_WIDTH * BLUEPRINT_SCALE,
                4,
            );
            const sharedFloorDepth = Math.max(
                BLUEPRINT_CANVAS_HEIGHT * BLUEPRINT_SCALE,
                4,
            );
            const sharedFloorCenterX = BLUEPRINT_CANVAS_WIDTH / 2;
            const sharedFloorCenterY = BLUEPRINT_CANVAS_HEIGHT / 2;

            // =====================================================
            // LOOP THROUGH EACH DATABASE FLOOR
            // =====================================================

            building3DData.forEach((floorData, floorIndex) => {
                // =====================================================
                // PHASE 7.4
                // CREATE GROUP FOR THIS FLOOR
                // =====================================================

                const floorGroup = new THREE.Group();

                floorGroup.userData = {
                    type: "floor",
                    floorId: String(floorData.id),
                    floorName: floorData.name,
                };

                building.add(floorGroup);

                buildingFloorGroups.set(String(floorData.id), floorGroup);

                const floorY = floorIndex * FLOOR_VERTICAL_GAP;

                // =================================================
                // SKIP EMPTY FLOORS
                // =================================================

                if (!floorData.rooms || floorData.rooms.length === 0) {
                    return;
                }

                // =================================================
                // STEP 1 / 2
                // Use Building Layout canvas center for all floors
                // so edge rooms stay on the edge in 3D
                // =================================================

                const floorCenterX = sharedFloorCenterX;

                const floorCenterY = sharedFloorCenterY;

                // =================================================
                // PHASE 7.1
                // SHARED SLAB SIZE = Building Layout canvas
                // =================================================

                const floorWidth = sharedFloorWidth;

                const floorDepth = sharedFloorDepth;

                // =================================================
                // PHASE 7.1
                // CREATE ARCHITECTURAL FLOOR SLAB
                // =================================================

                const slabGeometry = new THREE.BoxGeometry(
                    floorWidth + 1,
                    0.12,
                    floorDepth + 1,
                );

                const slabMaterial = new THREE.MeshBasicMaterial({
                    color: 0x03060c,

                    side: THREE.DoubleSide,

                    depthWrite: true,
                });

                const floorSlab = new THREE.Mesh(slabGeometry, slabMaterial);

                floorSlab.position.set(0, floorY, 0);

                floorSlab.receiveShadow = true;

                floorGroup.add(floorSlab);

                const slabGridMap = blueprintTexture.clone();
                slabGridMap.wrapS = THREE.RepeatWrapping;
                slabGridMap.wrapT = THREE.RepeatWrapping;
                slabGridMap.repeat.set(1.15, 1.15);
                slabGridMap.needsUpdate = true;

                const slabGrid = new THREE.Mesh(
                    new THREE.PlaneGeometry(floorWidth + 1, floorDepth + 1),
                    new THREE.MeshBasicMaterial({
                        map: slabGridMap,
                        color: 0xffffff,
                        transparent: true,
                        opacity: 0.9,
                        blending: THREE.AdditiveBlending,
                        depthWrite: false,
                        toneMapped: false,
                        side: THREE.DoubleSide,
                    }),
                );

                slabGrid.rotation.x = -Math.PI / 2;
                slabGrid.position.set(0, floorY + 0.062, 0);
                floorGroup.add(slabGrid);

                // =================================================
                // PHASE 7.1
                // CREATE CYAN FLOOR PERIMETER
                // =================================================

                const slabEdges = new THREE.EdgesGeometry(slabGeometry);

                const slabEdgeMaterial = new THREE.LineBasicMaterial({
                    color: 0x22d3ee,

                    transparent: true,

                    opacity: 0.42,
                });

                const slabOutline = new THREE.LineSegments(slabEdges, slabEdgeMaterial);

                // =================================================
                // FIX
                // KEEP FLOOR BORDER AT THE SAME HEIGHT AS FLOOR SLAB
                // =================================================

                slabOutline.position.set(0, floorY, 0);

                floorGroup.add(slabOutline);

                // =================================================
                // PHASE 7.3
                // ADD DYNAMIC FLOATING FLOOR LABEL
                // =================================================

                const floorLabel = createFloorLabel(
                    floorData.name || `Floor ${floorIndex + 1}`,
                );

                // =================================================
                // POSITION LABEL BESIDE THE FLOOR
                // =================================================

                floorLabel.position.set(
                    // Right side of the floor (matches front-right side cam)
                    floorWidth / 2 + 2.5,

                    // Slightly above the floor
                    floorY + 0.8,

                    // Center depth
                    0,
                );

                // =================================================
                // ADD LABEL TO BUILDING
                // =================================================

                floorGroup.add(floorLabel);

                // =================================================
                // STEP 4
                // CREATE ROOMS FOR THIS FLOOR
                // =================================================

                floorData.rooms.forEach((roomData) => {
                    // =============================================
                    // ORIGINAL 2D BLUEPRINT DATA
                    // =============================================

                    const originalX = Number(roomData.x) || 0;

                    const originalY = Number(roomData.y) || 0;

                    const originalWidth = Number(roomData.width) || 100;

                    const originalHeight = Number(roomData.height) || 100;

                    // =============================================
                    // CONVERT ROOM SIZE TO THREE.JS UNITS
                    // =============================================

                    const roomWidth = Math.max(
                        originalWidth * BLUEPRINT_SCALE,

                        MIN_ROOM_SIZE,
                    );

                    const roomDepth = Math.max(
                        originalHeight * BLUEPRINT_SCALE,

                        MIN_ROOM_SIZE,
                    );

                    // =============================================
                    // FIND CENTER OF ROOM IN 2D BLUEPRINT
                    //
                    // Your database position represents the
                    // top left corner.
                    //
                    // Three.js positions boxes from the center.
                    // =============================================

                    const roomCenterX = originalX + originalWidth / 2;

                    const roomCenterY = originalY + originalHeight / 2;

                    // =============================================
                    // CENTER ROOM RELATIVE TO ITS OWN FLOOR
                    // =============================================

                    const roomX = (roomCenterX - floorCenterX) * BLUEPRINT_SCALE;

                    const roomZ = (roomCenterY - floorCenterY) * BLUEPRINT_SCALE;

                    // =============================================
                    // CREATE ROOM
                    // Fill color comes from Building Layout room_color
                    // Report status stays in tooltip / details panel
                    // =============================================

                    const roomMesh = createRoom(
                        roomData.name,
                        roomX,
                        floorY,
                        roomZ,
                        roomWidth,
                        roomDepth,
                        roomData.color || "#60A5FA",
                    );

                    // =================================================
                    // PHASE 7.10
                    // CREATE ROOM NAME LABEL
                    // =================================================

                    const roomLabel = createRoomLabel(roomData.name);

                    // Position label above the room
                    roomLabel.position.set(0, 1.8, 0);

                    // Add label directly to room mesh
                    // This makes the label follow the room position
                    roomMesh.add(roomLabel);

                    // =================================================
                    // STATUS INDICATORS
                    // Critical     -> light bulb (urgent reports)
                    // Maintenance  -> tool (open reports / under maintenance)
                    // =================================================

                    const urgentCount = Number(roomData.urgentReportCount || 0);
                    const activeCount = Number(roomData.activeReportCount || 0);
                    const maintenanceCount = Number(
                        roomData.maintenanceEquipmentCount || 0,
                    );
                    const roomStatus = String(roomData.status || "");

                    const isCritical =
                        urgentCount > 0 || roomStatus === "critical";

                    const isMaintenance =
                        !isCritical &&
                        (activeCount > 0 ||
                            maintenanceCount > 0 ||
                            roomStatus === "needs-repair" ||
                            roomStatus === "maintenance");

                    if (isCritical) {
                        const criticalBulb = createCriticalLightBulb();
                        roomMesh.add(criticalBulb);
                        criticalRoomIndicators.push(criticalBulb);
                    } else if (isMaintenance) {
                        const maintenanceTool = createMaintenanceToolIcon();
                        roomMesh.add(maintenanceTool);
                        maintenanceRoomIndicators.push(maintenanceTool);
                    }

                    roomMesh.userData.originalEmissive =
                        roomMesh.material.emissive.getHex();

                    roomMesh.userData.originalEmissiveIntensity =
                        roomMesh.material.emissiveIntensity;

                    // =================================================
                    // PHASE 7.4
                    // ADD ROOM TO ITS FLOOR GROUP
                    // =================================================

                    floorGroup.add(roomMesh);

                    // =============================================
                    // PHASE 7.8
                    // SAVE DATABASE INFORMATION FOR ROOM PANEL
                    // =============================================

                    roomMesh.userData = {
                        type: "room",

                        roomId: roomData.id,

                        roomName: roomData.name,

                        roomType: roomData.type,

                        roomStatus: roomData.status,

                        floorId: floorData.id,

                        floorName: floorData.name,

                        layoutColor: roomData.color || "#60A5FA",

                        layoutColorHex: parseRoomLayoutColor(
                            roomData.color || "#60A5FA",
                        ),

                        // =========================================
                        // PHASE 7.8
                        // ROOM MAINTENANCE INFORMATION
                        // =========================================

                        activeReportCount: roomData.activeReportCount || 0,

                        urgentReportCount: roomData.urgentReportCount || 0,

                        maintenanceEquipmentCount:
                            roomData.maintenanceEquipmentCount || 0,

                        property: roomData.property || null,

                        originalEmissive: roomMesh.material.emissive.getHex(),

                        originalEmissiveIntensity: roomMesh.material.emissiveIntensity,
                    };

                    clickableRooms.push(roomMesh);

                    // =============================================
                    // APPLY SAVED ROOM ROTATION
                    // =============================================

                    roomMesh.rotation.y = THREE.MathUtils.degToRad(
                        roomData.rotation || 0,
                    );
                });
            });

            // =====================================================
            // PHASE 8.1 PART 2
            // CALCULATE COMPLETE INTERIOR BUILDING BOUNDS
            // =====================================================

            function getBuildingInteriorBounds() {
                building.updateMatrixWorld(true);

                const bounds = new THREE.Box3().setFromObject(building);

                if (bounds.isEmpty()) {
                    return null;
                }

                return bounds;
            }

            // =====================================================
            // PHASE 8.1 PART 2
            // CREATE AUTO FITTING EXTERIOR BUILDING
            // =====================================================

            // =====================================================
            // PHASE 8.3 PART 1
            // BASIC ARCHITECTURAL EXTERIOR SHAPE
            //
            // REFERENCE:
            // ROBINSONS ORMOC CENTRUM / STI COLLEGE ORMOC
            //
            // IMPORTANT BUILDING LAYOUT:
            //
            // GROUND FLOOR
            // Robinsons / commercial area
            //
            // SECOND AND THIRD FLOOR
            // STI College Ormoc
            //
            // FRONT
            // Robinsons commercial facade
            // Large central arched architectural feature
            //
            // BACK
            // Actual STI College entrance
            // Staircase from ground level to second floor
            // =====================================================

            function createDynamicBuildingExterior() {
                const bounds = getBuildingInteriorBounds();

                if (!bounds) {
                    console.warn("Could not calculate building interior bounds.");

                    return;
                }

                // =================================================
                // GET COMPLETE INTERIOR SIZE AND CENTER
                // =================================================

                const size = bounds.getSize(new THREE.Vector3());

                const center = bounds.getCenter(new THREE.Vector3());

                // =================================================
                // EXTRA SPACE AROUND INTERIOR
                // =================================================

                const paddingX = 2.5;
                const paddingY = 1.5;
                const paddingZ = 2.5;

                const buildingWidth = size.x + paddingX;

                const buildingHeight = size.y + paddingY;

                const buildingDepth = size.z + paddingZ;

                // =================================================
                // BUILDING DIRECTION
                //
                // CURRENT ASSUMPTION:
                //
                // +Z = FRONT
                // -Z = BACK
                //
                // Robinsons facade is located at +Z.
                // STI entrance will eventually be located at -Z.
                // =================================================

                const frontZ = center.z + buildingDepth / 2;

                const backZ = center.z - buildingDepth / 2;

                // =================================================
                // 1. MAIN LONG BUILDING BODY
                //
                // Represents the overall rectangular structure.
                //
                // This contains:
                //
                // Ground Floor
                // Robinsons / Commercial
                //
                // Upper Floors
                // STI College Ormoc
                // =================================================

                const exteriorMainBuilding = createExteriorSection(
                    buildingWidth,

                    buildingHeight,

                    buildingDepth,

                    center.x,

                    center.y,

                    center.z,
                );

                exteriorMainBuilding.userData.isBuildingExterior = true;

                // =================================================
                // 2. FRONT GROUND FLOOR COMMERCIAL BAND
                //
                // This represents the long Robinsons frontage
                // visible across the ground floor.
                //
                // IMPORTANT:
                // This is NOT the STI College entrance.
                // =================================================

                const commercialBandHeight = Math.max(buildingHeight * 0.28, 1.2);

                const commercialBandDepth = Math.max(buildingDepth * 0.08, 0.6);

                const commercialBand = createExteriorSection(
                    buildingWidth * 0.96,

                    commercialBandHeight,

                    commercialBandDepth,

                    center.x,

                    center.y - buildingHeight / 2 + commercialBandHeight / 2,

                    frontZ + commercialBandDepth / 2,
                );

                commercialBand.userData.isBuildingExterior = true;

                // =================================================
                // 3. SECOND FLOOR FRONT FACADE
                //
                // Represents the long horizontal upper facade
                // occupied partly by STI College.
                // =================================================

                const upperFacadeHeight = Math.max(buildingHeight * 0.22, 1);

                const upperFacadeDepth = Math.max(buildingDepth * 0.035, 0.3);

                const secondFloorFacade = createExteriorSection(
                    buildingWidth * 0.94,

                    upperFacadeHeight,

                    upperFacadeDepth,

                    center.x,

                    center.y,

                    frontZ + upperFacadeDepth / 2,
                );

                secondFloorFacade.userData.isBuildingExterior = true;

                // =================================================
                // 4. THIRD FLOOR / UPPER FACADE BAND
                //
                // Creates the upper horizontal shape visible
                // along the entire building.
                // =================================================

                const thirdFloorFacade = createExteriorSection(
                    buildingWidth * 0.96,

                    upperFacadeHeight * 0.8,

                    upperFacadeDepth,

                    center.x,

                    center.y + buildingHeight * 0.3,

                    frontZ + upperFacadeDepth / 2,
                );

                thirdFloorFacade.userData.isBuildingExterior = true;

                // =================================================
                // 5. FRONT CENTRAL ROBINSONS ARCH FEATURE
                //
                // IMPORTANT:
                //
                // This is NOT the STI College entrance.
                //
                // This represents the large curved architectural
                // feature visible at the center of the Robinsons
                // Ormoc Centrum front facade.
                //
                // The actual STI entrance is located at the BACK
                // of the building.
                // =================================================

                const frontFeatureWidth = Math.max(buildingWidth * 0.18, 3);

                const frontFeatureDepth = Math.max(buildingDepth * 0.05, 0.4);

                const frontFeatureHeight = buildingHeight * 1.12;

                // =================================================
                // CREATE THE CURVED FRONT ARCH
                // =================================================

                const frontCentralArch = createExteriorFrontArch(
                    frontFeatureWidth,

                    frontFeatureHeight,

                    frontFeatureDepth,

                    center.x,

                    center.y + (frontFeatureHeight - buildingHeight) / 2,

                    frontZ + frontFeatureDepth / 2,
                );

                // =================================================
                // CENTRAL GLASS FACADE
                //
                // The real building has a large glass section
                // underneath the curved architectural arch.
                //
                // This is only a simplified blueprint representation.
                // =================================================

                const glassWidth = frontFeatureWidth * 0.62;

                const glassHeight = frontFeatureHeight * 0.58;

                const glassDepth = 0.08;

                const frontGlassFacade = createExteriorSection(
                    glassWidth,

                    glassHeight,

                    glassDepth,

                    center.x,

                    center.y + frontFeatureHeight * 0.02,

                    frontZ + frontFeatureDepth + 0.02,
                );

                frontGlassFacade.userData.isBuildingExterior = true;

                // =================================================
                // PHASE 8.3 PART 2
                // FRONT HORIZONTAL ARCHITECTURAL BANDS
                //
                // These represent the long horizontal layers
                // visible across the Robinsons Ormoc facade.
                // =================================================

                const facadeBandDepth = Math.max(buildingDepth * 0.045, 0.35);

                // =================================================
                // LOWER FRONT BAND
                // =================================================

                const lowerFacadeBand = createExteriorSection(
                    buildingWidth * 0.98,

                    0.32,

                    facadeBandDepth,

                    center.x,

                    center.y - buildingHeight * 0.18,

                    frontZ + facadeBandDepth / 2 + 0.05,
                );

                lowerFacadeBand.userData.isBuildingExterior = true;

                // =================================================
                // MIDDLE FRONT BAND
                // =================================================

                const middleFacadeBand = createExteriorSection(
                    buildingWidth * 0.96,

                    0.22,

                    facadeBandDepth * 0.8,

                    center.x,

                    center.y + buildingHeight * 0.1,

                    frontZ + facadeBandDepth / 2 + 0.03,
                );

                middleFacadeBand.userData.isBuildingExterior = true;

                // =================================================
                // UPPER FRONT BAND
                // =================================================

                const upperFacadeBand = createExteriorSection(
                    buildingWidth * 0.98,

                    0.28,

                    facadeBandDepth,

                    center.x,

                    center.y + buildingHeight * 0.38,

                    frontZ + facadeBandDepth / 2 + 0.05,
                );

                upperFacadeBand.userData.isBuildingExterior = true;

                // =================================================
                // PHASE 8.3 PART 2.1
                // WRAPAROUND HORIZONTAL ARCHITECTURAL BANDS
                //
                // Extends the three existing front facade bands
                // around the LEFT SIDE, RIGHT SIDE, and BACK.
                //
                // IMPORTANT:
                // All bands use the exact same Y positions as the
                // existing front bands so they connect visually
                // around the corners of the building.
                //
                // +Z = FRONT
                // -Z = BACK
                // =================================================

                // =================================================
                // SHARED BAND POSITIONS
                // Must match the existing front facade bands.
                // =================================================

                const lowerBandY = center.y - buildingHeight * 0.18;

                const middleBandY = center.y + buildingHeight * 0.1;

                const upperBandY = center.y + buildingHeight * 0.38;

                // =================================================
                // SIDE BAND THICKNESS
                //
                // Since the side walls run along the Z axis,
                // the thin dimension is now X instead of Z.
                // =================================================

                const sideBandThickness = facadeBandDepth;

                // =================================================
                // BACK BAND OFFSET
                // Places the bands slightly outside the back wall.
                // =================================================

                const backBandZ = backZ - facadeBandDepth / 2 - 0.05;

                // =================================================
                // LEFT SIDE POSITION
                // =================================================

                const leftBandX =
                    center.x - buildingWidth / 2 - sideBandThickness / 2 - 0.05;

                // =================================================
                // RIGHT SIDE POSITION
                // =================================================

                const rightBandX =
                    center.x + buildingWidth / 2 + sideBandThickness / 2 + 0.05;

                // =================================================
                // 1. LOWER BACK BAND
                // =================================================

                const lowerBackFacadeBand = createExteriorSection(
                    buildingWidth * 0.98,

                    0.32,

                    facadeBandDepth,

                    center.x,

                    lowerBandY,

                    backBandZ,
                );

                lowerBackFacadeBand.userData.isBuildingExterior = true;

                // =================================================
                // 2. MIDDLE BACK BAND
                // =================================================

                const middleBackFacadeBand = createExteriorSection(
                    buildingWidth * 0.96,

                    0.22,

                    facadeBandDepth * 0.8,

                    center.x,

                    middleBandY,

                    backZ - (facadeBandDepth * 0.8) / 2 - 0.03,
                );

                middleBackFacadeBand.userData.isBuildingExterior = true;

                // =================================================
                // 3. UPPER BACK BAND
                // =================================================

                const upperBackFacadeBand = createExteriorSection(
                    buildingWidth * 0.98,

                    0.28,

                    facadeBandDepth,

                    center.x,

                    upperBandY,

                    backBandZ,
                );

                upperBackFacadeBand.userData.isBuildingExterior = true;

                // =================================================
                // 4. LOWER LEFT SIDE BAND
                //
                // Width is thin because this is attached to the
                // left wall.
                //
                // Depth runs almost the entire building length.
                // =================================================

                const lowerLeftFacadeBand = createExteriorSection(
                    sideBandThickness,

                    0.32,

                    buildingDepth * 0.98,

                    leftBandX,

                    lowerBandY,

                    center.z,
                );

                lowerLeftFacadeBand.userData.isBuildingExterior = true;

                // =================================================
                // 5. MIDDLE LEFT SIDE BAND
                // =================================================

                const middleLeftFacadeBand = createExteriorSection(
                    sideBandThickness * 0.8,

                    0.22,

                    buildingDepth * 0.96,

                    center.x - buildingWidth / 2 - (sideBandThickness * 0.8) / 2 - 0.03,

                    middleBandY,

                    center.z,
                );

                middleLeftFacadeBand.userData.isBuildingExterior = true;

                // =================================================
                // 6. UPPER LEFT SIDE BAND
                // =================================================

                const upperLeftFacadeBand = createExteriorSection(
                    sideBandThickness,

                    0.28,

                    buildingDepth * 0.98,

                    leftBandX,

                    upperBandY,

                    center.z,
                );

                upperLeftFacadeBand.userData.isBuildingExterior = true;

                // =================================================
                // 7. LOWER RIGHT SIDE BAND
                // =================================================

                const lowerRightFacadeBand = createExteriorSection(
                    sideBandThickness,

                    0.32,

                    buildingDepth * 0.98,

                    rightBandX,

                    lowerBandY,

                    center.z,
                );

                lowerRightFacadeBand.userData.isBuildingExterior = true;

                // =================================================
                // 8. MIDDLE RIGHT SIDE BAND
                // =================================================

                const middleRightFacadeBand = createExteriorSection(
                    sideBandThickness * 0.8,

                    0.22,

                    buildingDepth * 0.96,

                    center.x + buildingWidth / 2 + (sideBandThickness * 0.8) / 2 + 0.03,

                    middleBandY,

                    center.z,
                );

                middleRightFacadeBand.userData.isBuildingExterior = true;

                // =================================================
                // 9. UPPER RIGHT SIDE BAND
                // =================================================

                const upperRightFacadeBand = createExteriorSection(
                    sideBandThickness,

                    0.28,

                    buildingDepth * 0.98,

                    rightBandX,

                    upperBandY,

                    center.z,
                );

                upperRightFacadeBand.userData.isBuildingExterior = true;

                // =================================================
                // PHASE 8.3 PART 3
                // WINDOW ROWS AND FACADE SEGMENTATION
                //
                // Adds simplified architectural window modules
                // to the second and third floor.
                //
                // IMPORTANT:
                //
                // FRONT:
                // Window rows are split into LEFT and RIGHT sections
                // because the central Robinsons arch occupies the
                // middle of the facade.
                //
                // LEFT / RIGHT SIDES:
                // Window rows continue along the building depth.
                //
                // BACK:
                // Window rows are also split around the center because
                // the future STI College entrance and staircase will
                // occupy the central rear section.
                // =================================================

                // =================================================
                // WINDOW CONFIGURATION
                // =================================================

                const windowHeight = Math.max(buildingHeight * 0.11, 0.55);

                const windowDepth = Math.max(facadeBandDepth * 0.35, 0.08);

                const windowGap = Math.max(buildingWidth * 0.012, 0.18);

                // =================================================
                // FLOOR WINDOW Y POSITIONS
                //
                // These sit between the horizontal facade bands.
                // =================================================

                const secondFloorWindowY = center.y - buildingHeight * 0.03;

                const thirdFloorWindowY = center.y + buildingHeight * 0.25;

                // =================================================
                // FRONT WINDOW AREA
                //
                // The center is intentionally left empty because
                // the large Robinsons architectural arch and glass
                // facade already occupy this section.
                // =================================================

                const frontWindowSideWidth = (buildingWidth - frontFeatureWidth) / 2;

                // =================================================
                // HELPER FUNCTION
                // CREATE FRONT OR BACK WINDOW ROW
                //
                // Creates individual rectangular window modules
                // across a horizontal section.
                // =================================================

                function createHorizontalWindowRow(
                    startX,
                    totalWidth,
                    y,
                    z,
                    faceDirection,
                ) {
                    const approximateWindowWidth = Math.max(buildingWidth * 0.055, 0.7);

                    const windowCount = Math.max(
                        2,
                        Math.floor(totalWidth / (approximateWindowWidth + windowGap)),
                    );

                    const usableWidth = totalWidth - windowGap * (windowCount - 1);

                    const actualWindowWidth = usableWidth / windowCount;

                    for (let i = 0; i < windowCount; i++) {
                        const windowX =
                            startX +
                            actualWindowWidth / 2 +
                            i * (actualWindowWidth + windowGap);

                        const windowSection = createExteriorSection(
                            actualWindowWidth,

                            windowHeight,

                            windowDepth,

                            windowX,

                            y,

                            z,
                        );

                        windowSection.userData.isBuildingExterior = true;

                        windowSection.userData.isExteriorWindow = true;

                        windowSection.userData.windowFace = faceDirection;
                    }
                }

                // =================================================
                // FRONT WINDOW Z POSITION
                // =================================================

                const frontWindowZ = frontZ + windowDepth / 2 + facadeBandDepth + 0.04;

                // =================================================
                // LEFT FRONT WINDOW SECTION
                // =================================================

                const leftFrontWindowStart =
                    center.x - buildingWidth / 2 + buildingWidth * 0.03;

                const leftFrontWindowWidth =
                    frontWindowSideWidth - buildingWidth * 0.06;

                // =================================================
                // RIGHT FRONT WINDOW SECTION
                // =================================================

                const rightFrontWindowStart =
                    center.x + frontFeatureWidth / 2 + buildingWidth * 0.03;

                const rightFrontWindowWidth =
                    frontWindowSideWidth - buildingWidth * 0.06;

                // =================================================
                // SECOND FLOOR FRONT WINDOWS
                // LEFT SIDE
                // =================================================

                createHorizontalWindowRow(
                    leftFrontWindowStart,

                    leftFrontWindowWidth,

                    secondFloorWindowY,

                    frontWindowZ,

                    "front",
                );

                // =================================================
                // SECOND FLOOR FRONT WINDOWS
                // RIGHT SIDE
                // =================================================

                createHorizontalWindowRow(
                    rightFrontWindowStart,

                    rightFrontWindowWidth,

                    secondFloorWindowY,

                    frontWindowZ,

                    "front",
                );

                // =================================================
                // THIRD FLOOR FRONT WINDOWS
                // LEFT SIDE
                // =================================================

                createHorizontalWindowRow(
                    leftFrontWindowStart,

                    leftFrontWindowWidth,

                    thirdFloorWindowY,

                    frontWindowZ,

                    "front",
                );

                // =================================================
                // THIRD FLOOR FRONT WINDOWS
                // RIGHT SIDE
                // =================================================

                createHorizontalWindowRow(
                    rightFrontWindowStart,

                    rightFrontWindowWidth,

                    thirdFloorWindowY,

                    frontWindowZ,

                    "front",
                );

                // =================================================
                // BACK WINDOW CONFIGURATION
                //
                // Leave a center opening for the future STI entrance
                // and staircase architecture.
                // =================================================

                const backCenterClearance = Math.max(buildingWidth * 0.225, 3.75);
                const backWindowSideWidth = (buildingWidth - backCenterClearance) / 2;

                const backWindowZ = backZ - windowDepth / 2 - facadeBandDepth - 0.04;

                // =================================================
                // LEFT BACK WINDOW SECTION
                // =================================================

                const leftBackWindowStart =
                    center.x - buildingWidth / 2 + buildingWidth * 0.03;

                const leftBackWindowWidth = backWindowSideWidth - buildingWidth * 0.06;

                // =================================================
                // RIGHT BACK WINDOW SECTION
                // =================================================

                const rightBackWindowStart =
                    center.x + backCenterClearance / 2 + buildingWidth * 0.03;

                const rightBackWindowWidth = backWindowSideWidth - buildingWidth * 0.06;

                // =================================================
                // SECOND FLOOR BACK WINDOWS
                // LEFT SIDE
                // =================================================

                createHorizontalWindowRow(
                    leftBackWindowStart,

                    leftBackWindowWidth,

                    secondFloorWindowY,

                    backWindowZ,

                    "back",
                );

                // =================================================
                // SECOND FLOOR BACK WINDOWS
                // RIGHT SIDE
                // =================================================

                createHorizontalWindowRow(
                    rightBackWindowStart,

                    rightBackWindowWidth,

                    secondFloorWindowY,

                    backWindowZ,

                    "back",
                );

                // =================================================
                // THIRD FLOOR BACK WINDOWS
                // LEFT SIDE
                // =================================================

                createHorizontalWindowRow(
                    leftBackWindowStart,

                    leftBackWindowWidth,

                    thirdFloorWindowY,

                    backWindowZ,

                    "back",
                );

                // =================================================
                // THIRD FLOOR BACK WINDOWS
                // RIGHT SIDE
                // =================================================

                createHorizontalWindowRow(
                    rightBackWindowStart,

                    rightBackWindowWidth,

                    thirdFloorWindowY,

                    backWindowZ,

                    "back",
                );

                // =================================================
                // SIDE WINDOW HELPER
                //
                // Side windows run along the Z axis instead of X.
                // =================================================

                function createSideWindowRow(x, y, startZ, totalDepth, faceDirection) {
                    const approximateWindowWidth = Math.max(buildingDepth * 0.08, 0.7);

                    const sideWindowGap = Math.max(buildingDepth * 0.018, 0.18);

                    const windowCount = Math.max(
                        2,
                        Math.floor(
                            totalDepth / (approximateWindowWidth + sideWindowGap),
                        ),
                    );

                    const usableDepth = totalDepth - sideWindowGap * (windowCount - 1);

                    const actualWindowDepth = usableDepth / windowCount;

                    for (let i = 0; i < windowCount; i++) {
                        const windowZ =
                            startZ +
                            actualWindowDepth / 2 +
                            i * (actualWindowDepth + sideWindowGap);

                        const windowSection = createExteriorSection(
                            windowDepth,

                            windowHeight,

                            actualWindowDepth,

                            x,

                            y,

                            windowZ,
                        );

                        windowSection.userData.isBuildingExterior = true;

                        windowSection.userData.isExteriorWindow = true;

                        windowSection.userData.windowFace = faceDirection;
                    }
                }

                // =================================================
                // SIDE WINDOW POSITIONS
                // =================================================

                const leftWindowX =
                    center.x -
                    buildingWidth / 2 -
                    facadeBandDepth -
                    windowDepth / 2 -
                    0.04;

                const rightWindowX =
                    center.x +
                    buildingWidth / 2 +
                    facadeBandDepth +
                    windowDepth / 2 +
                    0.04;

                // =================================================
                // SIDE WINDOW DEPTH AREA
                //
                // Leave a little space near the front and back
                // corners so the windows do not collide with the
                // corner architectural sections.
                // =================================================

                const sideWindowStartZ = backZ + buildingDepth * 0.06;

                const sideWindowTotalDepth = buildingDepth * 0.88;

                // =================================================
                // LEFT SIDE
                // SECOND FLOOR WINDOWS
                // =================================================

                createSideWindowRow(
                    leftWindowX,

                    secondFloorWindowY,

                    sideWindowStartZ,

                    sideWindowTotalDepth,

                    "left",
                );

                // =================================================
                // LEFT SIDE
                // THIRD FLOOR WINDOWS
                // =================================================

                createSideWindowRow(
                    leftWindowX,

                    thirdFloorWindowY,

                    sideWindowStartZ,

                    sideWindowTotalDepth,

                    "left",
                );

                // =================================================
                // RIGHT SIDE
                // SECOND FLOOR WINDOWS
                // =================================================

                createSideWindowRow(
                    rightWindowX,

                    secondFloorWindowY,

                    sideWindowStartZ,

                    sideWindowTotalDepth,

                    "right",
                );

                // =================================================
                // RIGHT SIDE
                // THIRD FLOOR WINDOWS
                // =================================================

                createSideWindowRow(
                    rightWindowX,

                    thirdFloorWindowY,

                    sideWindowStartZ,

                    sideWindowTotalDepth,

                    "right",
                );

                // =================================================
                // 6. MAIN TOP ROOF
                //
                // Long flat roof following the overall building.
                //
                // The actual reference has rounded corners.
                // Those will be refined later.
                // =================================================

                const mainRoofThickness = 0.25;

                const exteriorRoof = createExteriorSection(
                    buildingWidth + 0.5,

                    mainRoofThickness,

                    buildingDepth + 0.5,

                    center.x,

                    center.y + buildingHeight / 2 + mainRoofThickness / 2,

                    center.z,
                );

                exteriorRoof.userData.isBuildingExterior = true;

                // =================================================
                // 7. FRONT ROOF FASCIA
                //
                // Creates the thicker upper edge visible from
                // the street-facing side of the real building.
                // =================================================

                const roofFasciaHeight = Math.max(buildingHeight * 0.1, 0.5);

                const roofFasciaDepth = Math.max(buildingDepth * 0.06, 0.5);

                const frontRoofFascia = createExteriorSection(
                    buildingWidth + 0.3,

                    roofFasciaHeight,

                    roofFasciaDepth,

                    center.x,

                    center.y + buildingHeight / 2 - roofFasciaHeight / 2,

                    frontZ + roofFasciaDepth / 2,
                );

                frontRoofFascia.userData.isBuildingExterior = true;

                // =================================================
                // 8. LEFT SIDE CORNER MASS
                //
                // The real building has large corner sections
                // instead of separate front wings.
                //
                // This replaces the incorrect left front wing
                // from the previous version.
                // =================================================

                const cornerWidth = Math.max(buildingWidth * 0.12, 1.8);

                const cornerDepth = Math.max(buildingDepth * 0.1, 0.8);

                const leftCornerSection = createExteriorSection(
                    cornerWidth,

                    buildingHeight * 0.95,

                    cornerDepth,

                    center.x - buildingWidth / 2 + cornerWidth / 2,

                    center.y,

                    frontZ + cornerDepth / 2,
                );

                leftCornerSection.userData.isBuildingExterior = true;

                // =================================================
                // 9. RIGHT SIDE CORNER MASS
                //
                // Mirrors the left side.
                // =================================================

                const rightCornerSection = createExteriorSection(
                    cornerWidth,

                    buildingHeight * 0.95,

                    cornerDepth,

                    center.x + buildingWidth / 2 - cornerWidth / 2,

                    center.y,

                    frontZ + cornerDepth / 2,
                );

                rightCornerSection.userData.isBuildingExterior = true;

                // =================================================
                // PHASE 8.3 PART 4
                // REAR STI COLLEGE ORMOC ENTRANCE
                // AND EXTERIOR STAIRCASE
                //
                // IMPORTANT BUILDING LAYOUT:
                //
                // GROUND FLOOR:
                // Robinsons / Commercial Area
                //
                // SECOND FLOOR:
                // STI College Ormoc entrance
                //
                // The staircase begins at ground level behind the
                // building and rises toward a central second-floor
                // entrance.
                //
                // +Z = FRONT
                // -Z = BACK
                // =================================================

                // =================================================
                // STI ENTRANCE DIMENSIONS
                // =================================================

                const stiEntranceWidth = Math.max(buildingWidth * 0.18, 3);

                const stiEntranceDepth = Math.max(buildingDepth * 0.1, 1);

                // =================================================
                // PHASE 8.3 PART 2.2
                // WRAPAROUND MAIN FACADE SECTIONS
                //
                // Extends the main facade sections to:
                // 1. LEFT SIDE
                // 2. RIGHT SIDE
                // 3. REAR SIDE
                //
                // IMPORTANT:
                // The rear ground floor and second floor facades
                // are split into LEFT and RIGHT sections.
                //
                // This creates a clear opening for the actual
                // STI College entrance, landing, and staircase.
                //
                // The third floor remains continuous.
                // =================================================

                // =================================================
                // REAR STI ENTRANCE FACADE OPENING
                //
                // Creates extra clearance around the STI entrance.
                // =================================================

                const rearEntranceOpeningWidth = stiEntranceWidth + 0.8;

                // =================================================
                // SHARED SIDE FACADE THICKNESS
                // =================================================

                const sideFacadeThickness = upperFacadeDepth;

                // =================================================
                // 1. REAR GROUND FLOOR COMMERCIAL BAND
                // SPLIT AROUND STI ENTRANCE
                // =================================================

                const rearCommercialTotalWidth = buildingWidth * 0.96;

                const rearCommercialSideWidth =
                    (rearCommercialTotalWidth - rearEntranceOpeningWidth) / 2;

                // =================================================
                // LEFT AND RIGHT X POSITIONS
                // =================================================

                const rearCommercialLeftX =
                    center.x -
                    rearEntranceOpeningWidth / 2 -
                    rearCommercialSideWidth / 2;

                const rearCommercialRightX =
                    center.x +
                    rearEntranceOpeningWidth / 2 +
                    rearCommercialSideWidth / 2;

                // =================================================
                // REAR COMMERCIAL BAND
                // LEFT SECTION
                // =================================================

                const rearCommercialBandLeft = createExteriorSection(
                    rearCommercialSideWidth,

                    commercialBandHeight,

                    commercialBandDepth,

                    rearCommercialLeftX,

                    center.y - buildingHeight / 2 + commercialBandHeight / 2,

                    backZ - commercialBandDepth / 2,
                );

                rearCommercialBandLeft.userData.isBuildingExterior = true;

                // =================================================
                // REAR COMMERCIAL BAND
                // RIGHT SECTION
                // =================================================

                const rearCommercialBandRight = createExteriorSection(
                    rearCommercialSideWidth,

                    commercialBandHeight,

                    commercialBandDepth,

                    rearCommercialRightX,

                    center.y - buildingHeight / 2 + commercialBandHeight / 2,

                    backZ - commercialBandDepth / 2,
                );

                rearCommercialBandRight.userData.isBuildingExterior = true;

                // =================================================
                // 2. LEFT GROUND FLOOR COMMERCIAL BAND
                // =================================================

                const leftCommercialBand = createExteriorSection(
                    commercialBandDepth,

                    commercialBandHeight,

                    buildingDepth * 0.96,

                    center.x - buildingWidth / 2 - commercialBandDepth / 2,

                    center.y - buildingHeight / 2 + commercialBandHeight / 2,

                    center.z,
                );

                leftCommercialBand.userData.isBuildingExterior = true;

                // =================================================
                // 3. RIGHT GROUND FLOOR COMMERCIAL BAND
                // =================================================

                const rightCommercialBand = createExteriorSection(
                    commercialBandDepth,

                    commercialBandHeight,

                    buildingDepth * 0.96,

                    center.x + buildingWidth / 2 + commercialBandDepth / 2,

                    center.y - buildingHeight / 2 + commercialBandHeight / 2,

                    center.z,
                );

                rightCommercialBand.userData.isBuildingExterior = true;

                // =================================================
                // 4. REAR SECOND FLOOR FACADE
                // SPLIT AROUND STI ENTRANCE
                // =================================================

                const rearSecondFacadeTotalWidth = buildingWidth * 0.94;

                const rearSecondFacadeSideWidth =
                    (rearSecondFacadeTotalWidth - rearEntranceOpeningWidth) / 2;

                // =================================================
                // LEFT AND RIGHT X POSITIONS
                // =================================================

                const rearSecondLeftX =
                    center.x -
                    rearEntranceOpeningWidth / 2 -
                    rearSecondFacadeSideWidth / 2;

                const rearSecondRightX =
                    center.x +
                    rearEntranceOpeningWidth / 2 +
                    rearSecondFacadeSideWidth / 2;

                // =================================================
                // REAR SECOND FLOOR FACADE
                // LEFT SECTION
                // =================================================

                const rearSecondFloorFacadeLeft = createExteriorSection(
                    rearSecondFacadeSideWidth,

                    upperFacadeHeight,

                    upperFacadeDepth,

                    rearSecondLeftX,

                    center.y,

                    backZ - upperFacadeDepth / 2,
                );

                rearSecondFloorFacadeLeft.userData.isBuildingExterior = true;

                // =================================================
                // REAR SECOND FLOOR FACADE
                // RIGHT SECTION
                // =================================================

                const rearSecondFloorFacadeRight = createExteriorSection(
                    rearSecondFacadeSideWidth,

                    upperFacadeHeight,

                    upperFacadeDepth,

                    rearSecondRightX,

                    center.y,

                    backZ - upperFacadeDepth / 2,
                );

                rearSecondFloorFacadeRight.userData.isBuildingExterior = true;

                // =================================================
                // 5. LEFT SECOND FLOOR FACADE
                // =================================================

                const leftSecondFloorFacade = createExteriorSection(
                    sideFacadeThickness,

                    upperFacadeHeight,

                    buildingDepth * 0.94,

                    center.x - buildingWidth / 2 - sideFacadeThickness / 2,

                    center.y,

                    center.z,
                );

                leftSecondFloorFacade.userData.isBuildingExterior = true;

                // =================================================
                // 6. RIGHT SECOND FLOOR FACADE
                // =================================================

                const rightSecondFloorFacade = createExteriorSection(
                    sideFacadeThickness,

                    upperFacadeHeight,

                    buildingDepth * 0.94,

                    center.x + buildingWidth / 2 + sideFacadeThickness / 2,

                    center.y,

                    center.z,
                );

                rightSecondFloorFacade.userData.isBuildingExterior = true;

                // =================================================
                // 7. REAR THIRD FLOOR FACADE
                //
                // This remains continuous because the STI entrance
                // opening only needs to affect the lower levels.
                // =================================================

                const rearThirdFloorFacade = createExteriorSection(
                    buildingWidth * 0.96,

                    upperFacadeHeight * 0.8,

                    upperFacadeDepth,

                    center.x,

                    center.y + buildingHeight * 0.3,

                    backZ - upperFacadeDepth / 2,
                );

                rearThirdFloorFacade.userData.isBuildingExterior = true;

                // =================================================
                // 8. LEFT THIRD FLOOR FACADE
                // =================================================

                const leftThirdFloorFacade = createExteriorSection(
                    sideFacadeThickness,

                    upperFacadeHeight * 0.8,

                    buildingDepth * 0.96,

                    center.x - buildingWidth / 2 - sideFacadeThickness / 2,

                    center.y + buildingHeight * 0.3,

                    center.z,
                );

                leftThirdFloorFacade.userData.isBuildingExterior = true;

                // =================================================
                // 9. RIGHT THIRD FLOOR FACADE
                // =================================================

                const rightThirdFloorFacade = createExteriorSection(
                    sideFacadeThickness,

                    upperFacadeHeight * 0.8,

                    buildingDepth * 0.96,

                    center.x + buildingWidth / 2 + sideFacadeThickness / 2,

                    center.y + buildingHeight * 0.3,

                    center.z,
                );

                rightThirdFloorFacade.userData.isBuildingExterior = true;

                // =================================================
                // ESTIMATED FLOOR HEIGHT
                //
                // The exterior represents approximately three
                // vertical building levels.
                //
                // We use this to calculate the second-floor height.
                // =================================================

                const estimatedFloorHeight = buildingHeight / 3;

                // =================================================
                // GROUND LEVEL
                // =================================================

                const buildingBottomY = center.y - buildingHeight / 2;

                // =================================================
                // SECOND FLOOR ENTRANCE HEIGHT
                //
                // This is one floor above ground level.
                // =================================================

                const secondFloorEntranceY = buildingBottomY + estimatedFloorHeight;

                // =================================================
                // 1. SECOND FLOOR STI ENTRANCE LANDING
                //
                // This platform sits behind the building at the
                // second-floor level.
                //
                // The staircase will connect directly to it.
                // =================================================

                const stiLandingThickness = 0.22;

                const stiLandingDepth = Math.max(buildingDepth * 0.12, 1.4);

                const stiEntranceLanding = createExteriorSection(
                    stiEntranceWidth,

                    stiLandingThickness,

                    stiLandingDepth,

                    center.x,

                    secondFloorEntranceY,

                    backZ - stiLandingDepth / 2,
                );

                stiEntranceLanding.userData.isBuildingExterior = true;

                stiEntranceLanding.userData.isStiEntrance = true;

                // =================================================
                // 2. STI SECOND FLOOR ENTRANCE FRAME
                //
                // Marks the doorway at the rear center.
                //
                // This is the actual school entrance position.
                // =================================================

                const stiDoorWidth = Math.max(stiEntranceWidth * 0.45, 1.2);

                const stiDoorHeight = Math.max(estimatedFloorHeight * 0.58, 1.5);

                const stiDoorDepth = Math.max(buildingDepth * 0.025, 0.18);

                const stiEntranceFrame = createExteriorSection(
                    stiDoorWidth,

                    stiDoorHeight,

                    stiDoorDepth,

                    center.x,

                    secondFloorEntranceY + stiDoorHeight / 2,

                    backZ - stiDoorDepth / 2 - 0.04,
                );

                stiEntranceFrame.userData.isBuildingExterior = true;

                stiEntranceFrame.userData.isStiEntrance = true;

                // =================================================
                // 3. STAIRCASE CONFIGURATION
                // SIDEWAYS REAR STAIRCASE
                //
                // The STI entrance remains centered at the BACK.
                //
                // The staircase now runs SIDEWAYS along the X axis.
                //
                // TOP:
                // Center landing at the STI entrance.
                //
                // BOTTOM:
                // Extends toward the RIGHT side of the building.
                //
                // Change stairDirection to -1 if you want the
                // staircase to descend toward the LEFT instead.
                // =================================================

                const stairDirection = -1;

                const stairWidth = Math.max(stiEntranceDepth * 0.85, 1.8);

                const stairRun = Math.max(buildingWidth * 0.32, 4);

                const stairStepCount = 12;

                // =================================================
                // CALCULATE STAIR STEP DIMENSIONS
                // =================================================

                const stairStepHeight = estimatedFloorHeight / stairStepCount;

                const stairStepRun = stairRun / stairStepCount;

                // =================================================
                // STAIRCASE Z POSITION
                //
                // Keeps the entire staircase behind the building
                // and aligned with the STI entrance landing.
                // =================================================

                const staircaseZ = backZ - stiLandingDepth - stairWidth / 2;

                // =================================================
                // STAIRCASE TOP CONNECTION POINT
                // Start from the RIGHT SIDE of the entrance landing
                // Keep the existing staircase direction unchanged
                // =================================================

                const stairTopX = center.x + stiEntranceWidth / 2;

                // =================================================
                // CREATE SIDEWAYS STAIR STEPS
                //
                // Higher steps are closer to the center landing.
                //
                // Lower steps extend sideways toward the right.
                //
                // stairDirection = 1  -> right side
                // stairDirection = -1 -> left side
                // =================================================

                for (let i = 0; i < stairStepCount; i++) {
                    const currentStepHeight = stairStepHeight * (i + 1);

                    // =================================================
                    // STEP HEIGHT
                    // =================================================

                    const stepY = buildingBottomY + currentStepHeight / 2;

                    // =================================================
                    // STEP X POSITION
                    //
                    // Step 0 is the lowest and farthest from entrance.
                    //
                    // Final step ends near the center landing.
                    // =================================================

                    const distanceFromLanding = stairRun - stairStepRun * (i + 0.5);

                    const stepX = stairTopX + distanceFromLanding * stairDirection;

                    // =================================================
                    // CREATE STEP
                    //
                    // Width is now along X.
                    // Stair width is now along Z.
                    // =================================================

                    const stairStep = createExteriorSection(
                        stairStepRun,

                        currentStepHeight,

                        stairWidth,

                        stepX,

                        stepY,

                        staircaseZ,
                    );

                    stairStep.userData.isBuildingExterior = true;

                    stairStep.userData.isStiStaircase = true;
                }

                // =================================================
                // 4. BACK STAIR RAILING
                // FIXED FOR SIDEWAYS STAIRCASE
                // =================================================

                const railingThickness = 0.08;

                const railingHeight = Math.max(estimatedFloorHeight * 0.3, 0.8);

                const stairSlopeAngle = Math.atan2(estimatedFloorHeight, stairRun);

                const staircaseCenterX = stairTopX + (stairRun / 2) * stairDirection;

                // =================================================
                // FIX:
                // Since the staircase runs along the X axis,
                // the railing geometry must follow X.
                //
                // Rotation direction must be OPPOSITE when
                // stairDirection is positive.
                // =================================================

                const railingRotationZ = -stairDirection * stairSlopeAngle;

                // =================================================
                // BACK STAIR RAILING
                // =================================================

                const backStairRailing = createExteriorSection(
                    stairRun,

                    railingThickness,

                    railingHeight,

                    staircaseCenterX,

                    buildingBottomY + estimatedFloorHeight / 2 + railingHeight / 2,

                    staircaseZ - stairWidth / 2,
                );

                backStairRailing.rotation.z = railingRotationZ;

                backStairRailing.userData.isBuildingExterior = true;

                backStairRailing.userData.isStiStaircase = true;

                // =================================================
                // 5. FRONT STAIR RAILING
                // =================================================

                const frontStairRailing = createExteriorSection(
                    stairRun,

                    railingThickness,

                    railingHeight,

                    staircaseCenterX,

                    buildingBottomY + estimatedFloorHeight / 2 + railingHeight / 2,

                    staircaseZ + stairWidth / 2,
                );

                frontStairRailing.rotation.z = railingRotationZ;

                frontStairRailing.userData.isBuildingExterior = true;

                frontStairRailing.userData.isStiStaircase = true;

                // =================================================
                // 6. LEFT LANDING RAILING
                // =================================================

                const landingRailingHeight = Math.max(estimatedFloorHeight * 0.25, 0.7);

                const leftLandingRailing = createExteriorSection(
                    railingThickness,

                    landingRailingHeight,

                    stiLandingDepth,

                    center.x - stiEntranceWidth / 2,

                    secondFloorEntranceY + landingRailingHeight / 2,

                    backZ - stiLandingDepth / 2,
                );

                leftLandingRailing.userData.isBuildingExterior = true;

                leftLandingRailing.userData.isStiEntrance = true;

                // =================================================
                // 7. RIGHT LANDING RAILING
                // =================================================

                const rightLandingRailing = createExteriorSection(
                    railingThickness,

                    landingRailingHeight,

                    stiLandingDepth,

                    center.x + stiEntranceWidth / 2,

                    secondFloorEntranceY + landingRailingHeight / 2,

                    backZ - stiLandingDepth / 2,
                );

                rightLandingRailing.userData.isBuildingExterior = true;

                rightLandingRailing.userData.isStiEntrance = true;

                // =================================================
                // 8. STI ENTRANCE CANOPY
                //
                // Small protective roof above the second-floor
                // entrance.
                //
                // This is intentionally simple for the blueprint
                // exterior.
                // =================================================

                const stiCanopyWidth = stiEntranceWidth * 0.75;

                const stiCanopyDepth = Math.max(stiLandingDepth * 0.65, 0.8);

                const stiCanopyThickness = 0.12;

                const stiEntranceCanopy = createExteriorSection(
                    stiCanopyWidth,

                    stiCanopyThickness,

                    stiCanopyDepth,

                    center.x,

                    secondFloorEntranceY + stiDoorHeight + 0.25,

                    backZ - stiCanopyDepth / 2,
                );

                stiEntranceCanopy.userData.isBuildingExterior = true;

                stiEntranceCanopy.userData.isStiEntrance = true;

                // =================================================
                // STI COLLEGE ORMOC BANNER (FRONTMOST LAYER)
                // Uses public/image/STI_College_Ormoc_Logo.png
                // Left facade only (no arch overlap)
                // =================================================

                // Keep banner on the LEFT street facade only —
                // stop before the center Robinsons arch.
                const archHalfWidth = frontFeatureWidth / 2;
                const gapFromArch = Math.max(buildingWidth * 0.035, 0.55);
                const leftFacadeInner =
                    center.x - archHalfWidth - gapFromArch;
                const leftFacadeOuter =
                    center.x - buildingWidth * 0.47;

                const stiBannerWidth = Math.max(
                    Math.min(
                        leftFacadeInner - leftFacadeOuter,
                        buildingWidth * 0.13,
                    ),
                    2.2,
                );

                const stiBannerHeight = Math.max(
                    upperFacadeHeight * 0.62,
                    0.75,
                );
                const stiBannerDepth = 0.14;

                const stiBannerCenterX =
                    leftFacadeInner - stiBannerWidth / 2;

                // Placeholder yellow until banner image loads
                const stiBannerMaterial = new THREE.MeshBasicMaterial({
                    color: 0xffd200,
                    toneMapped: false,
                    depthTest: true,
                    depthWrite: true,
                    polygonOffset: true,
                    polygonOffsetFactor: -4,
                    polygonOffsetUnits: -4,
                });

                const stiBanner = new THREE.Mesh(
                    new THREE.BoxGeometry(
                        stiBannerWidth,
                        stiBannerHeight,
                        stiBannerDepth,
                    ),
                    stiBannerMaterial,
                );

                const stiBannerZ =
                    (typeof frontWindowZ !== "undefined"
                        ? frontWindowZ
                        : frontZ + facadeBandDepth + 0.2) +
                    stiBannerDepth / 2 +
                    1.25;

                stiBanner.position.set(
                    stiBannerCenterX,
                    center.y + buildingHeight * 0.30,
                    stiBannerZ,
                );

                stiBanner.renderOrder = 999;
                stiBanner.castShadow = true;
                stiBanner.receiveShadow = true;
                stiBanner.userData.isBuildingExterior = true;
                stiBanner.userData.isStiBanner = true;

                exteriorBuilding.add(stiBanner);

                const stiBannerMount = new THREE.Mesh(
                    new THREE.BoxGeometry(
                        stiBannerWidth + 0.1,
                        stiBannerHeight + 0.08,
                        0.05,
                    ),
                    new THREE.MeshBasicMaterial({
                        color: 0x334155,
                        depthTest: true,
                        depthWrite: true,
                        polygonOffset: true,
                        polygonOffsetFactor: -2,
                        polygonOffsetUnits: -2,
                    }),
                );

                stiBannerMount.position.set(
                    stiBanner.position.x,
                    stiBanner.position.y,
                    stiBannerZ - stiBannerDepth / 2 - 0.03,
                );

                stiBannerMount.renderOrder = 998;
                stiBannerMount.userData.isBuildingExterior = true;
                exteriorBuilding.add(stiBannerMount);

                // Full campus banner image
                const stiBannerUrl = @json(asset('image/STI_College_Ormoc_Logo.png'));
                const stiBannerLoader = new THREE.TextureLoader();

                stiBannerLoader.load(
                    stiBannerUrl,
                    (bannerTexture) => {
                        bannerTexture.colorSpace = THREE.SRGBColorSpace;
                        bannerTexture.anisotropy = 8;
                        bannerTexture.needsUpdate = true;

                        stiBannerMaterial.map = bannerTexture;
                        stiBannerMaterial.color.set(0xffffff);
                        stiBannerMaterial.needsUpdate = true;
                    },
                    undefined,
                    () => {
                        console.warn(
                            "STI College Ormoc banner failed to load:",
                            stiBannerUrl,
                        );
                    },
                );

            }

            // =====================================================
            // PHASE 8.1 PART 2
            // BUILD EXTERIOR AFTER ALL FLOORS AND ROOMS EXIST
            // =====================================================

            createDynamicBuildingExterior();

            // =====================================================
            // CENTER EXTERIOR BUILDING ON THE WORLD GRID
            // MAKES THE BUILDING CENTER MATCH X = 0 AND Z = 0
            // =====================================================

            function centerExteriorBuildingOnGrid() {

                // Make sure all transforms are calculated
                exteriorBuilding.updateMatrixWorld(true);

                // Get complete exterior building bounds
                const bounds = new THREE.Box3().setFromObject(exteriorBuilding);

                if (bounds.isEmpty()) {
                    return;
                }

                // Get current center of the building
                const center = bounds.getCenter(new THREE.Vector3());

                // =================================================
                // MOVE BUILDING CENTER TO WORLD CENTER
                //
                // X = 0 means center horizontally
                // Z = 0 means center along depth
                //
                // We DO NOT change Y because Y controls height
                // =================================================

                exteriorBuilding.position.x -= center.x;
                exteriorBuilding.position.z -= center.z;

                // Update position immediately
                exteriorBuilding.updateMatrixWorld(true);
            }


            // =====================================================
            // APPLY CENTERING
            // =====================================================

            centerExteriorBuildingOnGrid();

            // =====================================================
            // SET DEFAULT CAMERA TO EXTERIOR SHELL OVERVIEW
            // Matches the first-load / refresh angle (screenshot)
            // =====================================================

            const defaultExteriorView = getExteriorFocusView();

            camera.position.copy(defaultExteriorView.position);

            controls.target.copy(defaultExteriorView.target);

            controls.update();

            // =====================================================
            // ENTER BUILDING BUTTON
            // =====================================================

            const enterBuildingButton = document.getElementById(
                "enterBuildingBtn",
            );

            // =====================================================
            // ENTER BUILDING — ANCHOR TO ROOF CENTER
            // Projects the shell roof top into screen space
            // =====================================================

            const enterButtonProjected = new THREE.Vector3();

            function getExteriorRoofAnchor() {
                exteriorBuilding.updateMatrixWorld(true);

                const box = new THREE.Box3().setFromObject(exteriorBuilding);

                if (box.isEmpty()) {
                    return new THREE.Vector3(0, 8, 0);
                }

                const center = box.getCenter(new THREE.Vector3());

                // Center of the roof plane, slightly above so the
                // button sits on top of the shell like a roof label.
                return new THREE.Vector3(
                    center.x,
                    box.max.y + 0.55,
                    center.z,
                );
            }

            function updateEnterBuildingButtonPosition() {
                if (!enterBuildingButton) {
                    return;
                }

                const isHidden =
                    enterBuildingButton.style.display === "none" ||
                    currentBuildingView !== "exterior";

                if (isHidden) {
                    return;
                }

                const anchor = getExteriorRoofAnchor();

                enterButtonProjected.copy(anchor).project(camera);

                // Behind the camera / clipped
                if (enterButtonProjected.z > 1) {
                    enterBuildingButton.style.visibility = "hidden";
                    return;
                }

                const x =
                    (enterButtonProjected.x * 0.5 + 0.5) *
                    container.clientWidth;

                const y =
                    (-enterButtonProjected.y * 0.5 + 0.5) *
                    container.clientHeight;

                // Keep on-screen with a small margin
                const margin = 28;
                const clampedX = Math.min(
                    Math.max(x, margin),
                    container.clientWidth - margin,
                );
                const clampedY = Math.min(
                    Math.max(y, margin + 18),
                    container.clientHeight - margin,
                );

                enterBuildingButton.style.visibility = "visible";
                enterBuildingButton.style.left = `${clampedX}px`;
                enterBuildingButton.style.top = `${clampedY}px`;
                enterBuildingButton.style.right = "auto";
            }

            // =====================================================
            // PHASE 7.4
            // GENERATE FLOOR FILTER BUTTONS
            // =====================================================

            const floorFilterButtonContainer = document.getElementById(
                "buildingFloorFilterButtons",
            );

            const floorFiltersBar = document.getElementById(
                "buildingFloorFilters",
            );

            // =====================================================
            // PHASE 8.2 PART 4
            // BACK TO BUILDING OVERVIEW BUTTON
            // =====================================================

            const backToBuildingOverviewButton = document.getElementById(
                "backToBuildingOverview",
            );

            // =====================================================
            // PHASE 8.2
            // HIDE FLOOR FILTERS ON EXTERIOR SHELL
            // (All Floors + floor buttons only inside)
            // =====================================================

            if (floorFiltersBar) {
                floorFiltersBar.style.display =
                    currentBuildingView === "exterior" ? "none" : "";
            }

            if (floorFilterButtonContainer) {
                building3DData.forEach((floorData) => {
                    const button = document.createElement("button");

                    button.type = "button";

                    button.className = "building-floor-filter";

                    button.dataset.floorFilter = String(floorData.id);

                    button.textContent = floorData.name;

                    floorFilterButtonContainer.appendChild(button);
                });
            }

            // =====================================================
            // PHASE 7.5
            // AUTO FOCUS CAMERA ON SELECTED FLOOR
            // =====================================================

            // =====================================================
            // PHASE 7.5 + 7.6
            // AUTO FOCUS WITH SMOOTH CAMERA TRANSITION
            // =====================================================

            function focusCameraOnObject(object) {
                if (!object) {
                    return;
                }

                object.updateMatrixWorld(true);

                const isRoom = object.userData?.type === "room";
                let size;
                let center;

                if (isRoom && object.geometry) {
                    object.geometry.computeBoundingBox();
                    size = object.geometry.boundingBox
                        .getSize(roomFocusSize)
                        .multiply(object.scale);
                    center = object.getWorldPosition(roomFocusCenter);
                } else {
                    const bounds = new THREE.Box3().setFromObject(object);

                    if (bounds.isEmpty()) {
                        return;
                    }

                    size = bounds.getSize(roomFocusSize);
                    center = bounds.getCenter(roomFocusCenter);
                }

                const distance = isRoom
                    ? Math.max(Math.max(size.x, size.y, size.z, 1.4) * 3.5, 7.4)
                    : Math.max(size.x, size.y, size.z, 8) * 1.75;

                // Same default interior side cam as getInteriorFocusView
                // (front-right / Building Layout angle)
                const targetPosition = new THREE.Vector3(
                    center.x + distance * 0.55,
                    center.y + distance * 0.42,
                    center.z + distance * 1.35,
                );

                cameraTransition = {
                    startPosition: camera.position.clone(),

                    endPosition: targetPosition.clone(),

                    startTarget: controls.target.clone(),

                    endTarget: center.clone(),

                    startTime: performance.now(),

                    duration: 800,
                };
            }

            // =====================================================
            // PHASE 7.4
            // FLOOR FILTERING
            // =====================================================

            const floorFilterButtons = document.querySelectorAll(
                ".building-floor-filter",
            );

            floorFilterButtons.forEach((button) => {
                button.addEventListener("click", function () {
                    const selectedFloor = this.dataset.floorFilter;

                    // =========================================
                    // UPDATE ACTIVE BUTTON
                    // =========================================

                    floorFilterButtons.forEach((filterButton) => {
                        filterButton.classList.remove("active");
                    });

                    this.classList.add("active");

                    // =========================================
                    // PHASE 7.10
                    // SHOW / HIDE FLOORS AND ROOM LABELS
                    // =========================================

                    buildingFloorGroups.forEach((floorGroup, floorId) => {
                        const isAllFloors = selectedFloor === "all";

                        const isSelectedFloor = floorId === selectedFloor;

                        // =====================================
                        // FLOOR VISIBILITY
                        // =====================================

                        floorGroup.visible = isAllFloors || isSelectedFloor;

                        // =====================================
                        // ROOM LABEL VISIBILITY
                        // =====================================

                        floorGroup.traverse((child) => {
                            if (child.userData.type === "room-label") {
                                // Hide labels on All Floors
                                // Show labels only on selected floor

                                child.visible = !isAllFloors && isSelectedFloor;
                            }
                        });
                    });

                    // =========================================
                    // PHASE 7.5
                    // AUTO FOCUS CAMERA
                    // =========================================

                    if (selectedFloor === "all") {
                        // Focus on the complete building
                        focusCameraOnObject(building);
                    } else {
                        // Get the selected floor group
                        const selectedFloorGroup =
                            buildingFloorGroups.get(selectedFloor);

                        // Focus only on the selected floor
                        if (selectedFloorGroup) {
                            focusCameraOnObject(selectedFloorGroup);
                        }
                    }

                    // =========================================
                    // CLEAR HOVERED ROOM
                    // =========================================

                    if (hoveredRoom) {
                        hoveredRoom = null;
                    }

                    renderer.domElement.style.cursor = "grab";
                });
            });

            // =====================================================
            // PHASE 2.3
            // CALCULATE FINAL 3D BUILDING BOUNDS
            // =====================================================

            const buildingBounds = new THREE.Box3().setFromObject(building);

            // =====================================================
            // PHASE 2.3
            // AUTOMATIC CAMERA FIT
            // =====================================================

            

            // =====================================================
            // PHASE 2.3 COMPLETE
            // EACH FLOOR IS NOW CENTERED AND STACKED
            // =====================================================

            // =====================================================

            // =====================================================
            // PHASE 2.1 COMPLETE
            // ROOMS ARE NOW GENERATED FROM DATABASE
            // =====================================================

            // =====================================================
            // PHASE 3
            // GET MOUSE POSITION INSIDE THE 3D VIEWER
            // =====================================================

            function getViewerMousePosition(event) {
                const rect = renderer.domElement.getBoundingClientRect();

                mouse.x = ((event.clientX - rect.left) / rect.width) * 2 - 1;

                mouse.y = -((event.clientY - rect.top) / rect.height) * 2 + 1;
            }

            // =====================================================
            // PHASE 7.4
            // GET ONLY ROOMS FROM VISIBLE FLOORS
            // =====================================================

            function getVisibleClickableRooms() {
                return clickableRooms.filter((room) => {
                    // Room must be visible
                    if (!room.visible) {
                        return false;
                    }

                    // Floor group must also be visible
                    if (room.parent && room.parent.userData.type === "floor") {
                        return room.parent.visible;
                    }

                    return true;
                });
            }

            // =====================================================
            // PHASE 8.2 PART 2
            // CHECK IF POINTER IS OVER EXTERIOR BUILDING
            // =====================================================

            function getExteriorIntersection(event) {
                if (currentBuildingView !== "exterior") {
                    return null;
                }

                const rect = renderer.domElement.getBoundingClientRect();

                mouse.x = ((event.clientX - rect.left) / rect.width) * 2 - 1;

                mouse.y = -((event.clientY - rect.top) / rect.height) * 2 + 1;

                raycaster.setFromCamera(mouse, camera);

                const intersections = raycaster.intersectObject(exteriorBuilding, true);

                if (intersections.length === 0) {
                    return null;
                }

                return intersections[0];
            }

            // =====================================================
            // PHASE 8.2 PART 3
            // FADE EXTERIOR BUILDING
            // =====================================================

            function fadeExteriorBuilding(
                targetOpacity,
                duration = 500,
                onComplete = null,
            ) {
                const materials = [];

                exteriorBuilding.traverse((object) => {
                    if (object.material) {
                        const objectMaterials = Array.isArray(object.material)
                            ? object.material
                            : [object.material];

                        objectMaterials.forEach((material) => {
                            materials.push({
                                material: material,

                                startOpacity: material.opacity,
                            });
                        });
                    }
                });

                const startTime = performance.now();

                function animateFade(currentTime) {
                    const elapsed = currentTime - startTime;

                    const progress = Math.min(elapsed / duration, 1);

                    // Smooth easing
                    const eased = progress * progress * (3 - 2 * progress);

                    materials.forEach((item) => {
                        item.material.opacity = THREE.MathUtils.lerp(
                            item.startOpacity,
                            targetOpacity,
                            eased,
                        );
                    });

                    if (progress < 1) {
                        requestAnimationFrame(animateFade);
                    } else {
                        if (onComplete) {
                            onComplete();
                        }
                    }
                }

                requestAnimationFrame(animateFade);
            }

            // =====================================================
            // PHASE 8.2 PART 3
            // GET CINEMATIC CAMERA POSITION NEAR EXTERIOR
            // =====================================================

            // =====================================================
            // DEFAULT EXTERIOR CAMERA VIEW
            // REAR SIDE OF BUILDING
            // =====================================================

            // =====================================================
            // DEFAULT EXTERIOR CAMERA VIEW
            // REAR SIDE OF BUILDING
            // =====================================================

            // =====================================================
            // DEFAULT EXTERIOR CAMERA VIEW
            // REAR EXTERIOR ANGLE
            // MATCHES THE DESIRED IMAGE 2 VIEW
            // =====================================================

            function getExteriorFocusView() {

                // Make sure exterior transforms are updated
                exteriorBuilding.updateMatrixWorld(true);

                // Get complete exterior building bounds
                const box = new THREE.Box3().setFromObject(exteriorBuilding);

                if (box.isEmpty()) {
                    return {
                        position: new THREE.Vector3(22, 7, 28),
                        target: new THREE.Vector3(0, 2.8, 0),
                    };
                }

                // Get building center and size
                const center = box.getCenter(new THREE.Vector3());
                const size = box.getSize(new THREE.Vector3());

                // Slightly more zoomed out than the ultra-close framing
                const distance = Math.max(size.x, size.y, size.z, 8) * 0.98;

                // =================================================
                // DEFAULT / RESET EXTERIOR ANGLE
                // Camera pushed far right so the front/right
                // side edge is the visual reference
                // =================================================

                const position = new THREE.Vector3(
                    center.x + distance * 0.92,
                    center.y + distance * 0.18,
                    center.z + distance * 0.88
                );

                // Aim near the right front corner edge
                const target = new THREE.Vector3(
                    center.x + size.x * 0.12,
                    center.y - size.y * 0.04,
                    center.z + size.z * 0.12
                );

                return {
                    position: position,
                    target: target,
                };
            }


            // =====================================================
            // INTERIOR DEFAULT CAMERA VIEW
            // After Enter Building / interior Reset
            // Matches Building Layout orientation:
            // layout bottom (ComLabs / front rooms) = +Z FRONT
            // layout top (Room 301) = -Z REAR
            // =====================================================

            function getInteriorFocusView() {

                building.updateMatrixWorld(true);

                const box = new THREE.Box3().setFromObject(building);

                if (box.isEmpty()) {
                    return {
                        position: new THREE.Vector3(22, 12, 28),
                        target: new THREE.Vector3(0, 3, 0),
                    };
                }

                const center = box.getCenter(new THREE.Vector3());
                const size = box.getSize(new THREE.Vector3());

                // Wide enough to see all floors + room blocks
                const distance = Math.max(size.x, size.y, size.z, 8) * 1.75;

                // Front-right elevated side cam (Building Layout angle)
                // +X = right, +Y = elevated, +Z = FRONT
                // Opposite lateral side from the previous front-left angle.
                const position = new THREE.Vector3(
                    center.x + distance * 0.55,
                    center.y + distance * 0.42,
                    center.z + distance * 1.35
                );

                // Geometric center = vertically centered in viewport
                const target = new THREE.Vector3(
                    center.x,
                    center.y,
                    center.z
                );

                return {
                    position,
                    target,
                };
            }

            function shortestCameraAngleDelta(from, to) {
                let delta = to - from;

                while (delta > Math.PI) {
                    delta -= Math.PI * 2;
                }

                while (delta < -Math.PI) {
                    delta += Math.PI * 2;
                }

                return delta;
            }

            function startOrbitCameraTransition(endPosition, endTarget, duration) {
                const startPosition = camera.position.clone();
                const startTarget = controls.target.clone();
                const pivot = startTarget.clone().lerp(endTarget, 0.5);

                const startSpherical = new THREE.Spherical().setFromVector3(
                    startPosition.clone().sub(pivot),
                );
                const endSpherical = new THREE.Spherical().setFromVector3(
                    endPosition.clone().sub(pivot),
                );
                const deltaTheta = shortestCameraAngleDelta(
                    startSpherical.theta,
                    endSpherical.theta,
                );

                controls.enableDamping = false;

                cameraTransition = {
                    mode: "orbit",
                    startPosition,
                    endPosition: endPosition.clone(),
                    startTarget,
                    endTarget: endTarget.clone(),
                    pivot,
                    startRadius: Math.max(startSpherical.radius, 0.01),
                    startPhi: startSpherical.phi,
                    startTheta: startSpherical.theta,
                    endRadius: Math.max(endSpherical.radius, 0.01),
                    endPhi: endSpherical.phi,
                    endTheta: startSpherical.theta + deltaTheta,
                    lift: Math.abs(deltaTheta) > Math.PI * 0.4,
                    offset: new THREE.Vector3(),
                    startTime: performance.now(),
                    duration,
                };
            }

            function enterInteriorMode() {

                if (currentBuildingView === "interior") {
                    return;
                }

                if (isBuildingViewTransitioning) {
                    return;
                }

                // =================================================
                // LOCK INTERACTION
                // =================================================

                isBuildingViewTransitioning = true;

                renderer.domElement.style.cursor = "default";

                // Stop any previous camera animation
                cameraTransition = null;


                // =================================================
                // GET THE CORRECT INTERIOR CAMERA VIEW
                // =================================================

                const interiorView = getInteriorFocusView();


                // =================================================
                // SHOW INTERIOR BEFORE MOVING CAMERA
                // =================================================

                building.visible = true;

                applyBuildingSceneTheme("interior");


                // =================================================
                // HIDE ENTER BUILDING BUTTON
                // =================================================

                if (enterBuildingButton) {
                    enterBuildingButton.style.display = "none";
                }


                // =================================================
                // START EXTERIOR -> INTERIOR CAMERA TRANSITION
                // Orbit over the building instead of lerping
                // through it (that looked like a camera flip).
                // =================================================

                startOrbitCameraTransition(
                    interiorView.position,
                    interiorView.target,
                    1100,
                );


                // =================================================
                // FADE EXTERIOR WHILE ENTERING
                // =================================================

                const fadeStart = performance.now();

                const fadeDuration = 650;


                function fadeExterior() {

                    const elapsed = performance.now() - fadeStart;

                    const progress = Math.min(
                        elapsed / fadeDuration,
                        1
                    );


                    exteriorBuilding.traverse((object) => {

                        if (!object.material) {
                            return;
                        }

                        const materials = Array.isArray(object.material)
                            ? object.material
                            : [object.material];


                        materials.forEach((material) => {

                            // Exterior blueprint surfaces
                            if (material.isMeshPhysicalMaterial || material.isMeshBasicMaterial) {

                                material.transparent = true;

                                material.opacity =
                                    THREE.MathUtils.lerp(
                                        material.userData.shellOpacity ?? 1,
                                        0,
                                        progress
                                    );
                            }


                            // Blueprint edge and grid lines
                            if (material.isLineBasicMaterial) {

                                material.transparent = true;

                                material.opacity =
                                    THREE.MathUtils.lerp(
                                        material.userData.shellOpacity ?? EXTERIOR_EDGE_OPACITY,
                                        0,
                                        progress
                                    );
                            }

                        });

                    });


                    if (progress < 1) {

                        requestAnimationFrame(
                            fadeExterior
                        );

                    }

                }


                fadeExterior();


                // =================================================
                // FINISH INTERIOR MODE
                // =================================================

                setTimeout(() => {

                    // Completely hide exterior
                    exteriorBuilding.visible = false;

                    // Keep interior visible
                    building.visible = true;

                    // Change mode
                    currentBuildingView = "interior";

                    applyBuildingSceneTheme("interior");


                    // =================================================
                    // SHOW FLOOR FILTERS (All Floors + floors)
                    // =================================================

                    if (floorFiltersBar) {
                        floorFiltersBar.style.display = "";
                    }


                    // =================================================
                    // SHOW RETURN BUTTON
                    // =================================================

                    if (backToBuildingOverviewButton) {
                        backToBuildingOverviewButton.style.display = "";
                    }


                    // =================================================
                    // FORCE EXACT INTERIOR VIEW
                    //
                    // This prevents small transition differences
                    // from leaving the camera at the wrong angle.
                    // =================================================

                    camera.position.copy(
                        interiorView.position
                    );

                    controls.target.copy(
                        interiorView.target
                    );

                    controls.enableDamping = true;

                    controls.update();


                    // =================================================
                    // UNLOCK INTERACTION
                    // =================================================

                    cameraTransition = null;

                    isBuildingViewTransitioning = false;

                    renderer.domElement.style.cursor = "grab";

                }, 1120);
            }

            // =====================================================
            // ENTER BUILDING BUTTON
            // Uses the existing cinematic interior transition
            // =====================================================

            enterBuildingButton?.addEventListener("click", () => {

                if (currentBuildingView !== "exterior") {
                    return;
                }

                if (isBuildingViewTransitioning) {
                    return;
                }

                enterInteriorMode();
            });

            // =====================================================
            // RETURN TO EXTERIOR MODE
            // Interior -> Exterior
            // =====================================================

            function returnToExteriorMode() {

                // =================================================
                // PREVENT RUNNING IF ALREADY EXTERIOR
                // =================================================

                if (currentBuildingView === "exterior") {
                    return;
                }


                // =================================================
                // PREVENT DOUBLE CLICK DURING TRANSITION
                // =================================================

                if (isBuildingViewTransitioning) {
                    return;
                }


                // =================================================
                // LOCK VIEWER DURING TRANSITION
                // =================================================

                isBuildingViewTransitioning = true;

                renderer.domElement.style.cursor = "default";


                // =================================================
                // STOP ANY EXISTING CAMERA TRANSITION
                // =================================================

                cameraTransition = null;


                // =================================================
                // CLEAR HOVERED ROOM
                // =================================================

                if (hoveredRoom) {

                    if (hoveredRoom !== selectedRoom) {

                        restoreRoomVisual(
                            hoveredRoom
                        );

                    }

                    hoveredRoom = null;
                }


                // =================================================
                // CLEAR SELECTED ROOM
                // =================================================

                if (selectedRoom) {

                    restoreRoomVisual(
                        selectedRoom
                    );

                    selectedRoom = null;
                }


                // =================================================
                // HIDE ROOM UI
                // =================================================

                hideRoomTooltip();

                roomDetailsPanel?.classList.remove(
                    "visible"
                );


                // =================================================
                // HIDE FLOOR FILTERS ON SHELL
                // =================================================

                if (floorFiltersBar) {
                    floorFiltersBar.style.display = "none";
                }


                // =================================================
                // HIDE RETURN BUTTON
                // =================================================

                if (backToBuildingOverviewButton) {

                    backToBuildingOverviewButton.style.display =
                        "none";

                }


                // =================================================
                // GET EXTERIOR CAMERA VIEW
                //
                // IMPORTANT:
                // This returns to the SAME exterior camera
                // used by your default exterior view.
                // =================================================

                const exteriorView =
                    getExteriorFocusView();


                // =================================================
                // MAKE EXTERIOR VISIBLE BEFORE TRANSITION
                // =================================================

                exteriorBuilding.visible = true;

                applyBuildingSceneTheme("exterior");


                // =================================================
                // START EXTERIOR AT ZERO OPACITY
                // =================================================

                exteriorBuilding.traverse((object) => {

                    if (!object.material) {
                        return;
                    }


                    const materials =
                        Array.isArray(object.material)
                            ? object.material
                            : [object.material];


                    materials.forEach((material) => {

                        material.transparent = true;


                        // =========================================
                        // EXTERIOR SURFACE
                        // =========================================

                        if (material.isMeshPhysicalMaterial || material.isMeshBasicMaterial) {

                            material.opacity = 0;

                        }


                        // =========================================
                        // EXTERIOR WIREFRAME
                        // =========================================

                        if (material.isLineBasicMaterial) {

                            material.opacity = 0;

                        }

                    });

                });


                // =================================================
                // CAMERA TRANSITION
                //
                // Orbit over the building so the camera does not
                // pass through the model and flip.
                // =================================================

                startOrbitCameraTransition(
                    exteriorView.position,
                    exteriorView.target,
                    1100,
                );


                // =================================================
                // FADE EXTERIOR BACK IN
                // =================================================

                const fadeStart =
                    performance.now();

                const fadeDuration =
                    750;


                function fadeExteriorIn() {

                    const elapsed =
                        performance.now() -
                        fadeStart;


                    const progress =
                        Math.min(
                            elapsed / fadeDuration,
                            1
                        );


                    exteriorBuilding.traverse((object) => {

                        if (!object.material) {
                            return;
                        }


                        const materials =
                            Array.isArray(object.material)
                                ? object.material
                                : [object.material];


                        materials.forEach((material) => {


                            // =====================================
                            // EXTERIOR TRANSPARENT SURFACES
                            // =====================================

                            if (material.isMeshPhysicalMaterial || material.isMeshBasicMaterial) {

                                material.opacity =
                                    THREE.MathUtils.lerp(
                                        0,
                                        material.userData.shellOpacity ?? 1,
                                        progress
                                    );

                            }


                            // =====================================
                            // BLUEPRINT EDGE LINES
                            // =====================================

                            if (material.isLineBasicMaterial) {

                                material.opacity =
                                    THREE.MathUtils.lerp(
                                        0,
                                        material.userData.shellOpacity ?? EXTERIOR_EDGE_OPACITY,
                                        progress
                                    );

                            }

                        });

                    });


                    if (progress < 1) {

                        requestAnimationFrame(
                            fadeExteriorIn
                        );

                    }

                }


                // =================================================
                // START FADE
                // =================================================

                fadeExteriorIn();


                // =================================================
                // FINISH RETURN TRANSITION
                // =================================================

                setTimeout(() => {


                    // =============================================
                    // HIDE INTERIOR
                    // =============================================

                    building.visible = false;


                    // =============================================
                    // KEEP EXTERIOR VISIBLE
                    // =============================================

                    exteriorBuilding.visible = true;


                    // =============================================
                    // UPDATE CURRENT MODE
                    // =============================================

                    currentBuildingView =
                        "exterior";

                    applyBuildingSceneTheme("exterior");


                    // =============================================
                    // SHOW ENTER BUILDING BUTTON AGAIN
                    // =============================================

                    if (enterBuildingButton) {

                        enterBuildingButton.style.display =
                            "";

                    }


                    // =============================================
                    // FORCE EXACT EXTERIOR POSITION
                    //
                    // Prevents transition rounding from leaving
                    // the camera slightly off position.
                    // =============================================

                    camera.position.copy(
                        exteriorView.position
                    );


                    controls.target.copy(
                        exteriorView.target
                    );


                    controls.enableDamping = true;


                    controls.update();


                    // =============================================
                    // CLEAR TRANSITION
                    // =============================================

                    cameraTransition = null;


                    // =============================================
                    // UNLOCK VIEWER
                    // =============================================

                    isBuildingViewTransitioning =
                        false;


                    renderer.domElement.style.cursor =
                        "grab";


                }, 1120);
            }

            

            // =====================================================
            // PHASE 8.2 PART 4
            // BACK TO BUILDING OVERVIEW BUTTON CLICK
            // =====================================================

            backToBuildingOverviewButton?.addEventListener("click", () => {
                returnToExteriorMode();
            });

            // =====================================================
            // PHASE 3
            // ROOM HOVER
            // =====================================================

            renderer.domElement.addEventListener("pointermove", function (event) {
                // =====================================================
                // PHASE 8.2 PART 3
                // IGNORE HOVER DURING CINEMATIC TRANSITION
                // =====================================================

                if (isBuildingViewTransitioning) {
                    renderer.domElement.style.cursor = "default";

                    return;
                }

                // =====================================================
                // PHASE 8.2 PART 2
                // EXTERIOR BUILDING HOVER
                // =====================================================

                if (currentBuildingView === "exterior") {
                    const exteriorHit = getExteriorIntersection(event);

                    renderer.domElement.style.cursor = exteriorHit ? "pointer" : "grab";

                    // Stop here while viewing the exterior.
                    // The room hover code below should only run
                    // when we are inside the building.
                    return;
                }

                // =====================================================
                // EXISTING ROOM HOVER CODE
                // =====================================================

                getViewerMousePosition(event);

                raycaster.setFromCamera(mouse, camera);

                // =================================================
                // PHASE 7.4
                // ONLY RAYCAST VISIBLE FLOOR ROOMS
                // =================================================

                const intersections = raycaster.intersectObjects(
                    getVisibleClickableRooms(),
                    false,
                );

                // ==============================================
                // RESET PREVIOUS HOVER
                // ==============================================

                if (hoveredRoom && hoveredRoom !== selectedRoom) {
                    restoreRoomVisual(hoveredRoom);
                }

                // ==============================================
                // ROOM IS BEING HOVERED
                // ==============================================

                if (intersections.length > 0) {
                    hoveredRoom = intersections[0].object;

                    // =================================================
                    // PHASE 7.7
                    // SHOW ROOM TOOLTIP
                    // =================================================

                    updateRoomTooltip(hoveredRoom, event);

                    renderer.domElement.style.cursor = "pointer";

                    // ==============================================
                    // PHASE 7.9
                    // APPLY HOVER VISUAL
                    // ==============================================

                    if (hoveredRoom !== selectedRoom) {
                        applyRoomHoverVisual(hoveredRoom);
                    }
                } else {
                    hoveredRoom = null;

                    renderer.domElement.style.cursor = "grab";

                    hideRoomTooltip();
                }
            });

            // =====================================================
            // PHASE 7.7
            // HIDE TOOLTIP WHEN POINTER LEAVES 3D VIEW
            // =====================================================

            renderer.domElement.addEventListener("pointerleave", () => {
                hideRoomTooltip();
            });

            // =====================================================
            // PHASE 3
            // ROOM CLICK SELECTION
            // =====================================================

            renderer.domElement.addEventListener("click", function (event) {
                // =====================================================
                // EXTERIOR MODE
                //
                // IMPORTANT:
                // Clicking the exterior building no longer opens
                // the interior.
                //
                // The user can freely rotate and interact with the
                // exterior.
                //
                // Interior is opened only through:
                // "Enter Building" button.
                // =====================================================

                if (currentBuildingView === "exterior") {

                    // Do not run room selection while outside.
                    return;
                }

                getViewerMousePosition(event);

                raycaster.setFromCamera(mouse, camera);

                // =================================================
                // PHASE 7.4
                // ONLY CLICK ROOMS ON VISIBLE FLOORS
                // =================================================

                const intersections = raycaster.intersectObjects(
                    getVisibleClickableRooms(),
                    false,
                );

                if (intersections.length === 0) {
                    if (selectedRoom) {
                        restoreRoomVisual(selectedRoom);

                        selectedRoom = null;
                    }

                    roomDetailsPanel?.classList.remove("visible");

                    return;
                }

                const roomMesh = intersections[0].object;

                // ==============================================
                // PHASE 7.9
                // RESTORE PREVIOUS SELECTED ROOM
                // ==============================================

                if (selectedRoom && selectedRoom !== roomMesh) {
                    restoreRoomVisual(selectedRoom);
                }

                // ==============================================
                // SELECT NEW ROOM
                // ==============================================

                selectedRoom = roomMesh;

                applyRoomSelectedVisual(selectedRoom);

                // =================================================
                // SAVE CURRENT CAMERA VIEW BEFORE ROOM FOCUS
                // =================================================

                cameraPositionBeforeRoomSelection = camera.position.clone();

                cameraTargetBeforeRoomSelection = controls.target.clone();

                focusCameraOnObject(selectedRoom);

                // ==============================================
                // TEST REAL DATABASE ROOM INFORMATION
                // ==============================================

                console.log("Selected 3D Room:", selectedRoom.userData);

                // =====================================================
                // PHASE 7.8
                // OPEN ROOM DETAILS PANEL
                // =====================================================

                openRoomDetailsPanel(selectedRoom.userData);
            });

            // =====================================================
            // PHASE 7.8
            // CLOSE ROOM DETAILS PANEL
            // =====================================================

            roomDetailsClose?.addEventListener("click", () => {
                roomDetailsPanel?.classList.remove("visible");

                // =================================================
                // PHASE 7.9
                // CLEAR SELECTED ROOM VISUAL
                // =================================================

                if (selectedRoom) {
                    restoreRoomVisual(selectedRoom);

                    selectedRoom = null;
                }

                // =================================================
                // PHASE 7.9
                // SMOOTHLY RETURN TO PREVIOUS CAMERA VIEW
                // =================================================

                if (
                    cameraPositionBeforeRoomSelection &&
                    cameraTargetBeforeRoomSelection
                ) {
                    cameraTransition = {
                        startPosition: camera.position.clone(),

                        endPosition: cameraPositionBeforeRoomSelection.clone(),

                        startTarget: controls.target.clone(),

                        endTarget: cameraTargetBeforeRoomSelection.clone(),

                        startTime: performance.now(),

                        duration: 800,
                    };

                    // Clear saved camera state
                    cameraPositionBeforeRoomSelection = null;
                    cameraTargetBeforeRoomSelection = null;
                }
            });

            // =====================================================
            // PHASE 7.8
            // VIEW FULL ROOM DETAILS
            // =====================================================

            function goToRoomLayout(roomId, floorId) {
                if (!roomId || goToRoomLayout.busy) {
                    return;
                }

                goToRoomLayout.busy = true;

                if (building3DRoomViewUrl) {
                    window.location.href = building3DRoomViewUrl.replace("__ROOM__", encodeURIComponent(roomId));
                    return;
                }

                const params = new URLSearchParams({ room: String(roomId) });
                if (floorId) {
                    params.set("floor", String(floorId));
                }

                window.location.href = `/maintenance/infrastructure?${params.toString()}`;
            }

            roomDetailsView?.addEventListener("click", () => {
                goToRoomLayout(
                    roomDetailsView.dataset.roomId,
                    roomDetailsView.dataset.floorId,
                );
            });

            // =====================================================
            // FIND A PERSON'S ASSIGNED PROPERTY
            // =====================================================

            function findRoomMesh(roomId) {
                return clickableRooms.find(
                    (room) => String(room.userData.roomId) === String(roomId),
                );
            }

            function selectRoomFromFinder(roomId) {
                const roomMesh = findRoomMesh(roomId);
                if (!roomMesh) {
                    return;
                }

                const select = () => {
                    if (roomMesh.parent && !roomMesh.parent.visible) {
                        document
                            .querySelector(
                                `.building-floor-filter[data-floor-filter="${roomMesh.userData.floorId}"]`,
                            )
                            ?.click();
                    }

                    if (selectedRoom && selectedRoom !== roomMesh) {
                        restoreRoomVisual(selectedRoom);
                    }

                    selectedRoom = roomMesh;
                    applyRoomSelectedVisual(selectedRoom);

                    if (!cameraPositionBeforeRoomSelection) {
                        cameraPositionBeforeRoomSelection = camera.position.clone();
                        cameraTargetBeforeRoomSelection = controls.target.clone();
                    }

                    focusCameraOnObject(selectedRoom);
                    openRoomDetailsPanel(selectedRoom.userData);
                };

                if (currentBuildingView === "interior" && !isBuildingViewTransitioning) {
                    select();
                    return;
                }

                enterInteriorMode();

                const startedAt = performance.now();
                const waitForInterior = () => {
                    if (currentBuildingView === "interior" && !isBuildingViewTransitioning) {
                        select();
                    } else if (performance.now() - startedAt < 5000) {
                        requestAnimationFrame(waitForInterior);
                    }
                };
                requestAnimationFrame(waitForInterior);
            }

            (function initCustodianFinder() {
                const finder = document.getElementById("buildingCustodianFinder");
                const toggle = document.getElementById("buildingCustodianFinderToggle");
                const panel = document.getElementById("buildingCustodianFinderPanel");
                const search = document.getElementById("buildingCustodianFinderSearch");
                const results = document.getElementById("buildingCustodianFinderResults");

                if (!finder || !toggle || !panel || !search || !results) {
                    return;
                }

                const people = (Array.isArray(building3DCustodians) ? building3DCustodians : [])
                    .map((person) => ({
                        ...person,
                        rooms: (person.rooms || []).filter((room) => findRoomMesh(room.roomId)),
                    }))
                    .filter((person) => person.rooms.length > 0);

                if (people.length === 0) {
                    return;
                }

                finder.hidden = false;
                let expandedPersonId = null;

                const roomName = (roomId) => findRoomMesh(roomId)?.userData.roomName || "Room";
                const itemLabel = (count) => `${count} ${count === 1 ? "item" : "items"}`;

                function render() {
                    const term = search.value.trim().toLowerCase();
                    const matches = people.filter(
                        (person) =>
                            term === "" ||
                            person.name.toLowerCase().includes(term) ||
                            String(person.employeeId || "").toLowerCase().includes(term),
                    );

                    results.innerHTML = "";

                    if (matches.length === 0) {
                        const empty = document.createElement("p");
                        empty.className = "building-custodian-finder-empty";
                        empty.textContent = "No one in this building matches.";
                        results.appendChild(empty);
                        return;
                    }

                    matches.forEach((person) => {
                        const row = document.createElement("button");
                        row.type = "button";
                        row.className = "building-custodian-finder-person";

                        const text = document.createElement("span");
                        const name = document.createElement("strong");
                        name.textContent = person.name;
                        const meta = document.createElement("small");
                        meta.textContent = person.rooms.length === 1
                            ? roomName(person.rooms[0].roomId)
                            : `${person.rooms.length} rooms`;
                        text.append(name, meta);

                        const count = document.createElement("span");
                        count.className = "building-custodian-finder-count";
                        count.textContent = itemLabel(person.itemCount);

                        row.append(text, count);
                        row.addEventListener("click", () => {
                            if (person.rooms.length === 1) {
                                selectRoomFromFinder(person.rooms[0].roomId);
                                return;
                            }
                            expandedPersonId = expandedPersonId === person.id ? null : person.id;
                            render();
                        });
                        results.appendChild(row);

                        if (expandedPersonId === person.id) {
                            person.rooms.forEach((room) => {
                                const roomRow = document.createElement("button");
                                roomRow.type = "button";
                                roomRow.className = "building-custodian-finder-room";

                                const label = document.createElement("span");
                                label.textContent = roomName(room.roomId);
                                const roomCount = document.createElement("small");
                                roomCount.textContent = itemLabel(room.count);

                                roomRow.append(label, roomCount);
                                roomRow.addEventListener("click", () => selectRoomFromFinder(room.roomId));
                                results.appendChild(roomRow);
                            });
                        }
                    });
                }

                function setOpen(open) {
                    panel.hidden = !open;
                    toggle.setAttribute("aria-expanded", open ? "true" : "false");
                    if (open) {
                        render();
                        search.focus();
                    }
                }

                toggle.addEventListener("click", () => setOpen(panel.hidden));
                search.addEventListener("input", () => {
                    expandedPersonId = null;
                    render();
                });
                search.addEventListener("keydown", (event) => {
                    event.stopPropagation();
                    if (event.key === "Escape") {
                        setOpen(false);
                    }
                });
            })();

            // =====================================================
            // PHASE 3
            // TEMPORARY ROOM INSPECTOR CONNECTION
            // =====================================================

            function open3DRoomInspector(room) {
                // ==============================================
                // MAKE SURE THE ROOM HAS A DATABASE ID
                // ==============================================

                if (!room || !room.roomId) {
                    console.error(
                        "Selected 3D room does not have a valid room ID:",
                        room,
                    );

                    return;
                }

                goToRoomLayout(room.roomId, room.floorId);
            }

            // =====================================================
            // GRID
            // =====================================================

            // =====================================================
            // ZOOM BUTTONS
            // =====================================================

            document.getElementById("buildingZoomIn")?.addEventListener("click", () => {
                camera.position.multiplyScalar(0.85);

                controls.update();
            });

            document
                .getElementById("buildingZoomOut")
                ?.addEventListener("click", () => {
                    camera.position.multiplyScalar(1.15);

                    controls.update();
                });

            // =====================================================
            // RESET VIEW
            // Hidden icon — same action via:
            // hold left-click on the building → press R → release
            // =====================================================

            function resetBuildingCameraView() {
                if (selectedRoom) {
                    restoreRoomVisual(selectedRoom);
                    selectedRoom = null;
                }

                hoveredRoom = null;
                hideRoomTooltip();
                roomDetailsPanel?.classList.remove("visible");
                cameraTransition = null;

                if (currentBuildingView === "interior") {
                    const interiorView = getInteriorFocusView();
                    camera.position.copy(interiorView.position);
                    controls.target.copy(interiorView.target);
                    controls.update();
                    return;
                }

                const exteriorView = getExteriorFocusView();
                camera.position.copy(exteriorView.position);
                controls.target.copy(exteriorView.target);
                controls.update();
            }

            document
                .getElementById("buildingReset")
                ?.addEventListener("click", () => {
                    resetBuildingCameraView();
                });

            // Hold left-click on building + R + release → reset
            let isHoldingBuildingPointer = false;
            let armedBuildingViewReset = false;
            const buildingResetPointer = new THREE.Vector2();

            function isPointerOnBuilding(clientX, clientY) {
                const rect = renderer.domElement.getBoundingClientRect();
                buildingResetPointer.x =
                    ((clientX - rect.left) / rect.width) * 2 - 1;
                buildingResetPointer.y =
                    -((clientY - rect.top) / rect.height) * 2 + 1;

                raycaster.setFromCamera(buildingResetPointer, camera);

                const targets = [];
                if (currentBuildingView === "exterior") {
                    targets.push(exteriorBuilding);
                } else {
                    targets.push(building);
                }

                const hits = raycaster.intersectObjects(targets, true);
                return hits.length > 0;
            }

            renderer.domElement.addEventListener("pointerdown", (event) => {
                if (event.button !== 0) {
                    return;
                }

                isHoldingBuildingPointer = isPointerOnBuilding(
                    event.clientX,
                    event.clientY,
                );
                armedBuildingViewReset = false;
            });

            window.addEventListener("pointerup", (event) => {
                if (event.button !== 0) {
                    return;
                }

                if (isHoldingBuildingPointer && armedBuildingViewReset) {
                    resetBuildingCameraView();
                }

                isHoldingBuildingPointer = false;
                armedBuildingViewReset = false;
            });

            window.addEventListener("keydown", (event) => {
                if (event.repeat) {
                    return;
                }

                const key = event.key?.toLowerCase();
                if (key !== "r") {
                    return;
                }

                const active = document.activeElement;
                const typing =
                    active &&
                    (active.tagName === "INPUT" ||
                        active.tagName === "TEXTAREA" ||
                        active.isContentEditable);

                if (typing) {
                    return;
                }

                if (!isHoldingBuildingPointer) {
                    return;
                }

                armedBuildingViewReset = true;
                event.preventDefault();
            });

            // =====================================================
            // RESPONSIVE 3D VIEWPORT RESIZE
            // KEEPS THE BUILDING VISIBLE WHEN THE SCREEN CHANGES
            // =====================================================

            let lastBuilding3DSize = "";

            function resizeBuilding3DViewer(force = false) {

                const width = container.clientWidth;
                const height = container.clientHeight;

                if (width <= 0 || height <= 0) {
                    return;
                }

                // Resizing reallocates the composer/bloom GPU buffers; skip no-op calls.
                const sizeKey = width + "x" + height;
                if (!force && sizeKey === lastBuilding3DSize) {
                    return;
                }
                lastBuilding3DSize = sizeKey;

                // =================================================
                // UPDATE CAMERA ASPECT RATIO
                // =================================================

                camera.aspect = width / height;
                camera.updateProjectionMatrix();


                // =================================================
                // UPDATE THREE.JS RENDERER
                // =================================================

                renderer.setSize(width, height, false);


                // =================================================
                // UPDATE POST PROCESSING
                // IMPORTANT BECAUSE YOU ARE USING EffectComposer
                // =================================================

                composer.setSize(width, height);


                // =================================================
                // UPDATE BLOOM EFFECT SIZE
                // =================================================

                bloomPass.setSize(width, height);
            }


            // =====================================================
            // WATCH THE ACTUAL BUILDING VIEWPORT
            // =====================================================

            const resizeObserver = new ResizeObserver(() => {

                requestAnimationFrame(() => {
                    resizeBuilding3DViewer();
                });

            });

            resizeObserver.observe(container);


            // =====================================================
            // ALSO HANDLE BROWSER / SCREEN RESIZE
            // =====================================================

            window.addEventListener("resize", () => {

                requestAnimationFrame(() => {
                    resizeBuilding3DViewer();
                });

            });


            // =====================================================
            // HANDLE PHONE / TABLET ORIENTATION CHANGE
            // =====================================================

            window.addEventListener("orientationchange", () => {

                setTimeout(() => {
                    resizeBuilding3DViewer();
                }, 150);

            });


            // =====================================================
            // INITIAL SIZE CHECK
            // =====================================================

            resizeBuilding3DViewer();

            // =====================================================
            // ANIMATION
            // =====================================================

            let building3DInView = true;
            let building3DContextLost = false;
            let building3DFrameId = 0;

            function canRenderBuilding3D() {
                return building3DInView && !document.hidden && !building3DContextLost;
            }

            function startBuilding3DLoop() {
                if (!building3DFrameId && canRenderBuilding3D()) {
                    building3DFrameId = requestAnimationFrame(animate);
                }
            }

            new IntersectionObserver((entries) => {
                building3DInView = entries.some((entry) => entry.isIntersecting);
                startBuilding3DLoop();
            }).observe(container);

            document.addEventListener("visibilitychange", startBuilding3DLoop);

            const building3DContextNotice = document.createElement("div");
            building3DContextNotice.className = "building-3d-context-notice";
            building3DContextNotice.hidden = true;
            building3DContextNotice.innerHTML =
                '<p class="building-3d-context-notice-title">3D view paused</p>' +
                '<p class="building-3d-context-notice-text">Your graphics card ran short on memory. The view resumes automatically, or you can reload it.</p>' +
                '<button type="button" class="building-3d-context-notice-btn">Reload 3D view</button>';
            building3DContextNotice
                .querySelector("button")
                .addEventListener("click", () => window.location.reload());
            container.appendChild(building3DContextNotice);

            renderer.domElement.addEventListener("webglcontextlost", (event) => {
                event.preventDefault();
                building3DContextLost = true;
                building3DContextNotice.hidden = false;
            });

            renderer.domElement.addEventListener("webglcontextrestored", () => {
                building3DContextLost = false;
                building3DContextNotice.hidden = true;
                resizeBuilding3DViewer(true);
                startBuilding3DLoop();
            });

            function animate() {
                building3DFrameId = 0;

                if (!canRenderBuilding3D()) {
                    return;
                }

                building3DFrameId = requestAnimationFrame(animate);

                // =================================================
                // PHASE 7.6
                // SMOOTH CAMERA TRANSITION
                // =================================================

                if (cameraTransition) {
                    const elapsed = performance.now() - cameraTransition.startTime;

                    let progress = elapsed / cameraTransition.duration;

                    progress = Math.min(progress, 1);

                    // =================================================
                    // SMOOTH EASE IN AND EASE OUT
                    // =================================================

                    const easedProgress =
                        progress < 0.5
                            ? 2 * progress * progress
                            : 1 - Math.pow(-2 * progress + 2, 2) / 2;

                    if (cameraTransition.mode === "orbit") {
                        const liftMix = cameraTransition.lift
                            ? Math.sin(easedProgress * Math.PI)
                            : 0;
                        const theta = THREE.MathUtils.lerp(
                            cameraTransition.startTheta,
                            cameraTransition.endTheta,
                            easedProgress,
                        );
                        const phi = THREE.MathUtils.lerp(
                            THREE.MathUtils.lerp(
                                cameraTransition.startPhi,
                                cameraTransition.endPhi,
                                easedProgress,
                            ),
                            0.42,
                            liftMix * 0.88,
                        );
                        const radius =
                            THREE.MathUtils.lerp(
                                cameraTransition.startRadius,
                                cameraTransition.endRadius,
                                easedProgress,
                            ) * (1 + liftMix * 0.28);

                        cameraTransition.offset.setFromSphericalCoords(
                            radius,
                            phi,
                            theta,
                        );
                        camera.position
                            .copy(cameraTransition.pivot)
                            .add(cameraTransition.offset);

                        controls.target.lerpVectors(
                            cameraTransition.startTarget,
                            cameraTransition.endTarget,
                            easedProgress,
                        );
                    } else {
                        camera.position.lerpVectors(
                            cameraTransition.startPosition,
                            cameraTransition.endPosition,
                            easedProgress,
                        );

                        controls.target.lerpVectors(
                            cameraTransition.startTarget,
                            cameraTransition.endTarget,
                            easedProgress,
                        );
                    }

                    // =================================================
                    // END TRANSITION
                    // =================================================

                    if (progress >= 1) {
                        cameraTransition = null;
                    }
                }

                controls.update();

                // =================================================
                // STATUS INDICATOR ANIMATION (infinite loop)
                // =================================================

                const statusPulse = performance.now() * 0.0045;

                const animateStatusIndicator = (indicator, index, offset) => {
                    if (!indicator || !indicator.visible) {
                        return;
                    }

                    // Wait until texture is ready (async wrench image)
                    if (!indicator.material?.map) {
                        return;
                    }

                    const phase = statusPulse + index * 0.7 + offset;
                    const bob = Math.sin(phase) * 0.08;
                    const scale = 1.05 + (Math.sin(phase * 1.1) + 1) * 0.1;

                    indicator.position.y =
                        (indicator.userData.baseY || 1.2) + bob;
                    indicator.scale.set(scale, scale, 1);

                    // Light bulb keeps a soft pulse; wrench stays solid
                    if (indicator.userData.type === "critical-bulb") {
                        indicator.material.opacity =
                            0.72 + (Math.sin(phase * 1.35) + 1) * 0.2;
                    } else {
                        indicator.material.opacity = 1;
                    }
                };

                criticalRoomIndicators.forEach((bulb, index) => {
                    animateStatusIndicator(bulb, index, 0);
                });

                maintenanceRoomIndicators.forEach((tool, index) => {
                    animateStatusIndicator(tool, index, 1.1);
                });

                updateEnterBuildingButtonPosition();
                updateRoomDetailsPanelPosition();

                composer.render();
            }

            startBuilding3DLoop();

            // Place once after exterior is ready
            updateEnterBuildingButtonPosition();

            console.log("Three.js building viewer started successfully.");
        }
    </script>

    <script>
        // =====================================================
        // BUILDING OVERVIEW FULL SCREEN
        // =====================================================

        (function initBuildingFullscreen() {
            const view = document.getElementById("dashboardBuildingView");
            const enterButton = document.getElementById("buildingFullscreenBtn");
            const exitButton = document.getElementById("buildingExitFullscreenBtn");

            if (!view || !enterButton || !exitButton) {
                return;
            }

            function getFullscreenElement() {
                return (
                    document.fullscreenElement ||
                    document.webkitFullscreenElement ||
                    document.mozFullScreenElement ||
                    document.msFullscreenElement ||
                    null
                );
            }

            function isBuildingFullscreen() {
                return (
                    getFullscreenElement() === view ||
                    view.classList.contains("is-pseudo-fullscreen")
                );
            }

            function enterPseudoFullscreen() {
                view.classList.add("is-pseudo-fullscreen");
                document.body.style.overflow = "hidden";
                syncBuildingFullscreenUi();
            }

            function syncBuildingFullscreenUi() {
                const active = isBuildingFullscreen();

                view.classList.toggle("is-building-fullscreen", active);
                enterButton.setAttribute("aria-pressed", active ? "true" : "false");

                if (window.lucide && typeof lucide.createIcons === "function") {
                    lucide.createIcons({ root: exitButton });
                }
            }

            function enterBuildingFullscreen() {
                const request =
                    view.requestFullscreen ||
                    view.webkitRequestFullscreen ||
                    view.mozRequestFullScreen ||
                    view.msRequestFullscreen;

                if (!request) {
                    enterPseudoFullscreen();
                    return;
                }

                const result = request.call(view);

                if (result && typeof result.catch === "function") {
                    result.catch(() => {
                        enterPseudoFullscreen();
                    });
                }
            }

            function exitBuildingFullscreen() {
                const activeElement = getFullscreenElement();

                if (activeElement) {
                    if (document.exitFullscreen) {
                        return document.exitFullscreen();
                    }

                    if (document.webkitExitFullscreen) {
                        return document.webkitExitFullscreen();
                    }

                    if (document.mozCancelFullScreen) {
                        return document.mozCancelFullScreen();
                    }

                    if (document.msExitFullscreen) {
                        return document.msExitFullscreen();
                    }
                }

                view.classList.remove("is-pseudo-fullscreen");
                document.body.style.overflow = "";
                syncBuildingFullscreenUi();
            }

            enterButton.addEventListener("click", () => {
                if (isBuildingFullscreen()) {
                    exitBuildingFullscreen();
                    return;
                }

                enterBuildingFullscreen();
            });

            exitButton.addEventListener("click", () => {
                exitBuildingFullscreen();
            });

            [
                "fullscreenchange",
                "webkitfullscreenchange",
                "mozfullscreenchange",
                "MSFullscreenChange",
            ].forEach((eventName) => {
                document.addEventListener(eventName, syncBuildingFullscreenUi);
            });

            document.addEventListener("keydown", (event) => {
                if (event.key !== "Escape") {
                    return;
                }

                if (!view.classList.contains("is-pseudo-fullscreen")) {
                    return;
                }

                exitBuildingFullscreen();
            });
        })();
    </script>
