<html>
<head>
    <title>threejs - models</title>

    <style>
    	body{
    		margin: 0;
    		overflow: hidden;
    	}
    </style>
</head>
<body>

    <canvas id="myCanvas"></canvas>
    <script id="HoloVS" type="x-shader/x-vertex">
		varying vec2 vUv;

        void main() {

            vUv = uv;

            gl_Position = projectionMatrix * modelViewMatrix *vec4( position, 1.0 );

        }
    </script>
    <script id="HoloFS" type="x-shader/x-fragment">
		varying vec2 vUv;

		uniform sampler2D texture1;
	    uniform float iTime;

	      	//--------------UTILITIES
	      				vec3 mod289(vec3 x) {
						  return x - floor(x * (1.0 / 289.0)) * 289.0;
						}

						vec2 mod289(vec2 x) {
						  return x - floor(x * (1.0 / 289.0)) * 289.0;
						}

						vec3 permute(vec3 x) {
						  return mod289(((x*34.0)+1.0)*x);
						}

						float snoise(vec2 v)
						  {
						  const vec4 C = vec4(0.211324865405187,  // (3.0-sqrt(3.0))/6.0
						                      0.366025403784439,  // 0.5*(sqrt(3.0)-1.0)
						                     -0.577350269189626,  // -1.0 + 2.0 * C.x
						                      0.024390243902439); // 1.0 / 41.0
						// First corner
						  vec2 i  = floor(v + dot(v, C.yy) );
						  vec2 x0 = v -   i + dot(i, C.xx);

						// Other corners
						  vec2 i1;
						  //i1.x = step( x0.y, x0.x ); // x0.x > x0.y ? 1.0 : 0.0
						  //i1.y = 1.0 - i1.x;
						  i1 = (x0.x > x0.y) ? vec2(1.0, 0.0) : vec2(0.0, 1.0);
						  // x0 = x0 - 0.0 + 0.0 * C.xx ;
						  // x1 = x0 - i1 + 1.0 * C.xx ;
						  // x2 = x0 - 1.0 + 2.0 * C.xx ;
						  vec4 x12 = x0.xyxy + C.xxzz;
						  x12.xy -= i1;

						// Permutations
						  i = mod289(i); // Avoid truncation effects in permutation
						  vec3 p = permute( permute( i.y + vec3(0.0, i1.y, 1.0 ))
								+ i.x + vec3(0.0, i1.x, 1.0 ));

						  vec3 m = max(0.5 - vec3(dot(x0,x0), dot(x12.xy,x12.xy), dot(x12.zw,x12.zw)), 0.0);
						  m = m*m ;
						  m = m*m ;

						// Gradients: 41 points uniformly over a line, mapped onto a diamond.
						// The ring size 17*17 = 289 is close to a multiple of 41 (41*7 = 287)

						  vec3 x = 2.0 * fract(p * C.www) - 1.0;
						  vec3 h = abs(x) - 0.5;
						  vec3 ox = floor(x + 0.5);
						  vec3 a0 = x - ox;

						// Normalise gradients implicitly by scaling m
						// Approximation of: m *= inversesqrt( a0*a0 + h*h );
						  m *= 1.79284291400159 - 0.85373472095314 * ( a0*a0 + h*h );

						// Compute final noise value at P
						  vec3 g;
						  g.x  = a0.x  * x0.x  + h.x  * x0.y;
						  g.yz = a0.yz * x12.xz + h.yz * x12.yw;
						  return 130.0 * dot(m, g);
						}

						float rand(vec2 co)
						{
						   return fract(sin(dot(co.xy,vec2(12.9898,78.233))) * 43758.5453);
						}
			//-------------MAIN
	        void main() {

	            vec2 uv = vUv;
    			float time = iTime * 4.0;
    
			    // Noise
			    float noise = max(0.0, snoise(vec2(time, uv.y * 0.3)) - 0.3) * (1.0 / 0.7);
				noise = noise + (snoise(vec2(time*50.0, uv.y * 0.2)) - 0.5) * 0.15;
    
			    float xpos = uv.x - noise * noise * 0.25;
				gl_FragColor = texture2D(texture1, vec2(xpos, uv.y));

			    // Mix in some random interference for lines
			    gl_FragColor.rgb = mix(gl_FragColor.rgb, vec3(rand(vec2(uv.y * time))), noise * 0.3).rgb;
    
			    // Apply a line pattern every 4 pixels
			    if (floor(mod(gl_FragColor.y * 0.25, 2.0)) == 0.0)
			    {
			        gl_FragColor.rgb *= 1.0 - (0.15 * noise);
			    }
			    
			    // Shift green/blue channels (using the red channel)
			    gl_FragColor.g = mix(gl_FragColor.r, texture2D(texture1, vec2(xpos + noise * 0.05, uv.y)).g, 0.25);
			    //gl_FragColor.b = mix(gl_FragColor.r, texture2D(texture1, vec2(xpos - noise * 0.05, uv.y)).b, 0.25);
			}

	</script>
	<script id="vertexShader" type="x-shader/x-vertex">
		varying vec2 vUv;

        void main() {

            vUv = uv;
            gl_Position = vec4( position, 1.0 );

        }

    </script>
    <script id="fragmentShader" type="x-shader/x-fragment">
        varying vec2 vUv;

        uniform float iTime;
        uniform vec2 iResolution;

        #define S(x, y, z) smoothstep(x, y, z)
		#define M(t, d) mat2(cos(t * d), sin(t * d), -sin(t * d), cos(t * d))
		#define SEED .2831
		#define PI acos(-1.)


		float hash(float n)
		{
		    return fract(sin(n) * 91438.55123);   
		}

		float hash2(vec2 p)
		{
		    // hash2 taken from Dave Hoskins 
		    // https://www.shadertoy.com/view/4djSRW
			vec3 p3  = fract(vec3(p.xyx) * SEED);
		    p3 += dot(p3, p3.yzx + 19.19);
		    return fract((p3.x + p3.y) * p3.z);
		}

		float noise( in vec2 x ) {
		    vec2 p = floor(x);
		    vec2 f = fract(x);
		    f = f*f*(3.0-2.0*f);
		    float n = p.x + p.y*57.0;
		    return mix(mix( hash(n + 0.0), hash(n + 1.0), f.x), mix(hash(n + 57.0), hash(n + 58.0), f.x), f.y);
		}

		mat2 m = mat2( 0.6, 0.6, -0.6, 0.8);
		float fbm(vec2 p){
		 
		    float f = 0.0;
		    f += 0.5000 * noise(p); p *= m * 2.02;
		    f += 0.2500 * noise(p); p *= m * 2.03;
		    f += 0.1250 * noise(p); p *= m * 2.01;
		    f += 0.0625 * noise(p); p *= m * 2.04;
		    f /= 0.9375;
		    return f;
		}

		float star(vec2 uv, vec2 scale, float density){

		    density *= 10.;
    		scale *= 10.;
		    vec2 grid = uv * scale;
		    vec2 id = floor(grid);  
		    grid = fract(grid) - .5;

		    float d = length(grid);  
		    float r = pow(hash2(id), density);
		    float star = S(-.01, clamp(r,.0,.5), d);

		    return 1. - star ;
		}

		float halo(vec2 uv, vec2 scale, float density){

		    density *= 10.;
    		scale *= 10.;
		    vec2 grid = uv * scale;
		    vec2 id = floor(grid);  
		    grid = fract(grid) - .5;

		    float d = length(grid);  
		    float r = pow(hash2(id), density);
		    float a = S(-.4, clamp(r,.0,.5), d);

		    return 1. - a ;
		}

		float bigstar(vec2 uv, vec2 scale, float density, float angle, float speed){

    		scale *= 2.;
		    vec2 grid = uv * scale;
		    vec2 id = floor(grid);  
		    grid = fract(grid) - .5;

		    float d = length(grid);  
		    float dx = length(M(-angle,speed)*grid*vec2(5.0,.1)); 
		    float dy = length(M(-angle,speed)*grid*vec2(.1,5.)); 
		    float r = pow(hash2(id), density);
		    float star = S(-.01, clamp(r,.0,.3), d);
		    
		    float l = S(.0, 1.,length(grid-1.));
		 

		    return   1. - S(-.1,r*l*l, 2.*sqrt(dot(dx,dy)));
		}


		vec2 dist(vec2 uv){
		    
		    vec2 d = vec2(.0);
		    d += .2*dot(uv,uv)-.5;
		    
		 return d;   
		}


		void main()
		{
			vec2 uv = vUv;
    		uv.x *= iResolution.x / iResolution.y;

		    vec2 st = uv * 1.35;
		   
		    st += dist(st);
		    float t = iTime * .01;
		    
		    uv-= vec2(.8,.5);
		    st += sign(dot(uv,uv));
		    uv += sign(dot(uv,uv));


		    float mw = S(-.5, 1.5, length(uv));
		    
		    
		    float a0 = halo(M( t, 2.5) * uv, vec2(15.), mw * 50.0);
		    float l0 = a0+bigstar(M( t, 2.5) * uv, vec2(15.), mw * 50.0,  t , 2.5);
		    
		    
		    
		    float l1 = star(M(10.+t, 2.0) * uv * .8, vec2(120.0), mw * 80.0);
		    float l2 = star(M(20.+t, 1.5) * uv * .6, vec2(150.), mw * 100.0);
		    float l3 = star(M(t*1.4, 1.0) * uv, vec2(200.), mw * 120.0);
		    
		    float a4 = halo(M(PI+t*1.4, 3.0) * uv, vec2(15.), mw * 90.0);
		    float l4 = a4+bigstar(M(PI+t*1.4, 3.) * uv, vec2(15.), mw * 90.0, t * 1.4  , 3.);
		    
		    uv -= sign(dot(uv,uv));
		    
		    vec2 n1 = vec2(.2 * t, .2 * t);
		    vec2 n2 = vec2(.3 * t, .3 * t);
		    vec2 n3 = vec2(.4 * t, .4 * t);
		    
		    float r = .23 * fbm(M(t, 2.5) * (1.5*st + n1));
		    float g = .24 * fbm(M(t, 2.0) * ( 2. * st + n2));
		    float b = .26 * fbm(M(t, 1.5) * (1. * st + n3));
		    
		    vec4 cl = pow(vec4(r, g, b, 1.), vec4(1.3));
		    cl = cl + cl;
		    
		    vec4 s = vec4(vec3(l0 + l1 + l2 + l3 + l4), 1.0);
		    cl = pow(cl  + cl , vec4(1.5));
		    cl = pow(cl + cl + cl , vec4(1.5))*.7;
		 
		   
		    cl = mix( cl + cl,  cl * s , .85);
		 	
		    cl += cl * 2.;
		    cl.xyz += .3 * pow(1.-length(uv/2.), 2.5);

		    cl = .9 * clamp(cl, vec4(.0), vec4(1.));

		    cl += S(.0,1.,S(.7, 1.,s));
		    float cl1 = fbm((fbm(st*.4) - vec2(t, t )) * 15.) * dist(uv).x;

		    
		    cl = mix(cl,   cl + cl1+cl1+cl1, .8);
			cl += .33 * fbm( vec2(-iTime*.3,iTime*.3) + (uv * 20.)) ;
		    cl -= min(cl, vec4(-.3, .0, -.5, 1.));
		    cl -= .8*dot(uv,uv);
		    
			gl_FragColor = cl ; 
		}
    </script>
    <script type="module">
	import * as THREE from '../build/three.module.js';
	
	import { GLTFLoader } from './jsm/loaders/GLTFLoader.js';
	import { OrbitControls } from './jsm/controls/OrbitControls.js';
	import { DRACOLoader } from './jsm/loaders/DRACOLoader.js';
	import { RGBELoader } from './jsm/loaders/RGBELoader.js';
	import { RoughnessMipmapper } from './jsm/utils/RoughnessMipmapper.js';
	import { EffectComposer } from './jsm/postprocessing/EffectComposer.js';
	import { RenderPass } from './jsm/postprocessing/RenderPass.js';
	import { UnrealBloomPass } from './jsm/postprocessing/UnrealBloomPass.js';
	import { GUI } from './jsm/libs/dat.gui.module.js';

    var renderer,
    	scene,
    	camera,
    	controls,
    	uniforms,
    	myCanvas = document.getElementById('myCanvas');

    //-----------------------------------------------RENDERER
    renderer = new THREE.WebGLRenderer({
      canvas: myCanvas, 
      antialias: true
    });

    renderer.setClearColor(0x000000);
    renderer.setPixelRatio(window.devicePixelRatio);
    renderer.setSize(window.innerWidth, window.innerHeight);
    renderer.toneMapping = THREE.ReinhardToneMapping;
    renderer.shadowMap.enabled = true;
	renderer.shadowMap.type = THREE.PCFSoftShadowMap;
	renderer.outputEncoding = THREE.sRGBEncoding;

    //----------------------------------------------CAMERA
    camera = new THREE.PerspectiveCamera(60, window.innerWidth / window.innerHeight, 0.1, 1000 );
    camera.position.set( 0, 8, 30);

    //----------------------------------------------CONTROLS
    controls = new OrbitControls( camera, renderer.domElement );
    controls.target.set( 0, 8, 0 );

    //-----------------------------------------------SCENE
    scene = new THREE.Scene();

    //----------------------------------------------LIGHTS
	var dirLight = new THREE.PointLight(0xd68e13, 0.2);
	dirLight.position.set(15, 25, 15);
	dirLight.castShadow = true;
	dirLight.receiveShadow = true;
	dirLight.shadow.mapSize.set(2048, 2048);
	dirLight.shadow.bias = - 0.0005;

	var dirLight2 = new THREE.PointLight(0x174be8, 1);
	dirLight2.position.set(-20, 15, -20);
	dirLight2.castShadow = true;
	dirLight2.receiveShadow = true;
	dirLight2.shadow.mapSize.set(2048, 2048);
	dirLight2.shadow.bias = - 0.0005;

	scene.add(dirLight);
	scene.add(dirLight2);
  	
  	//LIGHT SALLE DE JEUX
  	var salleDeJeuLight1 = new THREE.PointLight(0x0B5394, 20, 4)
  	salleDeJeuLight1.position.set(-10, 3, -1);
  	salleDeJeuLight1.castShadow = true;
	salleDeJeuLight1.shadow.bias = - 0.005;

	var salleDeJeuLight2 = new THREE.PointLight(0xf62be3, 10, 4)
  	salleDeJeuLight2.position.set(-6.5, 3, -1);
  	salleDeJeuLight2.castShadow = false;

  	scene.add(salleDeJeuLight1);
  	scene.add(salleDeJeuLight2);

  	//LIGHT STAIRS
  	var stairsLight = new THREE.PointLight(0xf6b32b, 5, 3)
  	stairsLight.position.set(-2, 4.5, -1);
  	stairsLight.castShadow = true;
	stairsLight.shadow.bias = - 0.005;

	scene.add(stairsLight);

	//LIGHT MEN
	var menLight = new THREE.PointLight(0x03e8fc, 10, 3)
	menLight.position.set(0.5, 4.5, -2);
  	menLight.castShadow = false;

	var menLight2 = new THREE.PointLight(0x03e8fc, 10, 3)
	menLight2.position.set(0.5, 4.5, 0.5);
	menLight2.castShadow = false;

	var menLight3 = new THREE.PointLight(0x03e8fc, 10, 3)
	menLight3.position.set(0.5, 4.5, 2);
	menLight3.castShadow = false;

	scene.add(menLight);
	scene.add(menLight2);
	scene.add(menLight3);

	//LIGHT BOISSON
	var drinkLight = new THREE.RectAreaLight(0x32a852, 10, 1, 2)
	drinkLight.lookAt(1, 0, 0);
  	drinkLight.position.set(3, 1, -6);

	var drinkLight1 = new THREE.RectAreaLight(0xfc0303, 10, 1, 2)
	drinkLight1.lookAt(1, 0, 0);
  	drinkLight1.position.set(3, 1, -4);

  	scene.add(drinkLight);
	scene.add(drinkLight1);

	//LIGHT ARRIERE BOUTIQUE
	var backLight = new THREE.PointLight(0xed152b, 10, 8)
	backLight.position.set(-8, 4, -10);
	backLight.castShadow = true;
	backLight.shadow.bias = -0.005;

	scene.add(backLight);

  	//------------------------------------------Load GLTF
	var loader = new GLTFLoader();
	var dracoLoader = new DRACOLoader();
	dracoLoader.setDecoderPath( '/examples/js/libs/draco/' );
	loader.setDRACOLoader( dracoLoader );

    loader.load('models/gltf/Arthur&Margot/SceneE.gltf', handle_load);

    //-------------------------------------------CUBEMAP
    var pmremGenerator = new THREE.PMREMGenerator( renderer );
	pmremGenerator.compileEquirectangularShader();
	new RGBELoader()
	.setDataType( THREE.UnsignedByteType )
	.setPath( 'models/gltf/Arthur&Margot/' )
	//.load( '49TH_STREET.hdr', function ( texture ) {

	.load( '42ND_STREET.hdr', function ( texture ) {

		var envMap = pmremGenerator.fromEquirectangular( texture ).texture;
		//affichage de l'HDR
		//scene.background = envMap;
		scene.environment = envMap;

		texture.dispose();
		pmremGenerator.dispose();
		render();

		} );

	//-------------------------------------------------BLOOM
	var renderScene = new RenderPass( scene, camera );

	var bloomPass = new UnrealBloomPass( new THREE.Vector2( window.innerWidth, window.innerHeight ), 1.5, 0.4, 0.85 );
	bloomPass.threshold = 0;
	bloomPass.strength = 0.2;
	bloomPass.radius = 0;

	var composer = new EffectComposer( renderer );
	composer.addPass( renderScene );
	composer.addPass( bloomPass );
	//--------------------------------------------------HOLOGRAM
	var Holotexture = new THREE.TextureLoader().load( 'models/gltf/Arthur&Margot/TextureTV2.png' );
	var Customuniforms = {
                    "iTime": { value: 1.0 },
				      //Declare texture uniform in shader
				     texture1: { type: 't', value: Holotexture }
                };
	var holoMaterial = new THREE.ShaderMaterial({
  					uniforms: Customuniforms,
					vertexShader: document.getElementById('HoloVS').textContent,
					fragmentShader: document.getElementById('HoloFS').textContent
					});

	//--------------------------------------------------RAIN
	var numberOfDroplet = 1000;
	var particles = new THREE.Group();
    const geo = new THREE.CylinderBufferGeometry( 0.01, 0.05, 1);
    const mat = new THREE.MeshBasicMaterial( {color: 0xffffff, side: THREE.DoubleSide} );
    for(let i=0; i < numberOfDroplet; i++) {
        const particle = new THREE.Mesh(geo,mat)
        particle.velocity = 1;
        //particle.scale.set(2, 2, 2);
        particle.acceleration = new THREE.Vector3(0,-0.001,0);

        particle.position.x = Math.floor(Math.random()*100) + 1
		particle.position.y  = Math.floor(Math.random()*100) + 1
		particle.position.z  = Math.floor(Math.random()*100) + 1

        particle.position.x *= Math.floor(Math.random()*2) == 1 ? 1 : -1, 
        particle.position.z *= Math.floor(Math.random()*2) == 1 ? 1 : -1, 
        particle.position.y *= Math.floor(Math.random()*2) == 1 ? 1 : -1, 
        particles.add(particle)
    }
    particles.position.z = -4
    scene.add(particles)
    //---------------------------------------------------LOAD GLTF
    function handle_load(gltf) 
    {
        var mesh = gltf.scene;
        gltf.scene.traverse( function ( child ) 
        {
            if ( child.isMesh ) {

                child.castShadow = true;
                child.receiveShadow = true;
                if(child.material.name == "M_Neon")
                	child.material.emissiveIntensity = 100;
                if(child.material.name == "M_Holo")
                	child.material = holoMaterial;
            }
        });

		scene.add(mesh);

		mesh.position.set(0, 0, 0);
		mesh.castShadow = true;
		mesh.receiveShadow = true;
    }

    //--------------------------------------------------RENDER TARGET
    var geometry = new THREE.PlaneBufferGeometry( 2, 2 )
    uniforms = {
                    "iTime": { value: 1.0 },
                    "iResolution": {type: "v2", value: new THREE.Vector2()},
                };

    uniforms[ "iResolution" ].value.x = window.innerWidth;
    uniforms[ "iResolution" ].value.y = window.innerHeight;
    console.log(uniforms[ "iResolution" ].value);
    var material = new THREE.ShaderMaterial( {

                    uniforms: uniforms,
                    vertexShader: document.getElementById( 'vertexShader' ).textContent,
                    fragmentShader: document.getElementById( 'fragmentShader' ).textContent

                });

    const rtWidth = window.innerWidth;
    const rtHeight = window.innerHeight;
    const renderTarget = new THREE.WebGLRenderTarget(rtWidth, rtHeight);
    const rtFov = 75;
    const rtAspect = rtWidth / rtHeight;
    const rtNear = 0.1;
    const rtFar = 50;
    const rtCamera = new THREE.PerspectiveCamera(rtFov, rtAspect, rtNear, rtFar);
    rtCamera.position.z = 49;
    const rtScene = new THREE.Scene();

    var mesh = new THREE.Mesh( geometry, material );
    rtScene.add( mesh );

    //-------------------------------------------------------AUDIO
    // create an AudioListener and add it to the camera
	var listener = new THREE.AudioListener();
	camera.add( listener );

	// create a global audio source
	var sound = new THREE.Audio( listener );

	// load a sound and set it as the Audio object's buffer
	var audioLoader = new THREE.AudioLoader();
	audioLoader.load( 'models/gltf/Arthur&Margot/Music.mp3', function( buffer ) {
		sound.setBuffer( buffer );
		sound.setLoop( true );
		sound.setVolume( 0.5 );
		sound.play();
	});

    //-------------------------------------------------------RENDER LOOP
    render();

    function render(time, timestamp) 
    {
    	renderer.render(scene, camera);
    	//BLOOM
    	//composer.render();
    	
    	//RAIN
	     particles.children.forEach(p => {
	       	p.velocity = -2;
	        p.position.y += p.velocity;
	        if (p.position.y < 0)
	          p.position.y = Math.floor(Math.random()*200) + 150;
	    })

	     //lock camera height
    	if(camera.position.y <= 5) 
    		camera.position.y = 5;

    	//Render target
		uniforms[ "iTime" ].value = time / 1000;
        renderer.setRenderTarget(renderTarget);
        renderer.render(rtScene, rtCamera);
        renderer.setRenderTarget(null);
        //Ajout dans la scene
        scene.background = renderTarget.texture;

        //Holo
        Customuniforms[ "iTime" ].value = time / 1000;

    	//FLICKERING LIGHT
    	backLight.power = Math.abs(Math.sin(time) + 2) * 50;
       	requestAnimationFrame(render);
       }

    </script>
</body>
</html>